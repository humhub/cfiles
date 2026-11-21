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

    it('names a move a move and a copy a copy', async () => {
        const wrapper = dialog();
        await flushPromises();
        expect(confirmButton().textContent.trim()).toBe('Move');

        await wrapper.setProps({ mode: 'copy' });
        expect(confirmButton().textContent.trim()).toBe('Copy');
        expect(document.body.textContent).toContain('Choose the folder to copy the selection into.');
    });
});
