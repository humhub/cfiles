import { describe, expect, it, vi } from 'vitest';
import { copyItems, deleteItems, findConflicts, moveItems } from '../../vue/browser/api';

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
        expect(post.mock.calls.every(([, { data }]) => data.containerId === 7 && data.parentFolderId === 12)).toBe(true);
    });

    it('copies every batch to the same target', async () => {
        const post = carryOut();
        globalThis.humhubStubs.client.post = post;

        await copyItems(7, items(201), null);

        expect(post.mock.calls.map(([url]) => url).every((url) => url.includes('cfiles/items/copy'))).toBe(true);
        expect(sizes(post)).toEqual([200, 1]);
        expect(post.mock.calls.every(([, { data }]) => data.containerId === 7 && data.parentFolderId === null)).toBe(true);
    });

    it('keeps the errors of a batch that carried nothing out when another one did', async () => {
        const refused = { type: 'file', id: 201, title: 'x.txt', messages: ['You cannot delete this.'] };
        globalThis.humhubStubs.client.post = vi.fn((url, { data }) => Promise.resolve(data.items.length === 200
            ? { results: data.items, errors: [] }
            : { results: [], errors: [refused] }));

        const answer = await deleteItems(items(201));

        expect(answer.results).toHaveLength(200);
        expect(answer.errors).toEqual([refused]);
    });

    // The server answers 200 with the errors per item also when it carried out none of them.
    it('fails with the errors of all items when no batch carried anything out', async () => {
        const errors = [{ type: 'file', id: 1, messages: ['Nope'] }];
        globalThis.humhubStubs.client.post = vi.fn(() => Promise.resolve({ results: [], errors }));

        await expect(deleteItems(items(3))).rejects.toEqual({ errors });
    });

    it('fails like the request when the server refused it as a whole', async () => {
        const refusal = { status: 422, errors: { items: ['items cannot be blank.'] } };
        globalThis.humhubStubs.client.post = vi.fn(() => Promise.reject(refusal));

        await expect(deleteItems(items(3))).rejects.toBe(refusal);
    });

    it('fails when a batch failed without saying which items, even if another one went through', async () => {
        const broken = { status: 500 };
        globalThis.humhubStubs.client.post = vi.fn((url, { data }) => (data.items.length === 200
            ? Promise.resolve({ results: data.items, errors: [] })
            : Promise.reject(broken)));

        await expect(deleteItems(items(201))).rejects.toBe(broken);
    });
});

describe('the taken names of a level', () => {
    it('asks in batches small enough for a URL and answers all conflicts', async () => {
        const get = vi.fn((url) => Promise.resolve({ conflicts: [{ name: new URL(url, 'http://x').searchParams.getAll('names[]')[0] }] }));
        globalThis.humhubStubs.client.get = get;
        const names = Array.from({ length: 120 }, (_, index) => 'file-' + index + '.txt');

        const conflicts = await findConflicts(5, 9, names);

        expect(get).toHaveBeenCalledTimes(3);
        expect(conflicts.map((conflict) => conflict.name)).toEqual(['file-0.txt', 'file-50.txt', 'file-100.txt']);
    });

    it('takes an answer without conflicts as none', async () => {
        globalThis.humhubStubs.client.get = vi.fn(() => Promise.resolve({}));

        expect(await findConflicts(5, null, ['a.txt'])).toEqual([]);
    });
});
