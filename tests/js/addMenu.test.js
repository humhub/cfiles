import { afterEach, describe, expect, it } from 'vitest';
import { enableAutoUnmount, mount } from '@vue/test-utils';
import { nextTick } from 'vue';
import AddMenu from '../../vue/browser/AddMenu.vue';

enableAutoUnmount(afterEach);

const menu = (props = {}) => mount(AddMenu, { props, attachTo: document.body });

const handlersHtml = '<li><a class="dropdown-item" href="#" data-action-click="x">New spreadsheet</a></li>';

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
        const wrapper = menu({ handlersHtml });
        const toggle = wrapper.find('[data-bs-toggle="dropdown"]');

        expect(wrapper.find('.cfiles-add-upload').exists()).toBe(false);
        expect(wrapper.find('.cfiles-add-folder').exists()).toBe(true);
        expect(toggle.classes()).toEqual(expect.arrayContaining(['btn', 'btn-accent', 'btn-icon-only']));
        expect(toggle.classes()).not.toContain('dropdown-toggle');
        expect(toggle.attributes('aria-label')).toBe('Add');
        expect(toggle.find('i.ti.ti-plus').exists()).toBe(true);
    });

    it('lists the upload, a divider, then the handlers, all selectable as Bootstrap dropdown items', async () => {
        const wrapper = menu({ handlersHtml });
        const list = wrapper.find('.dropdown-menu');

        expect(list.find('hr.dropdown-divider').exists()).toBe(true);
        expect(list.findAll('.dropdown-item').map((item) => item.text())).toEqual(['Upload files', 'New spreadsheet']);

        await list.find('.cfiles-add-menu-entry').trigger('click');
        expect(wrapper.emitted('upload')).toHaveLength(1);
    });

    it('runs the legacy ui additions over the handler markup, same as the old toolbar did', async () => {
        let enhanced = 0;
        globalThis.humhubStubs.additions.register('test-add-menu-handler', '[data-action-click="add-menu-enhance"]', () => {
            enhanced++;
        });
        expect(enhanced).toBe(0);

        menu({ handlersHtml: '<li><a class="dropdown-item" href="#" data-action-click="add-menu-enhance">New spreadsheet</a></li>' });
        await nextTick();

        expect(enhanced).toBe(1);
    });
});
