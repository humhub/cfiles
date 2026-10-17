import { describe, expect, it } from 'vitest';
import {
    SPACE_CRUMB, containerOf, isPageLocation, isPageUrl, pageOf, isSet, levelFilters, levelKey, levelUrl, mergeScoped, normalizeQuery,
    resolveSpace, sameContainer, scopedIds, spaceIdFromUrl, urlValue,
} from '../../vue/browser/globalLevel';
import { fileRow, globalFilters, openSpace, spaceEntry, spaceTile } from './support/fixtures.mjs';

describe('globalLevel', () => {
    describe('isSet', () => {
        it('reads an empty string, null, undefined and an empty array as not set', () => {
            expect(['', null, undefined, []].map(isSet)).toEqual([false, false, false, false]);
        });

        it('reads a value and a non-empty array as set', () => {
            expect([' ', '0', 0, ['3']].map(isSet)).toEqual([true, true, true, true]);
        });
    });

    it('writes several values comma-separated into a URL', () => {
        expect(urlValue(['3', '4'])).toBe('3,4');
        expect(urlValue('logo')).toBe('logo');
    });

    describe('normalizeQuery', () => {
        it('reads a filter of several values as an array', () => {
            expect(normalizeQuery({ spaceId: '3,4', q: 'a,b' }, globalFilters())).toEqual({ spaceId: ['3', '4'], q: 'a,b' });
        });

        it('reads an empty one as an empty array', () => {
            expect(normalizeQuery({ spaceId: '' }, globalFilters())).toEqual({ spaceId: [] });
        });

        it('keeps an array as it is', () => {
            expect(normalizeQuery({ spaceId: ['3'] }, globalFilters())).toEqual({ spaceId: ['3'] });
        });
    });

    describe('containerOf', () => {
        it('takes a tile, which names its space in `name`', () => {
            expect(containerOf(spaceTile())).toEqual({
                contentContainerId: 7,
                space: { id: 3, contentContainerId: 7, guid: 's-3', name: 'Marketing', color: '#6fdbe8', imageUrl: null },
            });
        });

        it('takes a path entry, which names it in `title`', () => {
            expect(containerOf(spaceEntry()).space.name).toBe('Marketing');
            expect(containerOf(spaceEntry()).space.color).toBeNull();
        });
    });

    it('compares containers by their container id, null being the top level', () => {
        expect(sameContainer(containerOf(spaceTile()), containerOf(spaceEntry()))).toBe(true);
        expect(sameContainer(containerOf(spaceTile()), null)).toBe(false);
        expect(sameContainer(null, undefined)).toBe(true);
    });

    describe('levelKey', () => {
        it('is the folder id in the space browser', () => {
            expect(levelKey(false, { contentContainerId: 5 }, { id: 9 })).toBe(9);
            expect(levelKey(false, { contentContainerId: 5 }, null)).toBe(0);
        });

        it('names the container on the global page, where folder ids of two spaces meet', () => {
            expect(levelKey(true, { contentContainerId: 7 }, { id: 9 })).toBe('7:9');
            expect(levelKey(true, { contentContainerId: 7 }, null)).toBe('7:0');
            expect(levelKey(true, null, null)).toBe('0');
        });
    });

    describe('levelUrl', () => {
        it('is the page itself at the top level', () => {
            expect(levelUrl('/files', null, null, {})).toBe('/files');
        });

        it('names the space, and the folder where there is one', () => {
            expect(levelUrl('/files', { id: 3 }, null, {})).toBe('/files?space=3');
            expect(levelUrl('/files', { id: 3 }, 9, {})).toBe('/files?space=3&fid=9');
        });

        it('names no folder without a space', () => {
            expect(levelUrl('/files', null, 9, {})).toBe('/files');
        });

        it('carries the filters that are set', () => {
            expect(levelUrl('/files', { id: 3 }, null, { q: 'logo', spaceId: ['3', '4'], type: '', sort: '' }))
                .toBe('/files?space=3&q=logo&spaceId=3%2C4');
        });

        it('appends to a URL with a query of its own', () => {
            expect(levelUrl('/index.php?r=cfiles%2Fglobal', { id: 3 }, null, {})).toBe('/index.php?r=cfiles%2Fglobal&space=3');
        });
    });

    it('reads the space a URL names, null for none', () => {
        expect(spaceIdFromUrl('?space=3&fid=9')).toBe(3);
        expect(spaceIdFromUrl('?fid=9')).toBeNull();
        expect(spaceIdFromUrl('?space=x')).toBeNull();
    });

    describe('resolveSpace', () => {
        it('finds a space among the containers it knows', () => {
            const known = containerOf(openSpace());
            expect(resolveSpace(3, { known: [null, known] })).toBe(known);
        });

        it('finds it in the history entry', () => {
            expect(resolveSpace(3, { state: { cfiles: { folderId: null, space: openSpace() } } }).contentContainerId).toBe(7);
        });

        it('finds it among the tiles', () => {
            expect(resolveSpace(3, { items: [spaceTile()] }).contentContainerId).toBe(7);
        });

        it('finds it in the path of a hit', () => {
            expect(resolveSpace(3, { items: [fileRow({ path: [spaceEntry()] })] }).space.name).toBe('Marketing');
        });

        it('is null for a space it knows nothing of', () => {
            expect(resolveSpace(4, { known: [containerOf(openSpace())], state: {}, items: [spaceTile(), fileRow()] })).toBeNull();
        });
    });

    describe('levelFilters', () => {
        const query = { q: 'logo', spaceId: ['3'], topicId: [], type: '' };

        it('keeps the filters of the definitions only', () => {
            expect(levelFilters(query, [{ key: 'q' }, { key: 'type' }, { key: 'sort' }])).toEqual({ q: 'logo', type: '' });
        });

        it('keeps them all without definitions', () => {
            expect(levelFilters(query, null)).toBe(query);
        });
    });

    describe('isPageUrl', () => {
        const at = (href) => new URL(href, 'http://localhost');

        it('is the page at its path, whatever the query', () => {
            expect(isPageUrl('/files', at('/files?space=3'))).toBe(true);
            expect(isPageUrl('/files', at('/dashboard'))).toBe(false);
        });

        it('compares the parameters the page URL carries, without pretty URLs', () => {
            expect(isPageUrl('/index.php?r=cfiles%2Fglobal%2Findex', at('/index.php?r=cfiles%2Fglobal%2Findex&space=3'))).toBe(true);
            expect(isPageUrl('/index.php?r=cfiles%2Fglobal%2Findex', at('/index.php?r=dashboard'))).toBe(false);
        });
    });

    describe('pageOf / isPageLocation', () => {
        const at = (href) => new URL(href, 'http://localhost');

        it('remembers the path, and the route and container of a URL without pretty URLs', () => {
            expect(pageOf(at('/s/x/cfiles/browse?fid=3&q=a'))).toEqual({ pathname: '/s/x/cfiles/browse', params: {} });
            expect(pageOf(at('/index.php?r=cfiles%2Fbrowse&cguid=abc&fid=3')))
                .toEqual({ pathname: '/index.php', params: { r: 'cfiles/browse', cguid: 'abc' } });
        });

        it('takes an entry at the same path, whatever else its query says', () => {
            expect(isPageLocation(pageOf(at('/s/x/cfiles/browse')), at('/s/x/cfiles/browse?fid=0'))).toBe(true);
            expect(isPageLocation(pageOf(at('/s/x/cfiles/browse')), at('/s/x/cfiles/browse/index'))).toBe(false);
        });

        it('compares the route and the container without pretty URLs', () => {
            const page = pageOf(at('/index.php?r=cfiles%2Fbrowse&cguid=abc'));

            expect(isPageLocation(page, at('/index.php?r=cfiles%2Fbrowse&cguid=abc&fid=3'))).toBe(true);
            expect(isPageLocation(page, at('/index.php?r=dashboard'))).toBe(false);
            expect(isPageLocation(page, at('/index.php?r=cfiles%2Fbrowse&cguid=other'))).toBe(false);
        });

        it('takes nothing without a page', () => {
            expect(isPageLocation(null, at('/files'))).toBe(false);
        });
    });

    it('names the space crumb so no folder id can be it', () => {
        expect(typeof SPACE_CRUMB).toBe('string');
    });

    describe('topics of a space', () => {
        const scope = { 4: true, 5: true, 9: false };

        it('takes the ids the space takes, not those it refuses or has not said anything of', () => {
            expect(scopedIds(['4', '9', '12'], scope)).toEqual(['4']);
            expect(scopedIds('4,5', scope)).toEqual(['4', '5']);
            expect(scopedIds(['4'], undefined)).toEqual([]);
        });

        it('keeps what the space does not take next to what was chosen in it', () => {
            expect(mergeScoped(['5'], ['4', '9', '12'], scope)).toEqual(['5', '9', '12']);
            expect(mergeScoped([], ['4', '9'], scope)).toEqual(['9']);
        });

        it('keeps the value as it was when the same ids come back', () => {
            expect(mergeScoped(['4'], ['9', '4'], scope)).toEqual(['9', '4']);
        });
    });
});
