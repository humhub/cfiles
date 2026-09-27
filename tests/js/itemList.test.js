import { afterEach, describe, expect, it, vi } from 'vitest';
import { enableAutoUnmount, mount } from '@vue/test-utils';
import ItemList from '../../vue/browser/ItemList.vue';
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
    enableAutoUnmount(afterEach);

    describe('display', () => {
        it('renders rows in the platform list container', () => {
            const wrapper = list();

            expect(wrapper.find('.hh-list.cfiles-list').exists()).toBe(true);
            expect(wrapper.findAll('.cfiles-row')).toHaveLength(2);
        });

        it('shows the empty state instead of a container when there is nothing', () => {
            const wrapper = list({ items: [] });

            expect(wrapper.find('.cfiles-empty').exists()).toBe(true);
            expect(wrapper.find('.cfiles-list').exists()).toBe(false);
            expect(wrapper.find('.cfiles-empty').text()).toContain('This folder is empty.');
        });

        it('lets the owner replace the empty state', () => {
            const wrapper = mount(ItemList, {
                props: { items: [], entriesFor: () => [], folderUrl: () => '/b' },
                slots: { empty: '<p class="own-empty">Nothing here</p>' },
            });

            expect(wrapper.find('.own-empty').exists()).toBe(true);
            expect(wrapper.text()).not.toContain('This folder is empty.');
        });

        it('offers to load more only while there is more', async () => {
            expect(list().find('button').exists()).toBe(false);

            const wrapper = list({ hasMore: true });
            expect(wrapper.find('button').text()).toBe('Show more');
            await wrapper.find('button').trigger('click');
            expect(wrapper.emitted('load-more')).toHaveLength(1);
        });

        // The selection header moved to the browser's SelectionMenu.
        it('renders no select-all header of its own', () => {
            const wrapper = list({ selectable: true, selection: ['file:21'] });

            expect(wrapper.find('.cfiles-list-header').exists()).toBe(false);
            expect(wrapper.findAll('input[type="checkbox"]')).toHaveLength(2);
        });
    });

    describe('the like link', () => {
        it('gives the rows what they need', () => {
            const props = { likeStates: { 1201: { total: 1, liked: false, canLike: true } } };

            expect(list(props).find('.likeLinkContainer').exists()).toBe(true);
        });
    });

    describe('events', () => {
        it('passes open through with the item', async () => {
            const wrapper = list();

            await wrapper.findAll('.cfiles-row')[0].trigger('click');

            expect(wrapper.emitted('open')[0][0].id).toBe(11);
        });

        it('passes toggle-select with the range flag', async () => {
            const wrapper = list({ selectable: true });

            await wrapper.findAll('input[type="checkbox"]')[1].trigger('click', { shiftKey: true });
            await wrapper.findAll('input[type="checkbox"]')[0].trigger('click');

            expect(wrapper.emitted('toggle-select')[0][0].id).toBe(21);
            expect(wrapper.emitted('toggle-select')[0][1]).toEqual({ range: true });
            expect(wrapper.emitted('toggle-select')[1][1]).toEqual({ range: false });
        });

        it('passes drag-start and drag-end with the item and the event', async () => {
            const wrapper = list({ draggable: true });
            const row = wrapper.findAll('.cfiles-row')[1];

            await row.trigger('dragstart', { dataTransfer: { setData: vi.fn() } });
            await row.trigger('dragend');

            const [startItem, startEvent] = wrapper.emitted('drag-start')[0];
            expect(startItem.id).toBe(21);
            expect(startEvent).toBeInstanceOf(Event);
            const [endItem, endEvent] = wrapper.emitted('drag-end')[0];
            expect(endItem.id).toBe(21);
            expect(endEvent).toBeInstanceOf(Event);
        });

        it('takes a drop only where canDrop allows it, and stops it', async () => {
            const outer = vi.fn();
            const host = document.createElement('div');
            host.addEventListener('drop', outer);
            document.body.appendChild(host);
            try {
                const wrapper = mount(ItemList, {
                    props: {
                        items,
                        entriesFor: () => [],
                        folderUrl: (id) => '/b?fid=' + (id || 0),
                        canDrop: (item) => item.type === 'folder',
                    },
                    attachTo: host,
                });
                const [folder, file] = wrapper.findAll('.cfiles-row');

                file.element.dispatchEvent(new Event('drop', { bubbles: true, cancelable: true }));
                expect(wrapper.emitted('drop-on')).toBeFalsy();
                expect(outer).toHaveBeenCalledTimes(1);

                const drop = new Event('drop', { bubbles: true, cancelable: true });
                folder.element.dispatchEvent(drop);

                const [item, event] = wrapper.emitted('drop-on')[0];
                expect(item.id).toBe(11);
                expect(event).toBe(drop);
                expect(drop.defaultPrevented).toBe(true);
                expect(outer).toHaveBeenCalledTimes(1);
            } finally {
                host.remove();
            }
        });

        it('announces a drag over a row only where canDrop allows it', async () => {
            const wrapper = list({ canDrop: (item) => item.type === 'folder' });
            const [folder, file] = wrapper.findAll('.cfiles-row');

            const overFile = new Event('dragover', { bubbles: true, cancelable: true });
            file.element.dispatchEvent(overFile);
            const overFolder = new Event('dragover', { bubbles: true, cancelable: true });
            folder.element.dispatchEvent(overFolder);

            expect(overFile.defaultPrevented).toBe(false);
            expect(overFolder.defaultPrevented).toBe(true);
            expect(wrapper.emitted('drag-over')).toHaveLength(1);
            expect(wrapper.emitted('drag-over')[0][0].id).toBe(11);
            expect(wrapper.emitted('drag-over')[0][1]).toBe(overFolder);
        });

        it('emits drag-leave only when leaving the row', () => {
            const wrapper = list();
            const folder = wrapper.findAll('.cfiles-row')[0];
            const leave = (relatedTarget) => {
                const event = new MouseEvent('dragleave', { bubbles: true, relatedTarget });
                folder.element.dispatchEvent(event);
            };

            leave(folder.find('h4 a').element);
            expect(wrapper.emitted('drag-leave')).toBeFalsy();

            leave(document.body);
            expect(wrapper.emitted('drag-leave')).toHaveLength(1);
            expect(wrapper.emitted('drag-leave')[0][0].id).toBe(11);
        });

        it('marks the drop target and the selected rows', () => {
            const wrapper = list({ dropTargetKey: 'folder:11', selection: ['file:21'] });
            const [folder, file] = wrapper.findAll('.cfiles-row');

            expect(folder.classes()).toContain('is-drop-target');
            expect(file.classes()).not.toContain('is-drop-target');
            expect(file.classes()).toContain('is-selected');
        });
    });

    describe('uploads', () => {
        const upload = { type: 'upload', id: 1, title: 'new.pdf', uploading: true, progress: 30, icon: 'file' };

        it('shows an upload as a row with its progress', () => {
            const wrapper = list({ items: [upload, fileRow()], selectable: true, draggable: true });
            const row = wrapper.find('.cfiles-row.is-uploading');

            expect(row.exists()).toBe(true);
            expect(row.text()).toContain('new.pdf');
            expect(row.find('.ti.ti-file').exists()).toBe(true);
            const bar = row.find('.progress-bar');
            expect(bar.attributes('aria-valuenow')).toBe('30');
            expect(bar.attributes('style')).toContain('width: 30%');
            expect(bar.attributes('aria-label')).toBe('Uploading...');
        });

        it('offers no checkbox, menu, like link or drag on an upload', () => {
            const wrapper = list({
                items: [upload],
                selectable: true,
                draggable: true,
                likeStates: { undefined: { total: 1, liked: false, canLike: true } },
            });
            const row = wrapper.find('.cfiles-row');

            expect(row.find('input[type="checkbox"]').exists()).toBe(false);
            expect(row.find('.cfiles-row-controls').exists()).toBe(false);
            expect(row.find('.likeLinkContainer').exists()).toBe(false);
            expect(row.attributes('draggable')).toBe('false');
        });
    });
});
