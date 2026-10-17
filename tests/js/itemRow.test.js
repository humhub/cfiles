import { beforeEach, describe, expect, it, vi } from 'vitest';
import { flushPromises, mount } from '@vue/test-utils';
import ItemRow from '../../vue/browser/ItemRow.vue';
import ContentControls from '@core/modules/content/vue/ContentControls.vue';
import { fileRow, folderRow } from './support/fixtures.mjs';
import { CONTROLS_VIEW_CONTEXT } from '../../vue/browser/itemPresentation';

const row = (item, over = {}) => mount(ItemRow, {
    props: {
        item,
        folderUrl: (id) => '/b?fid=' + (id || 0),
        entries: [],
        ...over,
    },
});

/**
 * A real right-click. `wrapper.trigger()` cannot be used here: the assertions are about the
 * pointer coordinates and about whether the native menu was suppressed, and both live on the
 * event object itself.
 */
const rightClick = (wrapper, init = {}) => {
    const event = new MouseEvent('contextmenu', {
        bubbles: true, cancelable: true, button: 2, clientX: 40, clientY: 90, ...init,
    });
    wrapper.element.dispatchEvent(event);

    return event;
};

/** Watches the row's context menu without letting it reach Bootstrap, which is not loaded here. */
const watchMenu = (wrapper) => vi.spyOn(wrapper.findComponent(ContentControls).vm, 'open')
    .mockImplementation(() => {});

