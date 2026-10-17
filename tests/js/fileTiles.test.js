import { describe, expect, it, vi } from 'vitest';
import { mount } from '@vue/test-utils';
import FileTiles from '../../vue/browser/FileTiles.vue';
import { fileRow, folderRow } from './support/fixtures.mjs';

// The browser hands the grid its items keyed by `type:id` (see `api.keyOf`).
const keyed = (list) => list.map((item) => ({ ...item, key: item.type + ':' + item.id }));
const items = keyed([folderRow(), fileRow()]);
const tiles = (props = {}) => mount(FileTiles, {
    props: {
        items,
        entriesFor: () => [],
        folderUrl: (id) => '/b?fid=' + (id || 0),
        ...props,
        ...(props.items ? { items: keyed(props.items) } : {}),
    },
    attachTo: document.body,
});

describe('FileTiles', () => {
    it('draws a folder as the filled folder glyph with its item count', () => {
        const folder = tiles().findAll('.c-tile-grid__tile')[0];

        expect(folder.find('.c-tile-grid__thumb i.ti.ti-folder-filled.cfiles-tile__folder').exists()).toBe(true);
        expect(folder.find('.c-tile-grid__meta').text()).toBe('4 items');
    });

    it('draws a file as a document card with its type icon and size', () => {
        const file = tiles().findAll('.c-tile-grid__tile')[1];

        expect(file.find('.cfiles-tile__doc i.ti.ti-file-type-pdf').exists()).toBe(true);
        expect(file.find('.c-tile-grid__meta').text()).toBe('1.2 MB');
    });

    it('shows a preview image when the file has one, not draggable on its own', () => {
        const file = tiles({ items: [fileRow({ previewUrl: '/p.jpg' })] }).find('.c-tile-grid__tile');
        const img = file.find('img.cfiles-tile__image');

        expect(img.attributes('src')).toBe('/p.jpg');
        expect(img.attributes('draggable')).toBe('false');
    });

    it('links the name like the list does, never as a drag source', () => {
        const [folder, file] = tiles().findAll('.c-tile-grid__name a');

        expect(folder.attributes('href')).toBe('/b?fid=11');
        expect(file.attributes('href')).toBe('/file/f-21');
        expect(file.attributes('data-file-download')).toBeDefined();
        expect(folder.attributes('draggable')).toBe('false');
    });

    it('opens a folder on a plain click and leaves modified clicks alone', async () => {
        const wrapper = tiles();
        const block = (e) => e.preventDefault();
        document.addEventListener('click', block);
        const link = wrapper.findAll('.c-tile-grid__name a')[0];

        await link.trigger('click');
        await link.trigger('click', { ctrlKey: true });
        document.removeEventListener('click', block);

        expect(wrapper.emitted('open')).toHaveLength(1);
    });

    it('passes selection, drag and drop through with the TileGrid vocabulary', async () => {
        const wrapper = tiles({ selectable: true, draggable: true, canDrop: (item) => item.type === 'folder' });
        const [folder] = wrapper.findAll('.c-tile-grid__tile');
        const setData = vi.fn();

        await folder.find('.c-tile-grid__check input').trigger('click', { shiftKey: true });
        await folder.trigger('dragstart', { dataTransfer: { setData, types: [] } });
        await folder.trigger('drop', { dataTransfer: { types: [] } });

        expect(wrapper.emitted('toggle-select')[0][1]).toEqual({ range: true });
        expect(wrapper.emitted('drag-start')).toHaveLength(1);
        expect(setData).toHaveBeenCalledWith('text/plain', 'folder:11');
        expect(wrapper.emitted('drop-on')).toHaveLength(1);
    });

    it('shows an upload as a document card with its progress', () => {
        const upload = { type: 'upload', id: 1, title: 'new.pdf', uploading: true, progress: 30, icon: 'file' };
        const tile = tiles({ items: [upload] }).find('.c-tile-grid__tile');

        expect(tile.classes()).toContain('is-uploading');
        expect(tile.find('.cfiles-tile__doc i.ti.ti-file').exists()).toBe(true);
        expect(tile.find('.c-tile-grid__meta').text()).toBe('30%');
        expect(tile.find('.c-tile-grid__name a').exists()).toBe(false);
    });

    it('opens the item menu where the right-click happened', async () => {
        const wrapper = tiles();
        expect(wrapper.vm.controls['file:21']).toBeDefined();
        const open = vi.spyOn(wrapper.vm.controls['file:21'], 'open');

        await wrapper.findAll('.c-tile-grid__tile')[1].trigger('contextmenu', { button: 2 });

        expect(open).toHaveBeenCalledTimes(1);
        expect(open.mock.calls[0][0]).toBeInstanceOf(MouseEvent);
        expect(open.mock.calls[0][0].button).toBe(2);
    });

    it('opens a keyboard-raised item menu under its toggle, not at the pointer', async () => {
        const wrapper = tiles();
        const open = vi.spyOn(wrapper.vm.controls['file:21'], 'open');

        await wrapper.findAll('.c-tile-grid__tile')[1].trigger('contextmenu', { button: 0 });

        expect(open).toHaveBeenCalledWith(null);
    });

    it('leaves a right-click inside an open item menu alone', async () => {
        const wrapper = tiles();
        const open = vi.spyOn(wrapper.vm.controls['file:21'], 'open');
        const tile = wrapper.findAll('.c-tile-grid__tile')[1];
        const menu = document.createElement('div');
        menu.className = 'dropdown-menu';
        const entry = document.createElement('a');
        menu.appendChild(entry);
        tile.element.appendChild(menu);

        entry.dispatchEvent(new MouseEvent('contextmenu', { bubbles: true, cancelable: true, button: 2 }));

        expect(open).not.toHaveBeenCalled();
    });

    it('renders the item menu toggle inline with a dots glyph', () => {
        const actions = tiles().findAll('.c-tile-grid__tile')[1].find('.c-tile-grid__actions');
        const root = actions.find('ul');

        expect(root.classes()).toContain('nav');
        expect(root.classes()).not.toContain('preferences');
        expect(actions.find('.cfiles-tile__toggle i.ti.ti-dots-vertical').exists()).toBe(true);
    });

    it('names the lock of a private item as an image', () => {
        const lock = tiles({ items: [fileRow({ visibility: 0 })] }).find('.c-tile-grid__name a i.ti-lock');

        expect(lock.attributes('role')).toBe('img');
        expect(lock.attributes('aria-label')).toBe('Private');
    });

    describe('the location of a hit', () => {
        const hit = () => fileRow({ parentFolderId: 8, path: [{ id: 7, title: 'Brand' }, { id: 8, title: 'Logos' }] });

        it('is a second meta line linking the folder the hit lies in', () => {
            const tile = tiles({ items: [hit()] }).find('.c-tile-grid__tile');
            const location = tile.find('.c-tile-grid__meta a.cfiles-location');

            expect(location.text()).toBe('in Brand › Logos');
            expect(location.attributes('href')).toBe('/b?fid=8');
            expect(location.attributes('draggable')).toBe('false');
            // The line is cut at the tile's width: the whole path stays readable on hover.
            expect(location.attributes('title')).toBe('in Brand › Logos');
        });

        it('is not there for an item directly in the open folder', () => {
            expect(tiles().find('.cfiles-location').exists()).toBe(false);
        });

        it('opens the folder on a plain click and leaves modified clicks alone', async () => {
            const wrapper = tiles({ items: [hit()] });
            const block = (e) => e.preventDefault();
            document.addEventListener('click', block);
            const location = wrapper.find('.cfiles-location');

            await location.trigger('click');
            await location.trigger('click', { metaKey: true });
            document.removeEventListener('click', block);

            expect(wrapper.emitted('open')).toHaveLength(1);
            expect(wrapper.emitted('open')[0][0]).toMatchObject({ type: 'folder', id: 8, title: 'Logos' });
        });
    });
});
