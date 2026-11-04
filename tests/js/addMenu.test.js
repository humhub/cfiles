import { afterEach, describe, expect, it } from 'vitest';
import { enableAutoUnmount, mount } from '@vue/test-utils';
import AddMenu from '../../vue/browser/AddMenu.vue';

enableAutoUnmount(afterEach);

const menu = (props = {}) => mount(AddMenu, { props, attachTo: document.body });

const handlers = [{ label: 'New spreadsheet', icon: 'table', attributes: { 'data-action-click': 'x' } }];
const imageHandler = { label: 'Attach an image', icon: 'camera', attributes: { 'data-action-click': 'file.uploadByType', 'data-action-params': '{"type":"image/*"}' } };

describe('AddMenu', () => {
    it('offers a new folder and an upload as icon buttons of their own', async () => {
        const wrapper = menu();
        const folder = wrapper.find('.cfiles-add-folder');
        const upload = wrapper.find('.cfiles-add-upload');

        expect(folder.classes()).toEqual(expect.arrayContaining(['btn', 'btn-light', 'btn-icon-only']));
        expect(folder.attributes('aria-label')).toBe('New folder');
        expect(folder.find('i.ti.ti-folder-plus').exists()).toBe(true);
        expect(upload.classes()).toEqual(expect.arrayContaining(['btn', 'btn-accent', 'btn-icon-only']));
        expect(upload.attributes('aria-label')).toBe('Upload files');
        expect(upload.find('i.ti.ti-upload').exists()).toBe(true);
        expect(wrapper.find('[data-bs-toggle="dropdown"]').exists()).toBe(false);

        await folder.trigger('click');
        await upload.trigger('click');
        expect(wrapper.emitted('create-folder')).toHaveLength(1);
        expect(wrapper.emitted('upload')).toHaveLength(1);
    });

    it('makes the upload an "add" menu where a module contributed handlers', () => {
        const wrapper = menu({ handlers });
        const toggle = wrapper.find('[data-bs-toggle="dropdown"]');

        expect(wrapper.find('.cfiles-add-upload').exists()).toBe(false);
        expect(wrapper.find('.cfiles-add-folder').exists()).toBe(true);
        expect(toggle.classes()).toEqual(expect.arrayContaining(['btn', 'btn-accent', 'btn-icon-only']));
        expect(toggle.classes()).not.toContain('dropdown-toggle');
        expect(toggle.attributes('aria-label')).toBe('Add');
        expect(toggle.find('i.ti.ti-plus').exists()).toBe(true);
    });

    it('lists the upload, a divider, then the handlers, all selectable as Bootstrap dropdown items', async () => {
        const wrapper = menu({ handlers });
        const list = wrapper.find('.dropdown-menu');

        expect(list.find('hr.dropdown-divider').exists()).toBe(true);
        expect(list.findAll('.dropdown-item').map((item) => item.text())).toEqual(['Upload files', 'New spreadsheet']);

        await list.find('.cfiles-add-menu-entry').trigger('click');
        expect(wrapper.emitted('upload')).toHaveLength(1);
    });

    it('renders a handler as a link carrying its attributes, and reports a module\'s choice', async () => {
        const wrapper = menu({ handlers });
        const link = wrapper.find('[data-action-click="x"]');

        expect(link.attributes('data-action-process')).toBe('file-handler');
        expect(link.find('i.dropdown-item-icon.ti.ti-table').exists()).toBe(true);

        await link.trigger('click');
        expect(wrapper.emitted('handler')).toHaveLength(1);
    });

    // The legacy `file.uploadByType` action looks for an upload button this page has not got
    it('asks for the picker with its type for the platform\'s "upload this type" entries', async () => {
        const delegate = (event) => { event.legacyReached = true; };
        document.addEventListener('click', delegate);
        const wrapper = menu({ handlers: [imageHandler] });

        const click = new MouseEvent('click', { bubbles: true, cancelable: true });
        wrapper.find('[data-action-click="file.uploadByType"]').element.dispatchEvent(click);
        document.removeEventListener('click', delegate);

        expect(wrapper.emitted('upload')).toEqual([['image/*']]);
        expect(wrapper.emitted('handler')).toBeUndefined();
        expect(click.legacyReached).toBeUndefined();
        expect(click.defaultPrevented).toBe(true);
    });
});
