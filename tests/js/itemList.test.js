import { describe, expect, it, vi } from 'vitest';
import { mount } from '@vue/test-utils';
import ItemList from '../../vue/browser/ItemList.vue';
import ContentControls from '@core/modules/content/vue/ContentControls.vue';
import { fileRow, folderRow } from './support/fixtures.mjs';

const items = [folderRow(), fileRow()];

const list = (props = {}) => mount(ItemList, {
    props: {
        items,
        entriesFor: () => [],
        folderUrl: (id) => '/b?fid=' + (id || 0),
        ...props,
    },
});

describe('ItemList', () => {
    describe('display', () => {
        it('renders rows in the platform list container by default', () => {
            const wrapper = list();

            expect(wrapper.find('.hh-list.cfiles-list').exists()).toBe(true);
            expect(wrapper.findAll('.cfiles-row')).toHaveLength(2);
            expect(wrapper.find('.cfiles-tile').exists()).toBe(false);
        });

        // `.hh-list` styles its own direct children, which is right for rows and wrong for a
        // grid — the container has to change with the shape.
        it('renders tiles in a grid container instead', () => {
            const wrapper = list({ view: 'tiles' });

            expect(wrapper.find('.cfiles-tiles').exists()).toBe(true);
            expect(wrapper.find('.hh-list').exists()).toBe(false);
            expect(wrapper.findAll('.cfiles-tile')).toHaveLength(2);
        });

        it('shows the same items either way', () => {
            const titles = (view) => list({ view }).findAll('a[href="/b?fid=11"]').length > 0;

            expect(titles('list')).toBe(true);
            expect(titles('tiles')).toBe(true);
        });

        it('shows the empty state instead of a container when there is nothing', () => {
            const wrapper = list({ items: [] });

            expect(wrapper.find('.cfiles-empty').exists()).toBe(true);
            expect(wrapper.find('.cfiles-list').exists()).toBe(false);
        });

        it('offers to load more only while there is more', () => {
            expect(list().find('button').exists()).toBe(false);
            expect(list({ hasMore: true }).find('button').text()).toBe('Show more');
        });
    });

    // The row's own right-click behaviour is covered in itemRow.test.js; what matters here is
    // that a tile has it too, since the tile is the only thing the grid renders.
    describe('right-click in the tiles view', () => {
        it('raises the tile\'s own menu at the cursor', () => {
            const wrapper = list({ view: 'tiles' });
            const open = vi.spyOn(wrapper.findComponent(ContentControls).vm, 'open')
                .mockImplementation(() => {});

            const event = new MouseEvent('contextmenu', {
                bubbles: true, cancelable: true, clientX: 15, clientY: 25,
            });
            wrapper.find('.cfiles-tile').element.dispatchEvent(event);

            expect(open.mock.calls[0][0].clientX).toBe(15);
            expect(event.defaultPrevented).toBe(true);
        });
    });

    describe('select all', () => {
        const box = (wrapper) => wrapper.find('.cfiles-list-header input[type="checkbox"]');

        it('appears only where selecting is possible and there is something to select', () => {
            expect(box(list({ selectable: true })).exists()).toBe(true);
            expect(box(list()).exists()).toBe(false);
            expect(box(list({ selectable: true, items: [] })).exists()).toBe(false);
        });

        it('is unchecked with nothing selected', () => {
            const wrapper = list({ selectable: true });

            expect(box(wrapper).element.checked).toBe(false);
            expect(box(wrapper).element.indeterminate).toBe(false);
        });

        // Never claims more than it did: half a selection reads as half, not as all.
        it('is indeterminate with some selected', async () => {
            const wrapper = list({ selectable: true, selection: ['folder:11'] });
            await wrapper.vm.$nextTick();

            expect(box(wrapper).element.checked).toBe(false);
            expect(box(wrapper).element.indeterminate).toBe(true);
        });

        it('is checked with all selected', async () => {
            const wrapper = list({ selectable: true, selection: ['folder:11', 'file:21'] });
            await wrapper.vm.$nextTick();

            expect(box(wrapper).element.checked).toBe(true);
            expect(box(wrapper).element.indeterminate).toBe(false);
        });

        it('emits a single toggle rather than deciding itself', async () => {
            const wrapper = list({ selectable: true });
            await box(wrapper).setValue(true);

            expect(wrapper.emitted('toggle-all')).toHaveLength(1);
        });
    });

    describe('bulk actions', () => {
        it('appear only while something is selected', () => {
            expect(list({ selectable: true }).find('.btn-danger').exists()).toBe(false);
            expect(list({ selectable: true, selection: ['file:21'] }).find('.btn-danger').exists()).toBe(true);
        });

        it('emit rather than act', async () => {
            const wrapper = list({ selectable: true, selection: ['file:21'] });

            await wrapper.find('.btn-light').trigger('click');
            await wrapper.find('.btn-danger').trigger('click');

            expect(wrapper.emitted('move-selection')).toHaveLength(1);
            expect(wrapper.emitted('delete-selection')).toHaveLength(1);
        });

        it('says how many are selected', () => {
            const wrapper = list({ selectable: true, selection: ['file:21'] });

            expect(wrapper.find('.cfiles-list-header').text()).toContain('1 selected');
        });
    });
});
