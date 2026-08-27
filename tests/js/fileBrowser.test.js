import { beforeEach, describe, expect, it, vi } from 'vitest';
import { flushPromises, mount } from '@vue/test-utils';
import CfilesFileBrowser from '../../vue/CfilesFileBrowser.vue';
import { browserProps, fileRow, folderRow, insideFolder, topLevel } from './support/fixtures.mjs';

const browser = (listing, over = {}) => mount(CfilesFileBrowser, { props: browserProps(listing, over) });

describe('CfilesFileBrowser', () => {
    beforeEach(() => {
        globalThis.humhubStubs.client.get = vi.fn(() => Promise.resolve(topLevel()));
        globalThis.humhubStubs.client.post = vi.fn(() => Promise.resolve({ results: [], errors: [] }));
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
            expect(wrapper.findAll('.breadcrumb-item').map((c) => c.text())).toEqual(['Files']);
        });

        it('reports the open folder when there is one', () => {
            const wrapper = browser(insideFolder([fileRow()]));

            expect(wrapper.vm.folderId).toBe(9);
            expect(wrapper.findAll('.breadcrumb-item').map((c) => c.text()))
                .toEqual(['Files', 'test123', 'sgadgasdg']);
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

        it('keeps folder links as real hrefs so they can be opened in a new tab', () => {
            const wrapper = browser(topLevel());

            expect(wrapper.find('.cfiles-row a').attributes('href'))
                .toBe('/s/x/cfiles/browse/index?fid=11');
        });

        it('lets a modified click through to the browser', async () => {
            const wrapper = browser(topLevel());

            await wrapper.find('.cfiles-row a').trigger('click', { metaKey: true });

            expect(globalThis.humhubStubs.client.get).not.toHaveBeenCalled();
        });
    });

    describe('selection and bulk actions', () => {
        it('collects the selected rows and moves them to the top level', async () => {
            const wrapper = browser(insideFolder([fileRow()]));

            await wrapper.findAll('input[type="checkbox"]')[0].setValue(true);
            expect(wrapper.vm.selection).toEqual(['file:21']);

            wrapper.vm.moveItemsList = wrapper.vm.selectedItems;
            wrapper.vm.moveTo(null);
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

            wrapper.vm.moveItemsList = [fileRow()];
            wrapper.vm.moveTo(null);
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

    // The row menu's own entries are defined by the browser, not the row: the browser is what
    // knows how to open, edit, move and delete an item.
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
