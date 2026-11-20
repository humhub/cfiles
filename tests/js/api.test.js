import { describe, expect, it, vi } from 'vitest';
import { deleteItems, moveItems } from '../../vue/browser/api';

const items = (count) => Array.from({ length: count }, (_, index) => ({ type: 'file', id: index + 1 }));

/** `client.post` carrying out every item it is sent, as the server does. */
const carryOut = () => vi.fn((url, { data }) => Promise.resolve({ results: data.items, errors: [] }));

const sizes = (post) => post.mock.calls.map(([, { data }]) => data.items.length);

// The server refuses more than ItemController::MAX_ITEMS (200) items per request as a whole.
describe('moving and deleting many items', () => {
    it('deletes in requests of at most 200 items and answers like one request', async () => {
        const post = carryOut();
        globalThis.humhubStubs.client.post = post;

        const answer = await deleteItems(items(450));

        expect(sizes(post)).toEqual([200, 200, 50]);
        expect(answer.results).toHaveLength(450);
        expect(answer.errors).toEqual([]);
    });

    it('moves every batch to the same target', async () => {
        const post = carryOut();
        globalThis.humhubStubs.client.post = post;

        await moveItems(7, items(201), 12);

        expect(sizes(post)).toEqual([200, 1]);
        expect(post.mock.calls.every(([, { data }]) => data.containerId === 7 && data.targetFolderId === 12)).toBe(true);
    });

    it('keeps the errors of a batch that carried nothing out when another one did', async () => {
        const refused = { type: 'file', id: 201, title: 'x.txt', message: 'You cannot delete this.' };
        globalThis.humhubStubs.client.post = vi.fn((url, { data }) => (data.items.length === 200
            ? Promise.resolve({ results: data.items, errors: [] })
            : Promise.reject({ errors: [refused] })));

        const answer = await deleteItems(items(201));

        expect(answer.results).toHaveLength(200);
        expect(answer.errors).toEqual([refused]);
    });

    it('fails like one request when no batch carried anything out', async () => {
        const failure = { errors: [{ type: 'file', id: 1, message: 'Nope' }] };
        globalThis.humhubStubs.client.post = vi.fn(() => Promise.reject(failure));

        await expect(deleteItems(items(3))).rejects.toBe(failure);
    });

    it('fails when a batch failed without saying which items, even if another one went through', async () => {
        const broken = { status: 500 };
        globalThis.humhubStubs.client.post = vi.fn((url, { data }) => (data.items.length === 200
            ? Promise.resolve({ results: data.items, errors: [] })
            : Promise.reject(broken)));

        await expect(deleteItems(items(201))).rejects.toBe(broken);
    });
});
