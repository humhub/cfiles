import { afterEach, describe, expect, it } from 'vitest';
import { enableAutoUnmount, mount } from '@vue/test-utils';
import { nextTick } from 'vue';
import AddMenu from '../../vue/browser/AddMenu.vue';

enableAutoUnmount(afterEach);

const menu = (props = {}) => mount(AddMenu, { props, attachTo: document.body });

const handlersHtml = '<li><a class="dropdown-item" href="#" data-action-click="x">New spreadsheet</a></li>';

describe('AddMenu', () => {
    it('is an accent icon button labelled for what it adds', () => {
        const toggle = menu().find('[data-bs-toggle="dropdown"]');

        expect(toggle.classes()).toEqual(expect.arrayContaining(['btn', 'btn-accent', 'c-icon-button']));
        expect(toggle.classes()).not.toContain('dropdown-toggle');
        expect(toggle.attributes('aria-label')).toBe('Add');
        expect(toggle.find('i.ti.ti-plus').exists()).toBe(true);
    });

    it('offers upload and a new folder', async () => {
        const wrapper = menu();
        const [upload, folder] = wrapper.findAll('.cfiles-add-menu__entry');

        expect(upload.text()).toBe('Upload files');
        expect(folder.text()).toBe('New folder');

        await upload.trigger('click');
        await folder.trigger('click');
        expect(wrapper.emitted('upload')).toHaveLength(1);
        expect(wrapper.emitted('create-folder')).toHaveLength(1);
    });

    it('appends the handlers a module contributed after a divider, in the same menu', () => {
        const wrapper = menu({ handlersHtml });
        const list = wrapper.find('.dropdown-menu');

        expect(list.find('hr.dropdown-divider').exists()).toBe(true);
        expect(list.find('[data-action-click="x"]').text()).toBe('New spreadsheet');
    });

    it('shows no divider without handlers', () => {
        expect(menu().find('hr.dropdown-divider').exists()).toBe(false);
    });

    it('lists the built-in entries before the handlers, all selectable as Bootstrap dropdown items', () => {
        const wrapper = menu({ handlersHtml });
        const items = wrapper.findAll('.dropdown-menu .dropdown-item');

        expect(items.map((item) => item.text())).toEqual(['Upload files', 'New folder', 'New spreadsheet']);
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
