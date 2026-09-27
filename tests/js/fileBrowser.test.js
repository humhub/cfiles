import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { enableAutoUnmount, flushPromises, mount } from '@vue/test-utils';
import CfilesFileBrowser from '../../vue/CfilesFileBrowser.vue';
import FilterBar from '@core/vue/FilterBar.vue';
import { browserProps, fileRow, folderRow, insideFolder, results, topLevel } from './support/fixtures.mjs';

// The browser listens on `window` (popstate) and `document` (Escape): leaving instances mounted
// would let one test's browser answer another test's events.
enableAutoUnmount(afterEach);

const browser = (listing, over = {}) => mount(CfilesFileBrowser, { props: browserProps(listing, over) });

/** Three items, for anything about order. */
const three = () => [folderRow(), fileRow(), fileRow({ id: 22, contentId: 202, recordId: 1202, title: 'Brief.pdf' })];

const fileDrag = (files = [new File(['x'], 'a.txt')]) => ({
    dataTransfer: { types: ['Files'], files, dropEffect: 'none', setData: vi.fn() },
});
const itemDrag = () => ({
    dataTransfer: { types: ['text/plain'], files: [], dropEffect: 'none', effectAllowed: 'all', setData: vi.fn() },
});

const tile = (wrapper, key) => wrapper.find(`.c-tile-grid__tile[data-key="${key}"]`);
const pending = () => new Promise(() => {});

/** A promise the test settles when it wants to, for requests that overlap. */
const deferred = () => {
    const d = {};
    d.promise = new Promise((resolve, reject) => {
        d.resolve = resolve;
        d.reject = reject;
    });
    return d;
};

/** `client.get` answering each call with the next of the given deferreds. */
const queuedGets = (...queue) => {
    globalThis.humhubStubs.client.get = vi.fn(() => queue.shift().promise);
};

const statusMessages = () => {
    const reported = [];
    globalThis.humhub.modules.vue.setStatusHandler((entry) => reported.push(entry));
    return reported;
};