describe('ItemRow', () => {
    beforeEach(() => {
        globalThis.humhub.config.module('i18n').language = 'de-DE';
        globalThis.humhubStubs.client.get = vi.fn(() => Promise.resolve({
            entries: [],
            capabilities: { canEdit: true, canDelete: true, canMove: true },
        }));
    });

    describe('presentation', () => {
        it('links a folder to its own page and a file to the file itself', () => {
            expect(row(folderRow()).find('h4 a').attributes('href')).toBe('/b?fid=11');
            expect(row(fileRow()).find('h4 a').attributes('href')).toBe('/file/f-21');
        });

        // A file is not always just a download: a module may have contributed a viewer or an
        // editor, and the server says which of the two link shapes applies.
        describe('what the name links to', () => {
            it('carries the download hooks when only the download handler applies', () => {
                const link = row(fileRow()).find('h4 a');

                expect(link.attributes('href')).toBe('/file/f-21');
                expect(link.attributes('target')).toBe('_blank');
                expect(link.attributes('data-file-name')).toBe('Angebot.pdf');
            });

            it('opens the file dialog when a module contributed a handler', () => {
                const link = row(fileRow({
                    link: { url: '/file/view?guid=f-21', attributes: { 'data-bs-target': '#globalModal' } },
                })).find('h4 a');

                expect(link.attributes('href')).toBe('/file/view?guid=f-21');
                expect(link.attributes('data-bs-target')).toBe('#globalModal');
                expect(link.attributes('target')).toBeUndefined();
            });

            it('falls back to the plain file url when the payload carries no decision', () => {
                const link = row(fileRow({ link: undefined })).find('h4 a');

                expect(link.attributes('href')).toBe('/file/f-21');
            });

            it('never applies any of it to a folder', () => {
                const link = row(folderRow()).find('h4 a');

                expect(link.attributes('href')).toBe('/b?fid=11');
                expect(link.attributes('data-bs-target')).toBeUndefined();
            });
        });

        it('shows a folder icon for a folder and a mime icon for a file', () => {
            expect(row(folderRow()).find('.cfiles-icon-folder').exists()).toBe(true);
            expect(row(fileRow()).find('.ti-file-type-pdf').exists()).toBe(true);
        });

        it('prefers a thumbnail over an icon when there is one', () => {
            const wrapper = row(fileRow({ previewUrl: '/preview/21.jpg' }));

            expect(wrapper.find('img.cfiles-thumb').attributes('src')).toBe('/preview/21.jpg');
            expect(wrapper.find('.cfiles-icon-file').exists()).toBe(false);
        });

        it('marks a private item and leaves a public one unmarked', () => {
            expect(row(fileRow({ visibility: 0 })).find('.ti-lock').exists()).toBe(true);
            expect(row(fileRow({ visibility: 1 })).find('.ti-lock').exists()).toBe(false);
        });

        it('counts a folder\'s items and sizes a file', () => {
            expect(row(folderRow({ itemCount: 4 })).find('.cfiles-row-meta').text()).toContain('4 items');
            expect(row(folderRow({ itemCount: 1 })).find('.cfiles-row-meta').text()).toContain('1 item');
            expect(row(folderRow({ itemCount: 0 })).find('.cfiles-row-meta').text()).toContain('empty');
            expect(row(fileRow()).find('.cfiles-row-meta').text()).toContain('1.2 MB');
        });

        it('names the topics of the item, each with its colour', () => {
            const topics = row(fileRow({ topics: [{ id: 4, name: 'Budget', color: '#ff0000' }, { id: 6, name: 'Brand', color: null }] }))
                .findAll('.cfiles-row-meta .cfiles-row-topic');

            expect(topics.map((topic) => topic.text())).toEqual(['Budget', 'Brand']);
            expect(topics[0].find('.cfiles-row-topic__dot').attributes('style')).toContain('background-color');
            expect(row(fileRow()).find('.cfiles-row-topic').exists()).toBe(false);
        });

        // Read out as "Topics: Budget Brand", not as two stray words after the date.
        it('introduces the topics for a screen reader only', () => {
            const hidden = row(fileRow({ topics: [{ id: 4, name: 'Budget', color: null }] })).find('.cfiles-row-meta .visually-hidden');

            expect(hidden.text()).toBe('Topics:');
            expect(row(fileRow()).find('.cfiles-row-meta .visually-hidden').exists()).toBe(false);
        });

        it('appends the description when there is one', () => {
            const meta = row(fileRow({ description: 'Erste Fassung' })).find('.cfiles-row-meta').text();

            expect(meta).toContain('Erste Fassung');
        });

        // A recent change reads as "3 days ago", an older one as a date - the same split the
        // platform's TimeAgo widget makes - and both in the HumHub language, not the browser's.
        describe('timestamps', () => {
            it('gives a recent change a relative time', () => {
                const twoHoursAgo = new Date(Date.now() - 2 * 60 * 60 * 1000).toISOString();
                const meta = row(fileRow({ updatedAt: twoHoursAgo })).find('.cfiles-row-meta').text();

                expect(meta).toMatch(/Stunden|hours/);
            });

            it('gives an older change a date in the HumHub language, not the browser\'s', () => {
                const german = row(fileRow({ updatedAt: '2026-01-15T09:00:00+00:00' }))
                    .find('.cfiles-row-meta').text();

                expect(german).toContain('15.01.2026');

                globalThis.humhub.config.module('i18n').language = 'en-US';
                const english = row(fileRow({ updatedAt: '2026-01-15T09:00:00+00:00' }))
                    .find('.cfiles-row-meta').text();

                expect(english).toContain('Jan 15, 2026');
            });
        });
    });

    describe('selection', () => {
        it('offers a checkbox only where selection is possible', () => {
            expect(row(fileRow(), { selectable: true }).find('input[type="checkbox"]').exists()).toBe(true);
            expect(row(fileRow()).find('input[type="checkbox"]').exists()).toBe(false);
        });

        it('emits the item and the range flag when its checkbox is clicked', async () => {
            const wrapper = row(fileRow(), { selectable: true });
            await wrapper.find('input[type="checkbox"]').trigger('click');
            await wrapper.find('input[type="checkbox"]').trigger('click', { shiftKey: true });

            expect(wrapper.emitted('toggle-select')[0][0].id).toBe(21);
            expect(wrapper.emitted('toggle-select')[0][1]).toEqual({ range: false });
            expect(wrapper.emitted('toggle-select')[1][1]).toEqual({ range: true });
        });

        // The browser flips the box before the owner decides; it must show what `selected` says.
        it('puts the box back to the selection when the owner does not change it', async () => {
            const wrapper = row(fileRow(), { selectable: true, selected: false });
            const box = wrapper.find('input[type="checkbox"]');

            box.element.click();
            expect(box.element.checked).toBe(true);
            await wrapper.vm.$nextTick();

            expect(box.element.checked).toBe(false);
        });

        it('marks a selected row', () => {
            expect(row(fileRow(), { selected: true }).classes()).toContain('is-selected');
            expect(row(fileRow()).classes()).not.toContain('is-selected');
        });
    });

    describe('context menu', () => {
        // Without this the row menu shows the whole stream-entry stack (pin, archive,
        // permalink) next to the file actions.
        it('tells the server not to send the core entries the row renders itself', async () => {
            const wrapper = row(fileRow());

            wrapper.find('a[data-bs-toggle="dropdown"]').element
                .dispatchEvent(new Event('show.bs.dropdown'));
            await flushPromises();

            expect(globalThis.humhubStubs.client.get.mock.calls[0][0])
                .toContain('suppress=edit%2Cdelete%2Cpermalink%2Cpin%2Cmove%2Carchive');
        });

        // The value has to be one of core's own VIEW_CONTEXT_* values; anything else is
        // silently treated as "not the default profile" and would break the day core starts
        // matching on the name. See CONTROLS_VIEW_CONTEXT for why it is this one.
        it('asks for a non-stream view context core actually knows', async () => {
            const wrapper = row(fileRow());

            wrapper.find('a[data-bs-toggle="dropdown"]').element
                .dispatchEvent(new Event('show.bs.dropdown'));
            await flushPromises();

            const url = globalThis.humhubStubs.client.get.mock.calls[0][0];

            expect(url).toContain(`viewContext=${CONTROLS_VIEW_CONTEXT}`);
            expect(['default', 'dashboard', 'search', 'detail', 'modal']).toContain(CONTROLS_VIEW_CONTEXT);
        });

        it('raises the menu at the cursor on a right-click anywhere on the row', () => {
            const wrapper = row(fileRow());
            const open = watchMenu(wrapper);

            const event = rightClick(wrapper);

            expect(open).toHaveBeenCalledTimes(1);
            expect(open.mock.calls[0][0].clientX).toBe(40);
            expect(open.mock.calls[0][0].clientY).toBe(90);
            expect(event.defaultPrevented).toBe(true);
        });

        // Menu key / Shift+F10: no pointer position worth using, so under the row's own toggle.
        it('opens a keyboard-raised menu under its toggle, not at the pointer', () => {
            const wrapper = row(fileRow());
            const open = watchMenu(wrapper);

            const event = rightClick(wrapper, { button: 0 });

            expect(open).toHaveBeenCalledWith(null);
            expect(event.defaultPrevented).toBe(true);
        });

        it('leaves the browser its own menu on an upload row', () => {
            const upload = { type: 'upload', id: 1, title: 'new.pdf', uploading: true, progress: 30, icon: 'file' };
            const wrapper = row(upload);

            const event = rightClick(wrapper);

            expect(event.defaultPrevented).toBe(false);
        });

        it('leaves ctrl+right-click to the browser, as the platform always has', () => {
            const wrapper = row(fileRow());
            const open = watchMenu(wrapper);

            const event = rightClick(wrapper, { ctrlKey: true });

            expect(open).not.toHaveBeenCalled();
            expect(event.defaultPrevented).toBe(false);
        });

        it('leaves a right-click inside the open menu to the menu', () => {
            const wrapper = row(fileRow());
            const open = watchMenu(wrapper);

            wrapper.find('.dropdown-menu').element.dispatchEvent(
                new MouseEvent('contextmenu', { bubbles: true, cancelable: true }),
            );

            expect(open).not.toHaveBeenCalled();
        });
    });

    /**
     * The row itself is the click target for the item it shows — a browser where only the
     * name is clickable makes every open a precision exercise.
     */
    describe('opening by clicking the row', () => {
        it('opens a folder', async () => {
            const wrapper = row(folderRow());

            await wrapper.trigger('click');

            expect(wrapper.emitted('open')[0][0].id).toBe(11);
        });

        it('follows a file through its own link, whatever the server made of it', async () => {
            const wrapper = row(fileRow({
                link: { url: '/file/view?guid=f-21', attributes: { 'data-bs-target': '#globalModal' } },
            }));
            const link = wrapper.find('h4 a').element;
            const clicks = vi.fn((event) => event.preventDefault());
            link.addEventListener('click', clicks);

            await wrapper.trigger('click');

            // Through the anchor, not around it: its attributes are what the platform's own
            // delegated handlers read to decide what opening this file means.
            expect(clicks).toHaveBeenCalledTimes(1);
            expect(wrapper.emitted('open')).toBeFalsy();
        });

        it('leaves the checkbox, the menu and the creator link their own clicks', async () => {
            const wrapper = row(folderRow(), { selectable: true });

            await wrapper.find('input[type="checkbox"]').trigger('click');
            await wrapper.find('a[data-bs-toggle="dropdown"]').trigger('click');

            expect(wrapper.emitted('open')).toBeFalsy();
        });

        it('leaves a modifier click to the browser', async () => {
            const wrapper = row(folderRow());

            await wrapper.trigger('click', { metaKey: true });
            await wrapper.trigger('click', { ctrlKey: true });
            await wrapper.trigger('click', { shiftKey: true });

            expect(wrapper.emitted('open')).toBeFalsy();
        });

        it('does not open when the click only ended a text selection', async () => {
            const wrapper = row(folderRow());
            vi.spyOn(window, 'getSelection').mockReturnValue({
                isCollapsed: false,
                anchorNode: wrapper.find('h5').element,
            });

            await wrapper.trigger('click');

            expect(wrapper.emitted('open')).toBeFalsy();
            window.getSelection.mockRestore();
        });
    });

    /**
     * The like link IS core's `LikeButton` island — this module renders it and nothing else,
     * with the state the listing already brought.
     */
    describe('likes', () => {
        const states = (over = {}) => ({ 1201: { total: 3, liked: true, canLike: true }, ...over });

        it('renders the platform like link with the state the listing brought', () => {
            const wrapper = row(fileRow(), { likeStates: states() });

            const like = wrapper.find('.likeLinkContainer');
            expect(like.exists()).toBe(true);
            // Liked already, so it offers the way back — and says how many.
            expect(like.find('a.unlike').exists()).toBe(true);
            expect(like.find('.likeCount').text()).toBe('(3)');
        });

        it('renders no like link where there is no state for the row', () => {
            // What a listing looks like with the like module switched off.
            expect(row(fileRow()).find('.likeLinkContainer').exists()).toBe(false);
        });

        it('renders no like link for a reader who may not like and nothing to count', () => {
            const wrapper = row(fileRow(), {
                likeStates: states({ 1201: { total: 0, liked: false, canLike: false } }),
            });

            expect(wrapper.find('.likeLinkContainer').exists()).toBe(false);
        });

        it('still shows an existing like count to a reader who may not like', () => {
            const wrapper = row(fileRow(), {
                likeStates: states({ 1201: { total: 2, liked: false, canLike: false } }),
            });

            expect(wrapper.find('.likeCount').text()).toBe('(2)');
        });

        // The row is one big click target; the like link must not trigger it.
        it('does not open the item when the like link is clicked', async () => {
            const wrapper = row(folderRow(), { likeStates: { 1101: { total: 1, liked: false, canLike: true } } });

            await wrapper.find('.likeLinkContainer a').trigger('click');

            expect(wrapper.emitted('open')).toBeFalsy();
        });
    });

    describe('drag and drop', () => {
        const dataTransfer = () => ({ setData: vi.fn(), effectAllowed: null });
        const dispatch = (wrapper, type, init = {}, target = wrapper.element) => {
            const event = new MouseEvent(type, { bubbles: true, cancelable: true, ...init });
            if (init.dataTransfer) {
                Object.defineProperty(event, 'dataTransfer', { value: init.dataTransfer });
            }
            target.dispatchEvent(event);

            return event;
        };

        it('is draggable only when asked to, and never through its title link', () => {
            expect(row(fileRow(), { draggable: true }).attributes('draggable')).toBe('true');
            expect(row(fileRow()).attributes('draggable')).toBe('false');
            expect(row(fileRow(), { draggable: true }).find('h4 a').attributes('draggable')).toBe('false');
        });

        it('starts a drag with the item and the event, carrying its key', () => {
            const wrapper = row(fileRow(), { draggable: true });
            const transfer = dataTransfer();

            const event = dispatch(wrapper, 'dragstart', { dataTransfer: transfer });

            expect(wrapper.emitted('drag-start')[0][0].id).toBe(21);
            expect(wrapper.emitted('drag-start')[0][1]).toBe(event);
            expect(transfer.effectAllowed).toBe('move');
            expect(transfer.setData).toHaveBeenCalledWith('text/plain', 'file:21');
        });

        it('starts no drag where it is not draggable', () => {
            const wrapper = row(fileRow());

            dispatch(wrapper, 'dragstart', { dataTransfer: dataTransfer() });
            dispatch(wrapper, 'dragend');

            expect(wrapper.emitted('drag-start')).toBeFalsy();
            expect(wrapper.emitted('drag-end')).toBeFalsy();
        });

        // Pair it with the row's own drag-start, even if it stopped being draggable since.
        it('ends only the drag it started', async () => {
            const wrapper = row(fileRow(), { draggable: true });

            dispatch(wrapper, 'dragend');
            expect(wrapper.emitted('drag-end')).toBeFalsy();

            dispatch(wrapper, 'dragstart', { dataTransfer: dataTransfer() });
            await wrapper.setProps({ draggable: false });
            const end = dispatch(wrapper, 'dragend');
            dispatch(wrapper, 'dragend');

            expect(wrapper.emitted('drag-end')).toHaveLength(1);
            expect(wrapper.emitted('drag-end')[0][0].id).toBe(21);
            expect(wrapper.emitted('drag-end')[0][1]).toBe(end);
        });

        it('takes a drag over and a drop only where canDrop allows it', () => {
            const canDrop = vi.fn((item) => item.type === 'folder');
            const folder = row(folderRow(), { canDrop });
            const file = row(fileRow(), { canDrop });

            const overFile = dispatch(file, 'dragover');
            const dropFile = dispatch(file, 'drop');
            expect(overFile.defaultPrevented).toBe(false);
            expect(dropFile.defaultPrevented).toBe(false);
            expect(file.emitted('drag-over')).toBeFalsy();
            expect(file.emitted('drop-on')).toBeFalsy();

            const overFolder = dispatch(folder, 'dragover');
            const dropFolder = dispatch(folder, 'drop');
            expect(overFolder.defaultPrevented).toBe(true);
            expect(folder.emitted('drag-over')[0][1]).toBe(overFolder);
            expect(dropFolder.defaultPrevented).toBe(true);
            expect(folder.emitted('drop-on')[0][0].id).toBe(11);
            expect(folder.emitted('drop-on')[0][1]).toBe(dropFolder);
            expect(canDrop.mock.calls[0][1]).toBe(overFile);
        });

        // Files dragged in from the desktop are an upload onto the folder, and must land there.
        it('accepts a file drop from the desktop where canDrop allows it', () => {
            const canDrop = (item, e) => item.type === 'folder' && e.dataTransfer.types.includes('Files');
            const wrapper = row(folderRow(), { canDrop });
            const files = [new File(['x'], 'a.txt')];

            const drop = dispatch(wrapper, 'drop', { dataTransfer: { types: ['Files'], files } });

            expect(drop.defaultPrevented).toBe(true);
            expect(wrapper.emitted('drop-on')[0][0].id).toBe(11);
            expect(wrapper.emitted('drop-on')[0][1]).toBe(drop);
            expect(wrapper.emitted('drop-on')[0][1].dataTransfer.files).toBe(files);
        });

        // In Chrome an <img> drag carries 'Files' and would read as an upload onto a folder.
        it.each([
            ['the creator', '.cfiles-row-creator'],
            ['the like link', '.cfiles-row-social'],
        ])('starts no drag of its own from inside %s', (name, selector) => {
            const wrapper = row(fileRow(), {
                draggable: true,
                likeStates: { 1201: { total: 1, liked: false, canLike: true } },
            });
            const inner = wrapper.find(selector).element.querySelector('a, img') || wrapper.find(selector).element;

            const event = dispatch(wrapper, 'dragstart', { dataTransfer: dataTransfer() }, inner);

            expect(event.defaultPrevented).toBe(true);
            expect(wrapper.emitted('drag-start')).toBeFalsy();
        });

        it('accepts no drop without a canDrop', () => {
            const wrapper = row(folderRow());

            expect(dispatch(wrapper, 'drop').defaultPrevented).toBe(false);
            expect(wrapper.emitted('drop-on')).toBeFalsy();
        });

        // Leaving a folder used to be reported as drag-end; it is its own event now.
        it('reports leaving the row as drag-leave, not as the end of the drag', () => {
            const wrapper = row(folderRow(), { canDrop: () => true });

            dispatch(wrapper, 'dragleave', { relatedTarget: wrapper.find('h5').element });
            expect(wrapper.emitted('drag-leave')).toBeFalsy();

            dispatch(wrapper, 'dragleave', { relatedTarget: document.body });
            expect(wrapper.emitted('drag-leave')).toHaveLength(1);
            expect(wrapper.emitted('drag-end')).toBeFalsy();
        });

        it('marks itself as the drop target', () => {
            expect(row(folderRow(), { dropTarget: true }).classes()).toContain('is-drop-target');
            expect(row(folderRow()).classes()).not.toContain('is-drop-target');
        });

        it('never drags an upload', () => {
            const upload = { type: 'upload', id: 1, title: 'new.pdf', uploading: true, progress: 30, icon: 'file' };
            const wrapper = row(upload, { draggable: true });

            dispatch(wrapper, 'dragstart', { dataTransfer: dataTransfer() });

            expect(wrapper.attributes('draggable')).toBe('false');
            expect(wrapper.emitted('drag-start')).toBeFalsy();
        });
    });

    describe('the location of a hit', () => {
        const hit = () => fileRow({ parentFolderId: 8, path: [{ id: 7, title: 'Brand' }, { id: 8, title: 'Logos' }] });

        it('is appended to the meta line as a link to the folder the hit lies in', () => {
            const meta = row(hit()).find('.cfiles-row-meta');
            const location = meta.find('a.cfiles-location');

            expect(location.text()).toBe('in Brand › Logos');
            expect(location.attributes('href')).toBe('/b?fid=8');
            expect(meta.text()).toMatch(/ · in Brand › Logos$/);
        });

        it('is not there for an item directly in the open folder', () => {
            expect(row(fileRow()).find('.cfiles-location').exists()).toBe(false);
        });

        it('names the folder it opens', () => {
            expect(row(hit()).find('.cfiles-location').attributes('aria-label')).toBe('Open Logos');
        });

        // The meta line is cut at its end: a long description must not hide where the hit is.
        it('takes the place of the description', () => {
            const described = { description: 'A very long description of the file' };

            expect(row(fileRow(described)).find('.cfiles-row-meta').text()).toContain('A very long description');

            const meta = row({ ...hit(), ...described }).find('.cfiles-row-meta').text();
            expect(meta).not.toContain('A very long description');
            expect(meta).toMatch(/in Brand › Logos$/);
        });

        it('opens the folder on a plain click, not the item', async () => {
            const wrapper = row(hit());
            const click = vi.fn();
            wrapper.find('h4 a').element.addEventListener('click', click);

            await wrapper.find('.cfiles-location').trigger('click');

            expect(wrapper.emitted('open')).toHaveLength(1);
            expect(wrapper.emitted('open')[0][0]).toMatchObject({ type: 'folder', id: 8 });
            // The row's own click (which would open the file) stays out of it.
            expect(click).not.toHaveBeenCalled();
        });

        it('leaves a modified click to the browser', async () => {
            const wrapper = row(hit());
            // Not prevented on purpose: block jsdom's navigation above the link.
            const block = (e) => e.preventDefault();
            wrapper.element.addEventListener('click', block);

            await wrapper.find('.cfiles-location').trigger('click', { ctrlKey: true });
            wrapper.element.removeEventListener('click', block);

            expect(wrapper.emitted('open')).toBeUndefined();
        });
    });
});
