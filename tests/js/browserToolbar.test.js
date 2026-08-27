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
            expect(toolbar().findAll('button').length).toBeGreaterThanOrEqual(2);
        });

        it('offers neither when the caller may not', () => {
            expect(toolbar({ canWrite: false }).findAll('button')).toHaveLength(0);
        });

        it('shows the bulk actions only while something is selected', () => {
            expect(toolbar().find('.cfiles-selection').exists()).toBe(false);
            expect(toolbar({ selectionCount: 2 }).find('.cfiles-selection').exists()).toBe(true);
        });
    });
});
