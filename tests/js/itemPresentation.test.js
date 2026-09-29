import { describe, expect, it } from 'vitest';
import { fileRow, folderRow } from './support/fixtures.mjs';
import { itemLocation, itemMeta, tileMeta } from '../../vue/browser/itemPresentation';

describe('tileMeta', () => {
    it('reads "empty" for a folder with no items', () => {
        expect(tileMeta(folderRow({ itemCount: 0 }))).toBe('empty');
    });

    it('reads the singular for one item', () => {
        expect(tileMeta(folderRow({ itemCount: 1 }))).toBe('1 item');
    });

    it('reads the plural for several items', () => {
        expect(tileMeta(folderRow({ itemCount: 4 }))).toBe('4 items');
    });

    /**
     * A folder payload outside a listing (e.g. the response to creating or renaming one) has
     * no count at all — see FolderSerializer::folder(). There is nothing honest to show then.
     */
    it('is empty when the item count is unknown', () => {
        expect(tileMeta(folderRow({ itemCount: null }))).toBe('');
    });

    it('reads a file\'s size', () => {
        expect(tileMeta(fileRow({ size: 1258291 }))).toBe('1.2 MB');
    });
});

describe('itemLocation', () => {
    const space = { type: 'space', id: 3, contentContainerId: 7, guid: 's-3', title: 'Marketing' };

    it('is null for an item directly in the open folder', () => {
        expect(itemLocation(fileRow())).toBeNull();
    });

    it('names the folders of a path without a space', () => {
        const location = itemLocation(fileRow({ path: [{ id: 7, title: 'Brand' }, { id: 8, title: 'Logos' }] }));

        expect(location.space).toBeNull();
        expect(location.folder).toEqual({ type: 'folder', id: 8, title: 'Logos' });
        expect(location.label).toBe('in Brand › Logos');
    });

    it('reads the space of a prefixed path apart from its folders', () => {
        const location = itemLocation(fileRow({
            path: [space, { type: 'folder', id: 7, title: 'Brand' }, { type: 'folder', id: 8, title: 'Logos' }],
        }));

        expect(location.space).toEqual(space);
        expect(location.folder).toEqual({ type: 'folder', id: 8, title: 'Logos', space });
        expect(location.folderLabel).toBe('Brand › Logos');
        expect(location.label).toBe('in Marketing › Brand › Logos');
    });

    it('names only the space for an item at the top level of its space', () => {
        const location = itemLocation(fileRow({ path: [space] }));

        expect(location.space).toEqual(space);
        expect(location.folder).toBeNull();
        expect(location.label).toBe('in Marketing');
    });

    // The two links sit inside one translated sentence: what stands around the path.
    it('splits the sentence around the path', () => {
        const location = itemLocation(fileRow({ path: [space] }));

        expect(location.before + 'Marketing' + location.after).toBe(location.label);
    });
});

describe('space items', () => {
    const space = { type: 'space', id: 3, name: 'Marketing', itemCount: 4 };

    it('reads a space tile\'s item count', () => {
        expect(tileMeta(space)).toBe('4 items');
    });

    it('reads one item in the singular', () => {
        expect(tileMeta({ ...space, itemCount: 1 })).toBe('1 item');
    });

    it('reads a space row\'s item count as its meta line', () => {
        expect(itemMeta({ ...space, itemCount: 2 })).toBe('2 items');
    });

    it('reads an empty space as a folder does', () => {
        expect(tileMeta({ ...space, itemCount: 0 })).toBe('empty');
    });
});
