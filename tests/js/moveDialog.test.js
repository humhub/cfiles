import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { enableAutoUnmount, flushPromises, mount } from '@vue/test-utils';
import MoveDialog from '../../vue/browser/MoveDialog.vue';
import { CONTAINER_ID, fileRow, folderRow } from './support/fixtures.mjs';

enableAutoUnmount(afterEach);

const dialog = (props = {}) => mount(MoveDialog, {
    props: { show: true, contentContainerId: CONTAINER_ID, items: [fileRow()], ...props },
    attachTo: document.body,
});

const nodes = () => [...document.querySelectorAll('.cfiles-move-tree .list-group-item')].map((node) => node.textContent.trim());
const confirmButton = () => document.querySelector('.modal-footer .btn-primary');

describe('MoveDialog', () => {
    beforeEach(() => {
        globalThis.humhubStubs.client.get = vi.fn(() => Promise.resolve({
            results: [folderRow(), folderRow({ id: 12, title: 'Archiv', itemCount: 0 }), fileRow()],
        }));
    });

    it('lists the folders of the top level below it, not the files', async () => {
        dialog();
        await flushPromises();

        expect(nodes()).toEqual(['Files', 'Entwürfe', 'Archiv']);
    });

    it('offers no folder that is being moved as its own target', async () => {
        dialog({ items: [folderRow()] });
        await flushPromises();

        expect(nodes()).toEqual(['Files', 'Archiv']);
    });

    it('confirms the folder chosen, null for the top level', async () => {
        const wrapper = dialog();
        await flushPromises();

        // Opened at the top level, that is where it starts.
        expect(document.querySelector('.cfiles-move-tree [aria-selected="true"]').textContent.trim()).toBe('Files');
        document.querySelectorAll('.cfiles-move-tree .list-group-item')[2].click();
        await flushPromises();
        confirmButton().click();
        document.querySelectorAll('.cfiles-move-tree .list-group-item')[0].click();
        await flushPromises();
        confirmButton().click();

        expect(wrapper.emitted('confirm')).toEqual([[12], [null]]);
    });

    it('opens on the folder the items are in, with the path to it expanded', async () => {
        globalThis.humhubStubs.client.get = vi.fn((url) => Promise.resolve({
            results: url.includes('parentFolderId=11')
                ? [folderRow({ id: 31, title: 'Alt', itemCount: 0 })]
                : [folderRow(), folderRow({ id: 12, title: 'Archiv', itemCount: 0 })],
            page: 1,
            pages: 1,
        }));
        const wrapper = dialog({ currentPath: [{ id: 11, title: 'Entwürfe' }] });
        await flushPromises();

        expect(nodes()).toEqual(['Files', 'Entwürfe', 'Alt', 'Archiv']);
        expect(document.querySelector('.cfiles-move-tree [aria-selected="true"]').textContent.trim()).toBe('Entwürfe');
        confirmButton().click();
        expect(wrapper.emitted('confirm')).toEqual([[11]]);
    });

    it('loads every page of a folder\'s subfolders', async () => {
        globalThis.humhubStubs.client.get = vi.fn((url) => Promise.resolve(url.includes('page=2')
            ? { results: [folderRow({ id: 13, title: 'Zweite Seite', itemCount: 0 }), fileRow()], page: 2, pages: 2 }
            : { results: [folderRow({ id: 12, title: 'Archiv', itemCount: 0 })], page: 1, pages: 2 }));
        dialog();
        await flushPromises();

        expect(nodes()).toEqual(['Files', 'Archiv', 'Zweite Seite']);
    });

    describe('with the keyboard', () => {
        const focused = () => document.activeElement.textContent.trim();
        const press = async (key) => {
            document.activeElement.dispatchEvent(new KeyboardEvent('keydown', { key, bubbles: true }));
            await flushPromises();
        };

        it('is one tab stop, a tree whose nodes the arrow keys walk', async () => {
            dialog();
            await flushPromises();

            const tree = document.querySelector('.cfiles-move-tree');
            expect(tree.getAttribute('role')).toBe('tree');
            expect([...tree.querySelectorAll('[role="treeitem"]')].map((node) => node.tabIndex)).toEqual([0, -1, -1]);

            tree.querySelector('[role="treeitem"]').focus();
            await press('ArrowDown');
            expect(focused()).toBe('Entwürfe');
            await press('ArrowDown');
            expect(focused()).toBe('Archiv');
            await press('ArrowUp');
            await press('ArrowLeft');
            expect(focused()).toBe('Files');
        });

        it('expands with the right arrow and selects with Enter', async () => {
            globalThis.humhubStubs.client.get = vi.fn((url) => Promise.resolve({
                results: url.includes('parentFolderId=11') ? [folderRow({ id: 31, title: 'Alt', itemCount: 0 })] : [folderRow()],
                page: 1,
                pages: 1,
            }));
            const wrapper = dialog();
            await flushPromises();

            document.querySelectorAll('.cfiles-move-tree [role="treeitem"]')[1].focus();
            await press('ArrowRight');
            expect(nodes()).toEqual(['Files', 'Entwürfe', 'Alt']);
            expect(document.activeElement.getAttribute('aria-expanded')).toBe('true');

            await press('ArrowRight');
            expect(focused()).toBe('Alt');
            await press('Enter');
            confirmButton().click();

            expect(wrapper.emitted('confirm')).toEqual([[31]]);
        });
    });

    it('names a move a move and a copy a copy', async () => {
        const wrapper = dialog();
        await flushPromises();
        expect(confirmButton().textContent.trim()).toBe('Move');

        await wrapper.setProps({ mode: 'copy' });
        expect(confirmButton().textContent.trim()).toBe('Copy');
        expect(document.body.textContent).toContain('Choose the folder to copy the selection into.');
    });
});
