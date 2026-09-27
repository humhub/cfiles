import { describe, expect, it } from 'vitest';
import { fileRow, folderRow } from './support/fixtures.mjs';
import { tileMeta } from '../../vue/browser/itemPresentation';

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