describe('CfilesFileBrowser', () => {
    beforeEach(() => {
        globalThis.humhubStubs.client.get = vi.fn(() => Promise.resolve(topLevel()));
        globalThis.humhubStubs.client.post = vi.fn(() => Promise.resolve({ results: [], errors: [] }));
        // PATCH (the view preference) goes through the bridge's ajax().
        globalThis.humhubStubs.client.ajax = vi.fn(() => Promise.resolve({ view: 'tiles' }));
        globalThis.humhubStubs.logCalls.error.length = 0;
        window.history.replaceState({}, '', '/s/x/cfiles/browse/index');
    });

    describe('mounting', () => {
        it('paints the embedded page without asking the server', () => {
            const wrapper = browser(topLevel());

            expect(wrapper.findAll('.cfiles-row')).toHaveLength(2);
            expect(globalThis.humhubStubs.client.get).not.toHaveBeenCalled();
        });

        // The top level has no folder record, so `folder` is null in the payload — nothing may
        // assume an object there.
        it('treats a null folder as the container top level', () => {
            const wrapper = browser(topLevel());

            expect(wrapper.vm.folderId).toBeNull();
            expect(wrapper.find('a.c-path-bar__root').attributes('aria-label')).toBe('Files');
            expect(wrapper.findAll('a.c-path-bar__link')).toHaveLength(0);
            expect(wrapper.find('.c-path-bar__current').exists()).toBe(false);
        });

        it('reports the open folder when there is one', () => {
            const wrapper = browser(insideFolder([fileRow()]));

            expect(wrapper.vm.folderId).toBe(9);
            expect(wrapper.find('a.c-path-bar__root').attributes('aria-label')).toBe('Files');
            expect(wrapper.findAll('a.c-path-bar__link').map((c) => c.text())).toEqual(['test123']);
            expect(wrapper.find('.c-path-bar__current').text()).toBe('sgadgasdg');
            // The crumbs link where the browser would: `?fid=`, not the payload's own URL.
            expect(wrapper.find('a.c-path-bar__link').attributes('href')).toBe('/s/x/cfiles/browse/index?fid=7');
        });

        it('renders the empty state instead of a list when the folder is empty', () => {
            const wrapper = browser(topLevel([]));

            expect(wrapper.find('.cfiles-list').exists()).toBe(false);
            expect(wrapper.find('.cfiles-empty').exists()).toBe(true);
        });
    });

    describe('navigation', () => {
        it('loads the folder and mirrors it into the URL without navigating', async () => {
            const wrapper = browser(topLevel());
            globalThis.humhubStubs.client.get = vi.fn(() => Promise.resolve(insideFolder([])));

            await wrapper.find('.cfiles-row a').trigger('click');
            await flushPromises();

            expect(globalThis.humhubStubs.client.get.mock.calls[0][0]).toContain('/5/items');
            expect(globalThis.humhubStubs.client.get.mock.calls[0][0]).toContain('parent=11');
            expect(window.location.search).toContain('fid=11');
            // Pushed without a `container` key, which is what keeps jquery.pjax's own popstate
            // handler from claiming the entry (see the component docblock).
            expect(window.history.state.cfiles).toEqual({ folderId: 11 });
        });

        it('goes back to the top level on popstate', async () => {
            const wrapper = browser(insideFolder([]));
            globalThis.humhubStubs.client.get = vi.fn(() => Promise.resolve(topLevel()));

            window.history.replaceState({}, '', '/s/x/cfiles/browse/index?fid=0');
            window.dispatchEvent(new Event('popstate'));
            await flushPromises();

            expect(globalThis.humhubStubs.client.get).toHaveBeenCalled();
            expect(globalThis.humhubStubs.client.get.mock.calls[0][0]).not.toContain('parent=');
        });

        it('goes up through the path bar', async () => {
            const wrapper = browser(insideFolder([]));
            globalThis.humhubStubs.client.get = vi.fn(() => Promise.resolve(topLevel()));

            await wrapper.find('a.c-path-bar__root').trigger('click');
            await flushPromises();

            expect(globalThis.humhubStubs.client.get.mock.calls[0][0]).not.toContain('parent=');
            expect(window.location.search).toContain('fid=0');
        });

        // The tile grid slides a level in by `level`; it has to change together with the
        // items, or the new pane slides in showing the old level's tiles.
        it('switches the level together with its items, forward going down and back going up', async () => {
            const wrapper = browser(topLevel());
            globalThis.humhubStubs.client.get = vi.fn(() => Promise.resolve(insideFolder([fileRow()])));

            wrapper.vm.open(9);
            expect(wrapper.vm.level).toBe(0);
            await flushPromises();

            expect(wrapper.vm.level).toBe(9);
            expect(wrapper.vm.direction).toBe('forward');
            expect(wrapper.vm.items.map((i) => i.id)).toEqual([21]);

            globalThis.humhubStubs.client.get = vi.fn(() => Promise.resolve(topLevel()));
            wrapper.vm.open(null);
            await flushPromises();

            expect(wrapper.vm.level).toBe(0);
            expect(wrapper.vm.direction).toBe('back');
        });

        it('keeps folder links as real hrefs so they can be opened in a new tab', () => {
            const wrapper = browser(topLevel());

            expect(wrapper.find('.cfiles-row a').attributes('href'))
                .toBe('/s/x/cfiles/browse/index?fid=11');
        });

        it('lets a modified click through to the browser', async () => {
            const wrapper = browser(topLevel());

            // Not prevented on purpose, so jsdom would try to follow the href and log "Not
            // implemented: navigation" - block that above the row (as the core's pathBar test
            // does at the document; this wrapper is not attached to it); what is asserted is
            // the browser's own reaction.
            const block = (e) => e.preventDefault();
            wrapper.element.addEventListener('click', block);
            await wrapper.find('.cfiles-row a').trigger('click', { metaKey: true });
            wrapper.element.removeEventListener('click', block);

            expect(globalThis.humhubStubs.client.get).not.toHaveBeenCalled();
        });
    });

    // Latest request wins: a response that was overtaken by a newer request is dropped.
    describe('overlapping loads', () => {
        it('shows the later of two overlapping loads even when the earlier answers last', async () => {
            const first = deferred();
            const second = deferred();
            queuedGets(first, second);
            const wrapper = browser(topLevel());

            wrapper.vm.open(7);
            wrapper.vm.open(9);
            second.resolve(insideFolder([fileRow()]));
            await flushPromises();
            first.resolve(topLevel([folderRow({ id: 7 })]));
            await flushPromises();

            expect(wrapper.vm.folderId).toBe(9);
            expect(wrapper.vm.items.map((i) => i.id)).toEqual([21]);
            expect(window.location.search).toContain('fid=9');
            expect(wrapper.vm.loading).toBe(false);
        });

        it('stays loading until the latest load is in', async () => {
            const first = deferred();
            const second = deferred();
            queuedGets(first, second);
            const wrapper = browser(topLevel());

            wrapper.vm.open(7);
            wrapper.vm.open(9);
            first.resolve(topLevel([folderRow({ id: 7 })]));
            await flushPromises();

            expect(wrapper.vm.loading).toBe(true);
            expect(wrapper.vm.folderId).toBeNull();

            second.resolve(insideFolder([]));
            await flushPromises();
            expect(wrapper.vm.folderId).toBe(9);
        });

        it('ignores the failure of an overtaken load', async () => {
            const first = deferred();
            const second = deferred();
            queuedGets(first, second);
            const wrapper = browser(topLevel());

            wrapper.vm.open(7);
            wrapper.vm.open(9);
            second.resolve(insideFolder([]));
            await flushPromises();
            first.reject(new Error('gone'));
            await flushPromises();

            expect(globalThis.humhubStubs.logCalls.error).toHaveLength(0);
            expect(wrapper.vm.folderId).toBe(9);
        });

        it('does not revert a sort change made while a load was in flight', async () => {
            const first = deferred();
            const second = deferred();
            queuedGets(first, second);
            const wrapper = browser(topLevel());

            wrapper.vm.setView('tiles');
            wrapper.vm.onFilters({ sort: 'newest' });
            expect(globalThis.humhubStubs.client.get.mock.calls[1][0]).toContain('sort=newest');

            second.resolve(topLevel(undefined, { view: 'tiles', sort: 'newest' }));
            await flushPromises();
            first.resolve(topLevel(undefined, { view: 'tiles' }));
            await flushPromises();

            expect(wrapper.vm.sort).toBe('newest');
            expect(wrapper.vm.view).toBe('tiles');
        });

        // A reload while a folder is still opening reloads THAT folder, and still gets it
        // into the address bar.
        it('reloads the folder being opened, not the one it is leaving', async () => {
            const first = deferred();
            const second = deferred();
            queuedGets(first, second);
            const wrapper = browser(topLevel());

            wrapper.vm.open(11);
            wrapper.vm.onFilters({ sort: 'newest' });
            expect(globalThis.humhubStubs.client.get.mock.calls[1][0]).toContain('parent=11');

            second.resolve(insideFolder([], { folder: folderRow(), path: [{ id: 11, title: 'Entwürfe' }] }));
            await flushPromises();

            expect(wrapper.vm.folderId).toBe(11);
            expect(window.location.search).toContain('fid=11');
        });

        it('shows the folder popstate asked for, even while another is loading', async () => {
            const first = deferred();
            const second = deferred();
            queuedGets(first, second);
            const wrapper = browser(topLevel());

            wrapper.vm.open(11);
            window.history.replaceState({}, '', '/s/x/cfiles/browse/index?fid=0');
            window.dispatchEvent(new Event('popstate'));
            expect(globalThis.humhubStubs.client.get).toHaveBeenCalledTimes(2);

            second.resolve(topLevel());
            await flushPromises();
            first.resolve(insideFolder([], { folder: folderRow() }));
            await flushPromises();

            expect(wrapper.vm.folderId).toBeNull();
            expect(window.location.search).toContain('fid=0');
        });

        it('drops a further page that belongs to a level no longer open', async () => {
            const more = deferred();
            const opened = deferred();
            queuedGets(more, opened);
            const wrapper = browser(topLevel([folderRow()], { pages: 2 }));

            wrapper.vm.loadMore();
            wrapper.vm.open(9);
            opened.resolve(insideFolder([]));
            await flushPromises();
            more.resolve(topLevel([fileRow()], { page: 2, pages: 2 }));
            await flushPromises();

            expect(wrapper.vm.folderId).toBe(9);
            expect(wrapper.vm.items).toEqual([]);
            expect(wrapper.vm.page).toBe(1);
            expect(wrapper.vm.loadingMore).toBe(false);
        });
        it('leaves no further page loading when an open fails after it', async () => {
            const more = deferred();
            const opened = deferred();
            queuedGets(more, opened);
            const wrapper = browser(topLevel([folderRow()], { pages: 2 }));

            wrapper.vm.loadMore();
            wrapper.vm.open(9);
            opened.reject(new Error('gone'));
            await flushPromises();
            more.resolve(topLevel([fileRow()], { page: 2, pages: 2 }));
            await flushPromises();

            expect(wrapper.vm.loadingMore).toBe(false);
            expect(wrapper.vm.page).toBe(1);
        });

        // The reload replaces the rows the further page would be appended to.
        it('does not load a further page while the level reloads', async () => {
            globalThis.humhubStubs.client.get = vi.fn(pending);
            const wrapper = browser(topLevel([folderRow()], { pages: 2 }));

            wrapper.vm.reload();
            wrapper.vm.loadMore();

            expect(globalThis.humhubStubs.client.get).toHaveBeenCalledTimes(1);
            expect(wrapper.vm.loadingMore).toBe(false);
        });

        it('adds no history entry for the level the URL already names', async () => {
            window.history.replaceState({}, '', '/s/x/cfiles/browse/index?fid=0');
            const wrapper = browser(topLevel());
            const entries = window.history.length;

            wrapper.vm.open(null);
            await flushPromises();

            expect(window.history.length).toBe(entries);
        });
    });

    describe('selection and bulk actions', () => {
        it('collects the selected rows and moves them to the top level', async () => {
            const wrapper = browser(insideFolder([fileRow({ parentFolderId: 9 })]));

            await wrapper.find('.cfiles-row input[type="checkbox"]').trigger('click');
            expect(wrapper.vm.selection).toEqual(['file:21']);

            wrapper.vm.moveTo(null, wrapper.vm.selectedItems);
            await flushPromises();

            const [url, cfg] = globalThis.humhubStubs.client.post.mock.calls[0];
            expect(url).toContain('cfiles/items/move');
            expect(cfg.data).toEqual({
                containerId: 5,
                items: [{ type: 'file', id: 21 }],
                targetFolderId: null,
            });
        });

        // Moving something to where it already is costs nothing and must not round-trip.
        it('does not move anything to the level it is already on', async () => {
            const wrapper = browser(topLevel());

            wrapper.vm.moveTo(null, [fileRow()]);
            await flushPromises();

            expect(globalThis.humhubStubs.client.post).not.toHaveBeenCalled();
        });

        it('deletes the selection once confirmed', async () => {
            globalThis.humhubStubs.modal.confirm = vi.fn(() => Promise.resolve(true));
            const wrapper = browser(topLevel());

            wrapper.vm.confirmDelete([fileRow()]);
            await flushPromises();

            const [url, cfg] = globalThis.humhubStubs.client.post.mock.calls[0];
            expect(url).toContain('cfiles/items/delete');
            expect(cfg.data).toEqual({ items: [{ type: 'file', id: 21 }] });
        });

        it('deletes nothing when the confirmation is declined', async () => {
            globalThis.humhubStubs.modal.confirm = vi.fn(() => Promise.resolve(false));
            const wrapper = browser(topLevel());

            wrapper.vm.confirmDelete([fileRow()]);
            await flushPromises();

            expect(globalThis.humhubStubs.client.post).not.toHaveBeenCalled();
        });
    });

    describe('edit deep link', () => {
        // A stream entry's Edit control links here instead of loading a form of its own.
        it('opens the dialog for the item the link names', () => {
            const wrapper = browser(topLevel(), { editKey: 'file:21' });

            expect(wrapper.vm.showEdit).toBe(true);
            expect(wrapper.vm.editItem.title).toBe('Angebot.pdf');
        });

        it('opens nothing when the link names something that is not on this page', () => {
            const wrapper = browser(topLevel(), { editKey: 'file:999' });

            expect(wrapper.vm.showEdit).toBe(false);
        });

        it('opens nothing without a link', () => {
            expect(browser(topLevel()).vm.showEdit).toBe(false);
        });
    });

    /**
     * `UiModal` teleports its dialog onto the real `document.body`, so the field is looked up
     * there rather than inside the wrapper — and these wait for the modal's `opened`, because
     * the dialog focuses itself first (that is what makes Escape and the tab ring work).
     */
    describe('dialog focus', () => {
        // Asserted on the focused element itself rather than by looking the field up: modals
        // are teleported onto the shared document.body, where earlier tests leave theirs.
        it('puts the cursor in the title field when the create dialog opens', async () => {
            const wrapper = browser(topLevel());

            wrapper.vm.showCreate = true;
            await flushPromises();

            expect(document.activeElement.getAttribute('name')).toBe('title');
            expect(document.activeElement.value).toBe('');
        });

        it('does the same for the rename dialog', async () => {
            browser(topLevel(), { editKey: 'file:21' });

            await flushPromises();

            expect(document.activeElement.getAttribute('name')).toBe('title');
            expect(document.activeElement.value).toBe('Angebot.pdf');
        });
    });

    // The row menu's own entries are defined by the browser, not the row: the browser is what
    // knows how to open, edit, move and delete an item.
    // Went through a hand-rolled XMLHttpRequest once, which silently failed every upload: the
    // CSRF token comes from Yii's ajax prefilter on the platform client, and there is no
    // csrf-token meta tag on a HumHub page to read it from.
    describe('upload', () => {
        it('posts the whole batch through the platform client', async () => {
            const wrapper = browser(topLevel());
            const files = [new File(['a'], 'a.txt'), new File(['b'], 'b.txt')];

            wrapper.vm.upload(files);
            await flushPromises();

            const [url, cfg] = globalThis.humhubStubs.client.post.mock.calls[0];
            expect(url).toContain('cfiles/5/files');
            expect(cfg.data).toBeInstanceOf(FormData);
            expect(cfg.data.getAll('files[]')).toHaveLength(2);
            // Without these jQuery serializes the body and the multipart boundary is lost.
            expect(cfg.processData).toBe(false);
            expect(cfg.contentType).toBe(false);
        });

        it('tells the endpoint which level to upload to', async () => {
            const wrapper = browser(insideFolder([]));

            wrapper.vm.upload([new File(['a'], 'a.txt')]);
            await flushPromises();

            expect(globalThis.humhubStubs.client.post.mock.calls[0][1].data.get('parent')).toBe('9');
        });

        it('sends no parent at the top level, where there is none', async () => {
            const wrapper = browser(topLevel());

            wrapper.vm.upload([new File(['a'], 'a.txt')]);
            await flushPromises();

            expect(globalThis.humhubStubs.client.post.mock.calls[0][1].data.get('parent')).toBeNull();
        });

        it('uploads nothing when the caller may not write', async () => {
            const wrapper = browser(topLevel(), { canWrite: false });

            wrapper.vm.upload([new File(['a'], 'a.txt')]);
            await flushPromises();

            expect(globalThis.humhubStubs.client.post).not.toHaveBeenCalled();
        });

        it('picks files through the add menu', async () => {
            const wrapper = browser(topLevel());
            const click = vi.spyOn(wrapper.vm.$refs.fileInput, 'click').mockImplementation(() => {});

            await wrapper.findAll('.cfiles-add-menu__entry')[0].trigger('click');

            expect(click).toHaveBeenCalledTimes(1);
        });

        it('opens the folder dialog through the add menu', async () => {
            const wrapper = browser(topLevel());

            await wrapper.findAll('.cfiles-add-menu__entry')[1].trigger('click');

            expect(wrapper.vm.showCreate).toBe(true);
        });

        it('offers no add menu to a reader who may not write', () => {
            expect(browser(topLevel(), { canWrite: false }).find('.cfiles-add-menu').exists()).toBe(false);
        });

        it('shows a placeholder row per file while the batch is on its way', async () => {
            globalThis.humhubStubs.client.post = vi.fn(pending);
            const wrapper = browser(topLevel([]));

            wrapper.vm.upload([new File(['a'], 'a.txt'), new File(['b'], 'b.txt')]);
            await flushPromises();

            const rows = wrapper.findAll('.cfiles-row.is-uploading');
            expect(rows.map((r) => r.find('h4').text())).toEqual(['a.txt', 'b.txt']);
            expect(rows[0].find('.progress-bar').attributes('aria-valuenow')).toBe('0');
        });

        it('removes the placeholders once the batch is in', async () => {
            let finish;
            globalThis.humhubStubs.client.post = vi.fn(() => new Promise((resolve) => { finish = resolve; }));
            const wrapper = browser(topLevel([]));

            wrapper.vm.upload([new File(['a'], 'a.txt')]);
            await flushPromises();
            finish({ results: [], errors: [] });
            await flushPromises();

            expect(wrapper.findAll('.is-uploading')).toHaveLength(0);
        });

        it('keeps the placeholders with the level they upload into', async () => {
            globalThis.humhubStubs.client.post = vi.fn(pending);
            const wrapper = browser(topLevel([]));

            wrapper.vm.upload([new File(['a'], 'a.txt')]);
            globalThis.humhubStubs.client.get = vi.fn(() => Promise.resolve(insideFolder([])));
            wrapper.vm.open(9);
            await flushPromises();
            expect(wrapper.findAll('.is-uploading')).toHaveLength(0);

            globalThis.humhubStubs.client.get = vi.fn(() => Promise.resolve(topLevel([])));
            wrapper.vm.open(null);
            await flushPromises();
            expect(wrapper.findAll('.cfiles-row.is-uploading')).toHaveLength(1);
        });

        // Placeholder gone before the new rows are in would flash the level empty.
        it('keeps the placeholders until the reloaded level is in', async () => {
            const reloaded = deferred();
            queuedGets(reloaded);
            const wrapper = browser(topLevel([]));

            wrapper.vm.upload([new File(['a'], 'a.txt')]);
            await flushPromises();
            expect(wrapper.findAll('.cfiles-row.is-uploading')).toHaveLength(1);

            reloaded.resolve(topLevel([fileRow()]));
            await flushPromises();
            expect(wrapper.findAll('.cfiles-row.is-uploading')).toHaveLength(0);
            expect(wrapper.findAll('.cfiles-row')).toHaveLength(1);
        });

        it('does not reload a level the upload did not go to', async () => {
            const upload = deferred();
            globalThis.humhubStubs.client.post = vi.fn(() => upload.promise);
            const wrapper = browser(topLevel([]));

            wrapper.vm.upload([new File(['a'], 'a.txt')]);
            globalThis.humhubStubs.client.get = vi.fn(() => Promise.resolve(insideFolder([])));
            wrapper.vm.open(9);
            await flushPromises();
            upload.resolve({ results: [{ id: 1 }], errors: [] });
            await flushPromises();

            expect(globalThis.humhubStubs.client.get).toHaveBeenCalledTimes(1);
        });

        it('reports an upload into a level that is not open, which shows nothing of it', async () => {
            const reported = statusMessages();
            globalThis.humhubStubs.client.post = vi.fn(() => Promise.resolve({ results: [{ id: 1 }, { id: 2 }], errors: [] }));
            const wrapper = browser(insideFolder([]));

            await wrapper.findAll('li.c-path-bar__crumb')[1].trigger('drop', fileDrag([new File(['a'], 'a.txt'), new File(['b'], 'b.txt')]));
            await flushPromises();

            expect(reported.at(-1).level).toBe('success');
            expect(reported.at(-1).message).toBe('2 files uploaded to test123');
            // Nothing of it is on this level.
            expect(globalThis.humhubStubs.client.get).not.toHaveBeenCalled();

            globalThis.humhub.modules.vue.setStatusHandler(null);
        });

        // Captured when the upload starts: by the time it is in, another level may be open.
        it('names the folder an upload into the open level went to once it is left', async () => {
            const reported = statusMessages();
            const upload = deferred();
            globalThis.humhubStubs.client.post = vi.fn(() => upload.promise);
            const wrapper = browser(insideFolder([]));

            wrapper.vm.upload([new File(['a'], 'a.txt')]);
            globalThis.humhubStubs.client.get = vi.fn(() => Promise.resolve(topLevel([])));
            wrapper.vm.open(null);
            await flushPromises();
            upload.resolve({ results: [{ id: 1 }], errors: [] });
            await flushPromises();

            expect(reported.at(-1).message).toBe('1 file uploaded to sgadgasdg');
            globalThis.humhub.modules.vue.setStatusHandler(null);
        });

        it('reports nothing for an upload into the open level', async () => {
            const reported = statusMessages();
            const wrapper = browser(topLevel([]));

            wrapper.vm.upload([new File(['a'], 'a.txt')]);
            await flushPromises();

            expect(reported.filter((entry) => entry.level === 'success')).toHaveLength(0);
            globalThis.humhub.modules.vue.setStatusHandler(null);
        });

        it('reports a per-file rejection rather than logging a transport error', async () => {
            // The bridge queues status messages until a handler is set; the StatusBar island
            // is what sets one in production.
            const reported = [];
            globalThis.humhub.modules.vue.setStatusHandler((entry) => reported.push(entry));

            globalThis.humhubStubs.client.post = vi.fn(() => Promise.reject({
                status: 422,
                errors: [{ fileName: 'huge.iso', messages: ['File is too big'] }],
            }));
            const wrapper = browser(topLevel());

            wrapper.vm.upload([new File(['a'], 'huge.iso')]);
            await flushPromises();

            expect(globalThis.humhubStubs.logCalls.error).toHaveLength(0);
            expect(reported.at(-1).message).toContain('huge.iso');
            expect(reported.at(-1).message).toContain('File is too big');

            globalThis.humhub.modules.vue.setStatusHandler(null);
        });
    });

    describe('display', () => {
        it('takes the display from the embedded payload', () => {
            const list = browser(topLevel());
            expect(list.findAll('.cfiles-row')).toHaveLength(2);
            expect(list.find('.c-tile-grid').exists()).toBe(false);

            const tiles = browser(topLevel([fileRow()], { view: 'tiles' }));
            expect(tiles.findAll('.c-tile-grid__tile')).toHaveLength(1);
            expect(tiles.find('.cfiles-row').exists()).toBe(false);
        });

        it('switches to tiles through the view switch', async () => {
            const wrapper = browser(topLevel());
            globalThis.humhubStubs.client.get = vi.fn(() => Promise.resolve(topLevel(undefined, { view: 'tiles' })));

            await wrapper.find('.c-view-switch button[aria-label="Tiles"]').trigger('click');
            await flushPromises();

            expect(globalThis.humhubStubs.client.get.mock.calls[0][0]).toContain('pageSize=96');
            expect(wrapper.findAll('.c-tile-grid__tile')).toHaveLength(2);
            expect(wrapper.find('.cfiles-row').exists()).toBe(false);
            expect(wrapper.find('.c-view-switch button[aria-label="Tiles"]').attributes('aria-pressed')).toBe('true');
        });

        // A tile grid asks for a bigger page than a row list; the view itself is not a
        // parameter of the list.
        it('reloads with the page size of the new display when it is switched', async () => {
            const wrapper = browser(topLevel());
            globalThis.humhubStubs.client.get = vi.fn(() => Promise.resolve(topLevel([], { view: 'tiles' })));

            wrapper.vm.setView('tiles');
            await flushPromises();

            const url = globalThis.humhubStubs.client.get.mock.calls[0][0];
            expect(url).toContain('pageSize=96');
            expect(url).not.toContain('view=');
            expect(wrapper.vm.view).toBe('tiles');
        });

        it('remembers the display through the preferences endpoint', async () => {
            const wrapper = browser(topLevel());

            wrapper.vm.setView('tiles');
            await flushPromises();

            expect(globalThis.humhubStubs.client.ajax).toHaveBeenCalledTimes(1);
            const [url, cfg] = globalThis.humhubStubs.client.ajax.mock.calls[0];
            expect(url).toContain('cfiles/preferences');
            expect(cfg.method).toBe('PATCH');
            expect(cfg.data).toEqual({ view: 'tiles' });
        });

        // The preference is a convenience: failing to store it only gets logged, the display
        // switches all the same.
        it('switches even when the preference cannot be stored', async () => {
            globalThis.humhubStubs.client.ajax = vi.fn(() => Promise.reject(new Error('offline')));
            globalThis.humhubStubs.client.get = vi.fn(() => Promise.resolve(topLevel([], { view: 'list' })));
            const wrapper = browser(topLevel());

            wrapper.vm.setView('tiles');
            await flushPromises();

            expect(globalThis.humhubStubs.logCalls.error).toHaveLength(1);
            // The payload's `view` is the stored one — possibly not yet the new one.
            expect(wrapper.vm.view).toBe('tiles');
        });

        it('asks for the page size of the display on every load', async () => {
            const wrapper = browser(topLevel([folderRow()], { pages: 2 }));
            globalThis.humhubStubs.client.get = vi.fn(() => Promise.resolve(topLevel([fileRow()], { page: 2, pages: 2 })));

            wrapper.vm.loadMore();
            await flushPromises();

            const url = globalThis.humhubStubs.client.get.mock.calls[0][0];
            expect(url).toContain('page=2');
            expect(url).toContain('pageSize=50');
        });

        it('does not reload when the display did not change', async () => {
            const wrapper = browser(topLevel());

            wrapper.vm.setView('list');
            await flushPromises();

            expect(globalThis.humhubStubs.client.get).not.toHaveBeenCalled();
        });

        it('keeps the display across folder navigation', async () => {
            const wrapper = browser(topLevel([fileRow()], { view: 'tiles' }));
            globalThis.humhubStubs.client.get = vi.fn(() => Promise.resolve(insideFolder([], { view: 'tiles' })));

            wrapper.vm.open(11);
            await flushPromises();

            expect(globalThis.humhubStubs.client.get.mock.calls[0][0]).toContain('pageSize=96');
        });
    });

    describe('select all', () => {
        const menuEntry = (wrapper, label) => wrapper.findAll('.c-selection-menu .dropdown-item')
            .find((entry) => entry.text() === label);

        it('offers the selection menu only while something is selected', async () => {
            const wrapper = browser(topLevel());

            expect(wrapper.find('.c-selection-menu').exists()).toBe(false);

            await wrapper.find('.cfiles-row input[type="checkbox"]').trigger('click');

            expect(wrapper.findAll('.c-selection-menu .dropdown-item').map((e) => e.text()))
                .toEqual(['Select all', 'Move', 'Delete', 'Clear selection']);
        });

        it('selects every loaded item from the selection menu', async () => {
            const wrapper = browser(topLevel());

            await wrapper.find('.cfiles-row input[type="checkbox"]').trigger('click');
            await menuEntry(wrapper, 'Select all').trigger('click');

            expect(wrapper.vm.selection).toEqual(['folder:11', 'file:21']);
        });

        it('clears the selection from the selection menu', async () => {
            const wrapper = browser(topLevel());

            await wrapper.find('.cfiles-row input[type="checkbox"]').trigger('click');
            await menuEntry(wrapper, 'Clear selection').trigger('click');

            expect(wrapper.vm.selection).toEqual([]);
            expect(wrapper.find('.c-selection-menu').exists()).toBe(false);
        });

        it('moves and deletes the selection from the selection menu', async () => {
            globalThis.humhubStubs.modal.confirm = vi.fn(() => Promise.resolve(false));
            const wrapper = browser(topLevel());

            await wrapper.findAll('.cfiles-row input[type="checkbox"]')[1].trigger('click');
            await menuEntry(wrapper, 'Move').trigger('click');
            expect(wrapper.vm.showMove).toBe(true);
            expect(wrapper.vm.moveItemsList.map((i) => i.id)).toEqual([21]);

            await menuEntry(wrapper, 'Delete').trigger('click');
            expect(globalThis.humhubStubs.modal.confirm).toHaveBeenCalledTimes(1);
        });

        // Covers what is loaded, not what exists: arming the delete button with rows the
        // reader has never seen is the failure mode worth designing against.
        it('covers only the loaded page, not the whole folder', async () => {
            const wrapper = browser(topLevel([folderRow()], { total: 120, pages: 3 }));

            wrapper.vm.selectAll();
            await wrapper.vm.$nextTick();

            expect(wrapper.vm.selection).toHaveLength(1);
        });

        it('clears the selection with Escape', async () => {
            const wrapper = browser(topLevel());

            await wrapper.find('.cfiles-row input[type="checkbox"]').trigger('click');
            document.dispatchEvent(new KeyboardEvent('keydown', { key: 'Escape' }));
            await wrapper.vm.$nextTick();

            expect(wrapper.vm.selection).toEqual([]);
        });

        // Escape there closes the dialog; the selection the dialog acts on stays.
        it('leaves the selection alone while a dialog takes the Escape', async () => {
            const wrapper = browser(topLevel());

            await wrapper.find('.cfiles-row input[type="checkbox"]').trigger('click');
            wrapper.vm.showMove = true;
            document.dispatchEvent(new KeyboardEvent('keydown', { key: 'Escape' }));

            expect(wrapper.vm.selection).toEqual(['folder:11']);
        });
    });

    describe('range selection', () => {
        const boxes = (wrapper) => wrapper.findAll('.cfiles-row input[type="checkbox"]');

        it('selects everything between the last box clicked and a Shift-clicked one', async () => {
            const wrapper = browser(topLevel(three()));

            await boxes(wrapper)[0].trigger('click');
            await boxes(wrapper)[2].trigger('click', { shiftKey: true });

            expect(wrapper.vm.selection).toEqual(['folder:11', 'file:21', 'file:22']);
        });

        it('works upwards too, in list order', async () => {
            const wrapper = browser(topLevel(three()));

            await boxes(wrapper)[2].trigger('click');
            await boxes(wrapper)[0].trigger('click', { shiftKey: true });

            expect(wrapper.vm.selection).toEqual(['folder:11', 'file:21', 'file:22']);
        });

        it('selects just the one item on a Shift-click without an earlier click', async () => {
            const wrapper = browser(topLevel(three()));

            await boxes(wrapper)[2].trigger('click', { shiftKey: true });

            expect(wrapper.vm.selection).toEqual(['file:22']);
        });

        it('ranges the same way in the tiles', async () => {
            const wrapper = browser(topLevel(three(), { view: 'tiles' }));
            const checks = wrapper.findAll('.c-tile-grid__check-input');

            await checks[0].trigger('click');
            await checks[2].trigger('click', { shiftKey: true });

            expect(wrapper.vm.selection).toEqual(['folder:11', 'file:21', 'file:22']);
        });
    });

    describe('drag and drop', () => {
        it('uploads desktop files dropped on a folder into that folder', async () => {
            const wrapper = browser(topLevel(undefined, { view: 'tiles' }));

            await tile(wrapper, 'folder:11').trigger('dragover', fileDrag());
            await tile(wrapper, 'folder:11').trigger('drop', fileDrag());
            await flushPromises();

            expect(globalThis.humhubStubs.client.post).toHaveBeenCalledTimes(1);
            const [url, cfg] = globalThis.humhubStubs.client.post.mock.calls[0];
            expect(url).toContain('/5/files');
            expect(cfg.data.get('parent')).toBe('11');
        });

        it('shows the progress on the folder being uploaded into', async () => {
            globalThis.humhubStubs.client.post = vi.fn(pending);
            const wrapper = browser(topLevel(undefined, { view: 'tiles' }));

            await tile(wrapper, 'folder:11').trigger('drop', fileDrag());
            await flushPromises();

            expect(tile(wrapper, 'folder:11').classes()).toContain('is-uploading');
            // Into another folder: no placeholder among this level's tiles.
            expect(wrapper.findAll('.c-tile-grid__tile')).toHaveLength(2);
        });

        it('reloads after an upload into a folder shown here, and names it', async () => {
            const reported = statusMessages();
            globalThis.humhubStubs.client.post = vi.fn(() => Promise.resolve({ results: [{ id: 1 }], errors: [] }));
            const wrapper = browser(topLevel(undefined, { view: 'tiles' }));

            await tile(wrapper, 'folder:11').trigger('drop', fileDrag());
            await flushPromises();

            // Its item count changed.
            expect(globalThis.humhubStubs.client.get).toHaveBeenCalledTimes(1);
            expect(reported.at(-1).message).toBe('1 file uploaded to Entwürfe');
            globalThis.humhub.modules.vue.setStatusHandler(null);
        });

        it('keeps a folder uploading until the last of its batches is in', async () => {
            const first = deferred();
            const second = deferred();
            const posts = [first, second];
            globalThis.humhubStubs.client.post = vi.fn(() => posts.shift().promise);
            globalThis.humhubStubs.client.get = vi.fn(() => Promise.resolve(topLevel(undefined, { view: 'tiles' })));
            const wrapper = browser(topLevel(undefined, { view: 'tiles' }));

            await tile(wrapper, 'folder:11').trigger('drop', fileDrag());
            await tile(wrapper, 'folder:11').trigger('drop', fileDrag());
            first.resolve({ results: [{ id: 1 }], errors: [] });
            await flushPromises();
            expect(tile(wrapper, 'folder:11').classes()).toContain('is-uploading');

            second.resolve({ results: [{ id: 2 }], errors: [] });
            await flushPromises();
            expect(tile(wrapper, 'folder:11').classes()).not.toContain('is-uploading');
        });

        it('reloads when a dragged move fails, so nothing looks moved that was not', async () => {
            globalThis.humhubStubs.client.post = vi.fn(() => Promise.reject({ errors: [{ message: 'No.' }] }));
            const wrapper = browser(topLevel(undefined, { view: 'tiles' }));

            await tile(wrapper, 'file:21').trigger('dragstart', itemDrag());
            await tile(wrapper, 'folder:11').trigger('drop', itemDrag());
            await flushPromises();

            expect(globalThis.humhubStubs.logCalls.error).toHaveLength(1);
            expect(globalThis.humhubStubs.client.get).toHaveBeenCalledTimes(1);
        });

        it('uploads files dropped anywhere else in the card into the open folder', async () => {
            globalThis.humhubStubs.client.post = vi.fn(pending);
            const wrapper = browser(insideFolder([fileRow()], { view: 'tiles' }));

            await wrapper.find('.c-drop-zone').trigger('drop', fileDrag());
            await flushPromises();

            expect(globalThis.humhubStubs.client.post.mock.calls[0][1].data.get('parent')).toBe('9');
            const placeholder = wrapper.find('.c-tile-grid__tile.is-uploading');
            expect(placeholder.exists()).toBe(true);
            expect(placeholder.text()).toContain('a.txt');
        });

        it('moves the whole selection when a selected item is dragged onto a folder', async () => {
            globalThis.humhubStubs.client.get = vi.fn(() => Promise.resolve(topLevel(three(), { view: 'tiles' })));
            const wrapper = browser(topLevel(three(), { view: 'tiles' }));
            const checks = wrapper.findAll('.c-tile-grid__check-input');
            await checks[1].trigger('click');
            await checks[2].trigger('click');

            await tile(wrapper, 'file:21').trigger('dragstart', itemDrag());
            await tile(wrapper, 'folder:11').trigger('dragover', itemDrag());
            expect(tile(wrapper, 'folder:11').classes()).toContain('is-drop-target');
            await tile(wrapper, 'folder:11').trigger('drop', itemDrag());

            // The drop itself ends the drag state: no drag-leave follows a drop (the dragend
            // that does is harmless, `onDragEnd` is idempotent).
            expect(wrapper.vm.dragged).toEqual([]);
            expect(tile(wrapper, 'folder:11').classes()).not.toContain('is-drop-target');

            await flushPromises();
            const [url, cfg] = globalThis.humhubStubs.client.post.mock.calls[0];
            expect(url).toContain('cfiles/items/move');
            expect(cfg.data).toEqual({
                containerId: 5,
                items: [{ type: 'file', id: 21 }, { type: 'file', id: 22 }],
                targetFolderId: 11,
            });
        });

        it('moves only the dragged item when it is not part of the selection', async () => {
            const wrapper = browser(topLevel(three()));
            await wrapper.findAll('.cfiles-row input[type="checkbox"]')[2].trigger('click');

            const rows = wrapper.findAll('.cfiles-row');
            await rows[1].trigger('dragstart', itemDrag());
            await rows[0].trigger('drop', itemDrag());
            await flushPromises();

            expect(globalThis.humhubStubs.client.post.mock.calls[0][1].data.items).toEqual([{ type: 'file', id: 21 }]);
        });

        it('moves items dropped on the root crumb to the top level', async () => {
            const wrapper = browser(insideFolder([fileRow({ parentFolderId: 9 })]));

            await wrapper.find('.cfiles-row').trigger('dragstart', itemDrag());
            const root = wrapper.findAll('li.c-path-bar__crumb')[0];
            await root.trigger('dragover', itemDrag());
            expect(root.classes()).toContain('is-drop-target');
            await root.trigger('drop', itemDrag());
            await flushPromises();

            const [url, cfg] = globalThis.humhubStubs.client.post.mock.calls[0];
            expect(url).toContain('cfiles/items/move');
            expect(cfg.data.targetFolderId).toBeNull();
            expect(cfg.data.items).toEqual([{ type: 'file', id: 21 }]);
            expect(wrapper.findAll('li.c-path-bar__crumb')[0].classes()).not.toContain('is-drop-target');
        });

        it('uploads files dropped on an ancestor crumb into that folder', async () => {
            const wrapper = browser(insideFolder([]));

            await wrapper.findAll('li.c-path-bar__crumb')[1].trigger('drop', fileDrag());
            await flushPromises();

            expect(globalThis.humhubStubs.client.post.mock.calls[0][1].data.get('parent')).toBe('7');
        });

        it('never offers a folder as a drop target for itself', async () => {
            const wrapper = browser(topLevel(undefined, { view: 'tiles' }));

            await tile(wrapper, 'folder:11').trigger('dragstart', itemDrag());

            expect(wrapper.vm.canDropOnItem(folderRow(), itemDrag())).toBe(false);
            await tile(wrapper, 'folder:11').trigger('drop', itemDrag());
            expect(globalThis.humhubStubs.client.post).not.toHaveBeenCalled();
        });

        it('offers files as drop targets for nothing', async () => {
            const wrapper = browser(topLevel());
            await wrapper.findAll('.cfiles-row')[0].trigger('dragstart', itemDrag());

            expect(wrapper.vm.canDropOnItem(fileRow(), itemDrag())).toBe(false);
            expect(wrapper.vm.canDropOnItem(fileRow(), fileDrag())).toBe(false);
        });

        it('forgets the drag when it ends without a drop', async () => {
            const wrapper = browser(topLevel(undefined, { view: 'tiles' }));

            await tile(wrapper, 'file:21').trigger('dragstart', itemDrag());
            await tile(wrapper, 'folder:11').trigger('dragover', itemDrag());
            await tile(wrapper, 'file:21').trigger('dragend', itemDrag());

            expect(wrapper.vm.dragged).toEqual([]);
            expect(tile(wrapper, 'folder:11').classes()).not.toContain('is-drop-target');
            // A text drag from elsewhere is not a move of what was dragged before.
            expect(wrapper.vm.canDropOnItem(folderRow(), itemDrag())).toBe(false);
        });

        it('takes no drop anywhere from a reader who may not write', async () => {
            const wrapper = browser(topLevel(undefined, { view: 'tiles' }), { canWrite: false });

            expect(wrapper.vm.canDropOnItem(folderRow(), fileDrag())).toBe(false);
            await tile(wrapper, 'folder:11').trigger('drop', fileDrag());
            await wrapper.find('.c-drop-zone').trigger('drop', fileDrag());
            await flushPromises();
            expect(globalThis.humhubStubs.client.post).not.toHaveBeenCalled();

            await wrapper.find('.c-drop-zone').trigger('dragenter', fileDrag());
            expect(wrapper.find('.c-drop-zone__overlay.is-refused').exists()).toBe(true);
        });
    });

    describe('toolbar', () => {
        it('offers the settings only when the page hands a link', () => {
            const withLink = browser(topLevel(), { settingsUrl: '/s/x/cfiles/config-container' });
            expect(withLink.find('.c-page-toolbar a[href="/s/x/cfiles/config-container"] .ti-settings').exists()).toBe(true);

            expect(browser(topLevel()).find('.c-page-toolbar .ti-settings').exists()).toBe(false);
        });

        it('titles the page', () => {
            expect(browser(topLevel()).find('.c-page-toolbar__title').text()).toBe('Files');
        });
    });

    describe('sort', () => {
        const choose = async (wrapper, label) => {
            await wrapper.find('.form-search-filter-sort .c-select__button').trigger('click');
            await wrapper.findAll('[role="option"]').find((o) => o.text() === label).trigger('click');
            await flushPromises();
        };

        it('reloads with the sort key the select chose', async () => {
            const wrapper = browser(topLevel());

            await choose(wrapper, 'Newest first');

            const url = globalThis.humhubStubs.client.get.mock.calls[0][0];
            expect(url).toContain('sort=newest');
            expect(url).not.toContain('order=');
        });

        it('shows the stored sort as the chosen option', () => {
            const wrapper = browser(topLevel(undefined, { sort: 'newest' }));

            expect(wrapper.find('.form-search-filter-sort .c-select__value').text()).toBe('Newest first');
        });

        it('shows the default order as the select\'s label', () => {
            const wrapper = browser(topLevel(undefined, { sort: 'default' }));

            expect(wrapper.vm.filterValues.sort).toBe('');
            expect(wrapper.find('.form-search-filter-sort .c-select').classes()).not.toContain('has-selection');
            expect(wrapper.find('.form-search-filter-sort .c-select__value').text()).toBe('Sort by');
        });

        // Without a sort the server would re-apply the stored one, and clearing the select
        // would do nothing: `default` is the module's order, and forgets the stored one.
        it('asks for the default order explicitly when the select is cleared', async () => {
            const wrapper = browser(topLevel(undefined, { sort: 'newest' }));

            await wrapper.find('.form-search-filter-sort .c-select__clear').trigger('click');
            await flushPromises();

            const url = globalThis.humhubStubs.client.get.mock.calls[0][0];
            expect(url).toContain('sort=default');
        });

        it('keeps the sort across folder navigation', async () => {
            const wrapper = browser(topLevel(undefined, { sort: 'largest' }));

            wrapper.vm.open(11);
            await flushPromises();

            expect(globalThis.humhubStubs.client.get.mock.calls[0][0]).toContain('sort=largest');
        });

        it('does not ask the server on mount', () => {
            browser(topLevel(undefined, { sort: 'newest' }));

            expect(globalThis.humhubStubs.client.get).not.toHaveBeenCalled();
        });
    });

    // The filters search the open folder and all its subfolders (FolderList's results mode).
    describe('filters and results', () => {
        const bar = (wrapper) => wrapper.findComponent(FilterBar).vm;
        const lastUrl = () => globalThis.humhubStubs.client.get.mock.calls.at(-1)[0];
        const inBrand = (over = {}) => fileRow({
            id: 31, contentId: 301, recordId: 1301, title: 'logo.png', parentFolderId: 8,
            path: [{ id: 7, title: 'Brand' }, { id: 8, title: 'Logos' }],
            ...over,
        });

        it('loads with the filters the bar applies', async () => {
            const wrapper = browser(topLevel());

            bar(wrapper).setFilter('type', 'image');
            await flushPromises();

            expect(lastUrl()).toContain('type=image');
            expect(lastUrl()).not.toContain('q=');

            bar(wrapper).setFilter('q', 'logo');
            await flushPromises();

            expect(lastUrl()).toContain('type=image');
            expect(lastUrl()).toContain('q=logo');
        });

        it('mirrors the filters and the sort into the page URL, next to the folder', async () => {
            window.history.replaceState({}, '', '/s/x/cfiles/browse/index?fid=9');
            const wrapper = browser(insideFolder([]));
            // The server reports the sort it was asked for.
            globalThis.humhubStubs.client.get = vi.fn((url) => Promise.resolve(results(insideFolder([]), {
                sort: new URL(url, 'http://localhost').searchParams.get('sort') || 'default',
            })));

            bar(wrapper).setFilter('modified', '30d');
            bar(wrapper).setFilter('sort', 'newest');
            await flushPromises();

            const params = new URLSearchParams(window.location.search);
            expect(params.get('fid')).toBe('9');
            expect(params.get('modified')).toBe('30d');
            expect(params.get('sort')).toBe('newest');
            expect(params.has('q')).toBe(false);
        });

        // The page was built with the URL's filters (BrowseController): the bar's first look
        // at the URL has nothing to apply.
        it('asks nothing on mount when the page came with the filters of its URL', async () => {
            window.history.replaceState({}, '', '/s/x/cfiles/browse/index?fid=0&q=logo&type=image&sort=newest');

            const wrapper = browser(results(topLevel([inBrand()]), { sort: 'newest' }), {
                initialFilters: { q: 'logo', userId: '', type: 'image', modified: '' },
            });
            await flushPromises();

            expect(globalThis.humhubStubs.client.get).not.toHaveBeenCalled();
            expect(wrapper.find('.form-search-filter-q input').element.value).toBe('logo');
        });

        it('says how many results there are and where they were searched', () => {
            const top = browser(results(topLevel([inBrand(), fileRow()])));
            expect(top.find('.cfiles-results').text()).toBe('2 results in all files');

            const inside = browser(results(insideFolder([inBrand()])));
            expect(inside.find('.cfiles-results').text()).toBe('1 result in this folder and its subfolders');
        });

        it('says nothing about results while it lists a level', () => {
            expect(browser(topLevel()).find('.cfiles-results').exists()).toBe(false);
        });

        it('places each hit and opens its folder, filters and all', async () => {
            window.history.replaceState({}, '', '/s/x/cfiles/browse/index?fid=0&q=logo');
            const wrapper = browser(results(topLevel([inBrand(), fileRow()])), {
                initialFilters: { q: 'logo', userId: '', type: '', modified: '' },
            });
            const locations = wrapper.findAll('.cfiles-location');

            // Only a hit that is not directly in the open folder has a location.
            expect(locations).toHaveLength(1);
            expect(locations[0].text()).toBe('in Brand › Logos');
            expect(locations[0].attributes('href')).toBe('/s/x/cfiles/browse/index?fid=8&q=logo');

            globalThis.humhubStubs.client.get = vi.fn(() => Promise.resolve(results(insideFolder([inBrand({ path: [] })]))));
            await locations[0].trigger('click');
            await flushPromises();

            expect(lastUrl()).toContain('parent=8');
            expect(lastUrl()).toContain('q=logo');
            const params = new URLSearchParams(window.location.search);
            expect(params.get('fid')).toBe('8');
            expect(params.get('q')).toBe('logo');
        });

        it('keeps the filters when a folder hit is opened', async () => {
            window.history.replaceState({}, '', '/s/x/cfiles/browse/index?fid=0&type=image');
            const wrapper = browser(results(topLevel([folderRow()])), {
                initialFilters: { q: '', userId: '', type: 'image', modified: '' },
            });

            await wrapper.find('.cfiles-row h4 a').trigger('click');
            await flushPromises();

            expect(lastUrl()).toContain('parent=11');
            expect(lastUrl()).toContain('type=image');
            expect(window.location.search).toContain('type=image');
        });

        it('searches the whole container from the root of the path', async () => {
            window.history.replaceState({}, '', '/s/x/cfiles/browse/index?fid=9&q=logo');
            const wrapper = browser(results(insideFolder([])), {
                initialFilters: { q: 'logo', userId: '', type: '', modified: '' },
            });

            await wrapper.find('a.c-path-bar__root').trigger('click');
            await flushPromises();

            expect(lastUrl()).not.toContain('parent=');
            expect(lastUrl()).toContain('q=logo');
        });

        it('offers to reset the filters when nothing matches', async () => {
            window.history.replaceState({}, '', '/s/x/cfiles/browse/index?fid=9&q=nothing&sort=newest');
            const wrapper = browser(results(insideFolder([]), { sort: 'newest' }), {
                initialFilters: { q: 'nothing', userId: '', type: '', modified: '' },
            });

            expect(wrapper.find('.cfiles-empty').text()).toContain('No results in this folder and its subfolders.');
            const reset = wrapper.findAll('button').find((b) => b.text() === 'Reset filters');

            await reset.trigger('click');
            await flushPromises();

            // One load, without the filters but with the sort.
            expect(globalThis.humhubStubs.client.get).toHaveBeenCalledTimes(1);
            expect(lastUrl()).not.toContain('q=');
            expect(lastUrl()).toContain('sort=newest');
            expect(window.location.search).not.toContain('q=');
            expect(wrapper.find('.form-search-filter-q input').element.value).toBe('');
        });

        it('says there are no results in all files at the top level', () => {
            const wrapper = browser(results(topLevel([])), { initialFilters: { q: 'nothing', userId: '', type: '', modified: '' } });

            expect(wrapper.find('.cfiles-empty').text()).toContain('No results in all files.');
            expect(wrapper.findAll('button').some((b) => b.text() === 'Reset filters')).toBe(true);
        });

        // Hits lie in subfolders: moving them into the open folder is a real move.
        it('moves hits of subfolders into the open folder', async () => {
            const wrapper = browser(results(insideFolder([inBrand()])));

            wrapper.vm.moveTo(9, [inBrand()]);
            await flushPromises();

            expect(globalThis.humhubStubs.client.post).toHaveBeenCalledTimes(1);
            expect(globalThis.humhubStubs.client.post.mock.calls[0][0]).toContain('items/move');
        });

        it('does not move hits that already are in the target folder', async () => {
            const wrapper = browser(results(insideFolder([inBrand()])));

            wrapper.vm.moveTo(8, [inBrand()]);
            await flushPromises();

            expect(globalThis.humhubStubs.client.post).not.toHaveBeenCalled();
        });

        it('takes the sort of the history entry it goes back to', async () => {
            const wrapper = browser(topLevel());
            // The server reports the sort it was asked for.
            globalThis.humhubStubs.client.get = vi.fn((url) => Promise.resolve(topLevel(undefined, {
                sort: new URL(url, 'http://localhost').searchParams.get('sort') || 'default',
            })));

            window.history.replaceState({}, '', '/s/x/cfiles/browse/index?fid=0&sort=newest');
            window.dispatchEvent(new Event('popstate'));
            await flushPromises();

            expect(lastUrl()).toContain('sort=newest');
            expect(wrapper.vm.sort).toBe('newest');
        });

        // A link without `fid` (`/cfiles/browse?q=…`) is the top level.
        it('goes back to an entry without a folder as the top level', async () => {
            browser(insideFolder([]));

            window.history.replaceState({}, '', '/s/x/cfiles/browse/index?q=logo');
            window.dispatchEvent(new Event('popstate'));
            await flushPromises();

            expect(lastUrl()).not.toContain('parent=');
            expect(lastUrl()).toContain('q=logo');
        });

        describe('author', () => {
            const person = { id: 3, guid: 'g-3', displayName: 'Sara Tester', url: '/u/3', imageUrl: '/i/3.jpg', contentContainerId: 103, title: null, tags: [] };
            const itemCalls = () => globalThis.humhubStubs.client.get.mock.calls.map(([url]) => url).filter((url) => url.includes('/items'));
            const control = (wrapper) => wrapper.findComponent({ name: 'UserFilterControl' });

            beforeEach(() => {
                // The items endpoint, and the user search the control resolves an id with.
                globalThis.humhubStubs.client.get = vi.fn((url) => Promise.resolve(url.includes('/items')
                    ? topLevel()
                    : { results: [person], total: 1 }));
            });

            it('renders the person control for the author', () => {
                const wrapper = browser(topLevel());

                expect(control(wrapper).exists()).toBe(true);
                expect(wrapper.find('.form-search-filter-userId input[role="combobox"]').exists()).toBe(true);
            });

            it('reloads with the author chosen and mirrors it into the page URL', async () => {
                const wrapper = browser(topLevel());

                control(wrapper).vm.$emit('update:modelValue', '3');
                await flushPromises();

                expect(itemCalls()).toHaveLength(1);
                expect(itemCalls()[0]).toContain('userId=3');
                expect(new URLSearchParams(window.location.search).get('userId')).toBe('3');
            });

            it('takes the author of the history entry it goes back to', async () => {
                const wrapper = browser(topLevel());

                window.history.replaceState({}, '', '/s/x/cfiles/browse/index?fid=0&userId=3');
                window.dispatchEvent(new Event('popstate'));
                await flushPromises();

                expect(itemCalls().at(-1)).toContain('userId=3');
                expect(wrapper.vm.filterValues.userId).toBe('3');
            });

            it('is cleared by the reset of an empty result list', async () => {
                window.history.replaceState({}, '', '/s/x/cfiles/browse/index?fid=0&userId=3');
                const wrapper = browser(results(topLevel([])), { initialFilters: { q: '', userId: '3', type: '', modified: '' } });
                await flushPromises();
                expect(itemCalls()).toHaveLength(0);

                await wrapper.findAll('button').find((b) => b.text() === 'Reset filters').trigger('click');
                await flushPromises();

                expect(itemCalls()).toHaveLength(1);
                expect(itemCalls()[0]).not.toContain('userId=');
                expect(window.location.search).not.toContain('userId=');
            });

            // A guest has no Author filter: the page dropped the link's author.
            it('drops an author the page did not apply from the URL', async () => {
                window.history.replaceState({}, '', '/s/x/cfiles/browse/index?fid=0&userId=3');

                browser(topLevel());
                await flushPromises();

                expect(itemCalls()).toHaveLength(0);
                expect(window.location.search).not.toContain('userId=');
            });
        });

        // The page URL is put in line with what the first page was built with, before the bar
        // reads it: nothing to apply, nothing refused, no stored sort forgotten.
        describe('the page URL on mount', () => {
            it('takes the value the list used for a padded search', async () => {
                window.history.replaceState({}, '', '/s/x/cfiles/browse/index?fid=0&q=%20logo%20');

                browser(results(topLevel([inBrand()])), { initialFilters: { q: 'logo', userId: '', type: '', modified: '' } });
                await flushPromises();

                expect(globalThis.humhubStubs.client.get).not.toHaveBeenCalled();
                expect(new URLSearchParams(window.location.search).get('q')).toBe('logo');
                expect(new URLSearchParams(window.location.search).get('fid')).toBe('0');
            });

            it('drops a search the list refused', async () => {
                window.history.replaceState({}, '', '/s/x/cfiles/browse/index?q=' + 'x'.repeat(300));

                browser(topLevel());
                await flushPromises();

                expect(globalThis.humhubStubs.client.get).not.toHaveBeenCalled();
                expect(window.location.search).not.toContain('q=');
            });

            it('drops a sort the list refused without forgetting the stored one', async () => {
                window.history.replaceState({}, '', '/s/x/cfiles/browse/index?fid=0&sort=bogus');

                const wrapper = browser(topLevel(undefined, { sort: 'newest' }));
                await flushPromises();

                expect(globalThis.humhubStubs.client.get).not.toHaveBeenCalled();
                expect(window.location.search).not.toContain('sort=');
                expect(wrapper.vm.sort).toBe('newest');
            });

            it('keeps a sort the list used', async () => {
                window.history.replaceState({}, '', '/s/x/cfiles/browse/index?fid=0&sort=oldest');

                browser(topLevel(undefined, { sort: 'oldest' }));
                await flushPromises();

                expect(globalThis.humhubStubs.client.get).not.toHaveBeenCalled();
                expect(window.location.search).toContain('sort=oldest');
            });
        });

        it('shows the folder-is-empty state for an empty level', () => {
            const wrapper = browser(topLevel([]));

            expect(wrapper.find('.cfiles-empty').text()).toContain('This folder is empty.');
            expect(wrapper.findAll('button').some((b) => b.text() === 'Reset filters')).toBe(false);
        });

        it('takes the filters of the history entry it goes back to', async () => {
            const wrapper = browser(insideFolder([]));

            window.history.replaceState({}, '', '/s/x/cfiles/browse/index?fid=0&q=logo');
            window.dispatchEvent(new Event('popstate'));
            await flushPromises();

            expect(lastUrl()).not.toContain('parent=');
            expect(lastUrl()).toContain('q=logo');
            expect(wrapper.vm.filterValues.q).toBe('logo');
        });

        it('drops a further page of the results before the filters changed', async () => {
            const more = deferred();
            const reload = deferred();
            queuedGets(more, reload);
            const wrapper = browser(topLevel(undefined, { pages: 2 }));

            wrapper.vm.loadMore();
            bar(wrapper).setFilter('type', 'image');
            reload.resolve(results(topLevel([inBrand()])));
            await flushPromises();
            more.resolve(topLevel([fileRow({ id: 99 })], { page: 2, pages: 2 }));
            await flushPromises();

            expect(wrapper.vm.items.map((i) => i.id)).toEqual([31]);
        });
    });

    describe('row context menu', () => {
        // Scoped to the row: the toolbar has a dropdown of its own.
        const rowMenu = (wrapper) => wrapper.findAll('.cfiles-row-controls .dropdown-item')
            .map((i) => i.text());

        const openMenu = async (wrapper, index = 0) => {
            wrapper.findAll('.cfiles-row a[data-bs-toggle="dropdown"]')[index].element
                .dispatchEvent(new Event('show.bs.dropdown'));
            await flushPromises();
        };

        beforeEach(() => {
            globalThis.humhubStubs.client.get = vi.fn(() => Promise.resolve({
                entries: [],
                capabilities: { canEdit: true, canDelete: true, canMove: true },
            }));
        });

        it('offers Open for a folder and Download for a file before anything is loaded', () => {
            const wrapper = browser(topLevel([folderRow(), fileRow()]));
            const labels = wrapper.findAll('.cfiles-row').map(
                (r) => r.findAll('.dropdown-item').map((i) => i.text()),
            );

            expect(labels).toEqual([['Open'], ['Download']]);
        });

        it('adds the editing actions once the permissions are in', async () => {
            const wrapper = browser(topLevel([fileRow()]));
            await openMenu(wrapper);

            expect(rowMenu(wrapper)).toEqual(['Download', 'Edit', 'Move', 'Delete']);
        });

        it('leaves out what the caller may not do', async () => {
            globalThis.humhubStubs.client.get = vi.fn(() => Promise.resolve({
                entries: [],
                capabilities: { canEdit: false, canDelete: false },
            }));
            const wrapper = browser(topLevel([fileRow()]));
            await openMenu(wrapper);

            expect(rowMenu(wrapper)).toEqual(['Download']);
        });

        it('appends what the server contributed', async () => {
            globalThis.humhubStubs.client.get = vi.fn(() => Promise.resolve({
                entries: [{ id: 'topics', label: 'Topics', icon: 'tags', sortOrder: 370 }],
                capabilities: { canEdit: true, canDelete: true },
            }));
            const wrapper = browser(topLevel([fileRow()]));
            await openMenu(wrapper);

            expect(rowMenu(wrapper)).toContain('Topics');
        });

        it('downloads through the cache-busting URL rather than the file URL', () => {
            const wrapper = browser(topLevel([fileRow()]));

            expect(wrapper.find('.cfiles-row-controls .dropdown-item').attributes('href'))
                .toBe('/s/x/cfiles/download/f-21');
        });
    });

    describe('write permission', () => {
        it('offers no selection checkboxes when the caller may not write', () => {
            const wrapper = browser(topLevel(), { canWrite: false });

            expect(wrapper.findAll('input[type="checkbox"]')).toHaveLength(0);
        });
    });
});
