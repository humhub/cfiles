import { describe, expect, it } from 'vitest';
import { mount } from '@vue/test-utils';
import BrowserToolbar from '../../vue/browser/BrowserToolbar.vue';

const path = [
    { id: 7, title: 'test123', url: '?fid=7' },
    { id: 9, title: 'sgadgasdg', url: '?fid=9' },
];

const toolbar = (props = {}) => mount(BrowserToolbar, {
    props: {
        path,
        canWrite: true,
        folderUrl: (id) => '/b?fid=' + (id || 0),
        ...props,
    },
});

const crumbs = (wrapper) => wrapper.findAll('.breadcrumb-item').map((c) => c.text());
const marked = (wrapper) => wrapper.findAll('.breadcrumb-item')
    .filter((c) => c.classes().includes('cfiles-crumb-drop'))
    .map((c) => c.text());

describe('BrowserToolbar', () => {
    describe('breadcrumb', () => {
        it('puts the container top level in front of the path the API sent', () => {
            expect(crumbs(toolbar())).toEqual(['Files', 'test123', 'sgadgasdg']);
        });

        it('shows only the top level when the path is empty', () => {
            expect(crumbs(toolbar({ path: [] }))).toEqual(['Files']);
        });

        it('links every crumb but the current one', () => {
            const wrapper = toolbar();
            const links = wrapper.findAll('.breadcrumb-item a');

            expect(links.map((a) => a.attributes('href'))).toEqual(['/b?fid=0', '/b?fid=7']);
        });
    });

    // The top-level crumb's own id is null, so "nothing is being dragged over" must NOT be
    // null too — that comparison was permanently true once and painted the crumb as a drop
    // target at all times.
    describe('drop target marking', () => {
        it('marks nothing while no drag is in progress', () => {
            expect(marked(toolbar())).toEqual([]);
        });

        it('marks the top level when it is the target', () => {
            expect(marked(toolbar({ dropTargetId: null }))).toEqual(['Files']);
        });

        it('marks an intermediate crumb when it is the target', () => {
            expect(marked(toolbar({ dropTargetId: 7 }))).toEqual(['test123']);
        });
    });

    describe('sort control', () => {
        it('names the column in force and which way it points', () => {
            const toggle = toolbar({ sort: 'updatedAt', order: 'desc' })
                .find('a[data-bs-toggle="dropdown"]');

            expect(toggle.text()).toBe('Updated ↓');
        });

        it('is not a nav-pills menu, which the theme styles as navigation chrome', () => {
            const menu = toolbar().find('.dropdown-menu').element.closest('ul[class]');

            expect(menu.className).not.toContain('nav-pills');
            expect(menu.className).not.toContain('preferences');
        });

        it('emits the column that was picked', async () => {
            const wrapper = toolbar();
            await wrapper.findAll('.dropdown-item')[1].trigger('click');

            expect(wrapper.emitted('sort')).toEqual([['size']]);
        });
    });

    describe('write actions', () => {
        it('offers creating and uploading when the caller may write', () => {
            const wrapper = toolbar();

            expect(wrapper.find('button .fa-folder').exists()).toBe(true);
            expect(wrapper.find('button .fa-upload').exists()).toBe(true);
        });

        it('offers neither when the caller may not', () => {
            const wrapper = toolbar({ canWrite: false });

            expect(wrapper.find('button .fa-folder').exists()).toBe(false);
            expect(wrapper.find('button .fa-upload').exists()).toBe(false);
        });
    });

    // File handlers a module contributed ("new spreadsheet", "import from …") stay
    // server-rendered: they carry legacy data-action-click attributes and build their own URLs.
    describe('file handlers', () => {
        it('offers no split toggle when no module contributed a handler', () => {
            expect(toolbar().find('.dropdown-toggle-split').exists()).toBe(false);
        });

        it('offers the handlers beside the upload button when one did', () => {
            const wrapper = toolbar({
                createHandlersHtml: '<li><a class="dropdown-item" data-action-click="x">New sheet</a></li>',
            });

            expect(wrapper.find('.dropdown-toggle-split').exists()).toBe(true);
            expect(wrapper.find('.cfiles-add-files .dropdown-menu a').text()).toBe('New sheet');
        });

        it('hides them from the caller who may not write at all', () => {
            const wrapper = toolbar({
                canWrite: false,
                createHandlersHtml: '<li><a class="dropdown-item">New sheet</a></li>',
            });

            expect(wrapper.find('.dropdown-toggle-split').exists()).toBe(false);
        });
    });

    // Two displays of the same items; the toolbar only says which one is in force.
    describe('view switch', () => {
        const viewButtons = (wrapper) => wrapper.findAll('.cfiles-view-switch button');

        it('offers a list and a tile display', () => {
            expect(viewButtons(toolbar())).toHaveLength(2);
        });

        it('marks the display in force, for the eye and for a screen reader', () => {
            const buttons = viewButtons(toolbar({ view: 'tiles' }));

            expect(buttons[0].classes()).not.toContain('active');
            expect(buttons[0].attributes('aria-pressed')).toBe('false');
            expect(buttons[1].classes()).toContain('active');
            expect(buttons[1].attributes('aria-pressed')).toBe('true');
        });

        it('emits the display that was picked', async () => {
            const wrapper = toolbar({ view: 'list' });
            await viewButtons(wrapper)[1].trigger('click');

            expect(wrapper.emitted('view')).toEqual([['tiles']]);
        });

        it('names each display for a screen reader, since the buttons are icons only', () => {
            const buttons = viewButtons(toolbar());

            expect(buttons.map((b) => b.attributes('aria-label'))).toEqual(['List', 'Tiles']);
        });
    });
});
