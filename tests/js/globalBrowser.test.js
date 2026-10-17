import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { enableAutoUnmount, flushPromises, mount } from '@vue/test-utils';
import CfilesFileBrowser from '../../vue/CfilesFileBrowser.vue';
import CfilesItemForm from '../../vue/CfilesItemForm.vue';
import FilterBar from '@core/vue/FilterBar.vue';
import {
    browserProps, fileRow, globalProps, topLevel, globalResults, globalTop, insideFolder, openSpace, salesTile, spaceEntry, spaceLevel, spaceTile,
} from './support/fixtures.mjs';

// The browser listens on `window` (popstate): leaving instances mounted would let one test's
// browser answer another test's events.
enableAutoUnmount(afterEach);

const browser = (listing, over = {}) => mount(CfilesFileBrowser, { props: globalProps(listing, over) });

const deferred = () => {
    const d = {};
    d.promise = new Promise((resolve, reject) => {
        d.resolve = resolve;
        d.reject = reject;
    });
    return d;
};

/** The listing requests made, without what the filter controls ask on their own. */
const itemCalls = () => globalThis.humhubStubs.client.get.mock.calls
    .map(([url]) => url)
    .filter((url) => /cfiles\/(\d+\/)?items/.test(url));
const isGlobal = (url) => /cfiles\/items/.test(url);

/**
 * `client.get` answering the listing requests with `listing()` and the Space filter's own
 * lookups (`GET space?purpose=picker`, see SpaceFilterControl) with the space they ask for.
 */
const answer = (listing) => {
    globalThis.humhubStubs.client.get = vi.fn((url) => (/\/space\?/.test(url)
        ? Promise.resolve({ results: [{ id: 3, guid: 's-3', name: 'Marketing', url: '/s/marketing/', color: '#6fdbe8', imageUrl: null }] })
        : listing(url)));
};

/** A hit two folders down in the space Marketing. */
const hit = () => fileRow({
    parentFolderId: 8,
    path: [spaceEntry(), { type: 'folder', id: 7, title: 'Brand' }, { type: 'folder', id: 8, title: 'Logos' }],
});

describe('CfilesFileBrowser on the global files page', () => {
    beforeEach(() => {
        answer(() => Promise.resolve(spaceLevel()));
        globalThis.humhubStubs.client.post = vi.fn(() => Promise.resolve({ results: [], errors: [] }));
        globalThis.humhubStubs.client.ajax = vi.fn(() => Promise.resolve({ view: 'tiles' }));
        globalThis.humhubStubs.logCalls.error.length = 0;
        window.history.replaceState({}, '', '/files');
    });

    describe('the top level', () => {
        it('shows a tile per space: a folder with the space as its badge, and how much it holds', () => {
            const wrapper = browser(globalTop(undefined, { view: 'tiles' }));
            const marketing = wrapper.find('.c-tile-grid__tile[data-key="space:3"]');
            const sales = wrapper.find('.c-tile-grid__tile[data-key="space:4"]');

            expect(marketing.find('.cfiles-tile__folder').exists()).toBe(true);
            expect(marketing.find('.cfiles-tile__badge .space-acronym').text()).toBe('M');
            expect(sales.find('.cfiles-tile__badge img').attributes('src')).toBe('/uploads/sales.jpg');
            expect(marketing.find('.c-tile-grid__name').text()).toBe('Marketing');
            expect(marketing.find('.c-tile-grid__meta').text()).toBe('4 items');
            expect(sales.find('.c-tile-grid__meta').text()).toBe('1 item');
        });

        it('shows a row per space in the list view', () => {
            const wrapper = browser(globalTop());
            const rows = wrapper.findAll('.cfiles-row');

            expect(rows).toHaveLength(2);
            expect(rows[0].find('.cfiles-row-icon .space-acronym').exists()).toBe(true);
            expect(rows[0].find('h4 a').text()).toBe('Marketing');
            expect(rows[0].find('h4 a').attributes('href')).toBe('/files?space=3');
            expect(rows[0].find('.cfiles-row-meta').text()).toBe('4 items');
        });

        it('offers no add menu and takes no drop', () => {
            const wrapper = browser(globalTop());

            expect(wrapper.find('.cfiles-add-menu').exists()).toBe(false);
            expect(wrapper.find('.c-drop-zone').exists()).toBe(false);
            expect(wrapper.find('.cfiles-browser__card').exists()).toBe(true);
        });

        it('offers no selection and no menu on a space', () => {
            const tiles = browser(globalTop(undefined, { view: 'tiles' }));
            const rows = browser(globalTop());

            expect(tiles.find('.c-tile-grid__check').exists()).toBe(false);
            expect(tiles.find('.cfiles-tile__toggle').exists()).toBe(false);
            expect(tiles.find('.c-tile-grid__tile[draggable="true"]').exists()).toBe(false);
            expect(rows.find('.cfiles-row-select').exists()).toBe(false);
            expect(rows.find('.cfiles-row-controls').exists()).toBe(false);
            expect(rows.find('.cfiles-row').attributes('draggable')).toBe('false');
        });

        it('opens a space through its own endpoint and mirrors it into the URL', async () => {
            const wrapper = browser(globalTop());

            await wrapper.find('.cfiles-row h4 a').trigger('click');
            await flushPromises();

            expect(itemCalls()).toHaveLength(1);
            expect(itemCalls()[0]).toContain('cfiles/7/items');
            expect(itemCalls()[0]).not.toContain('parent=');
            expect(new URLSearchParams(window.location.search).get('space')).toBe('3');
            expect(wrapper.vm.container.contentContainerId).toBe(7);
            expect(wrapper.findAll('.cfiles-row')).toHaveLength(2);
        });
    });

    describe('the path bar', () => {
        const inside = () => {
            window.history.replaceState({}, '', '/files?space=3&fid=9');
            return browser(insideFolder([], { canWrite: true }), { space: openSpace(), contentContainerId: 7 });
        };

        it('reads Files › space › folders', () => {
            const wrapper = inside();
            const links = wrapper.findAll('a.c-path-bar__link');

            expect(wrapper.find('a.c-path-bar__root').attributes('aria-label')).toBe('Files');
            expect(wrapper.find('a.c-path-bar__root').attributes('href')).toBe('/files');
            expect(links.map((link) => link.text())).toEqual(['Marketing', 'test123']);
            expect(links[0].attributes('href')).toBe('/files?space=3');
            expect(links[1].attributes('href')).toBe('/files?space=3&fid=7');
            expect(wrapper.find('.c-path-bar__current').text()).toBe('sgadgasdg');
        });

        it('goes back to the tiles through the global endpoint from the root', async () => {
            const wrapper = inside();
            answer(() => Promise.resolve(globalTop()));

            await wrapper.find('a.c-path-bar__root').trigger('click');
            await flushPromises();

            expect(isGlobal(itemCalls()[0])).toBe(true);
            expect(wrapper.vm.container).toBeNull();
            expect(window.location.search).toBe('');
            expect(wrapper.find('.cfiles-row h4 a').text()).toBe('Marketing');
        });

        it('goes to the space\'s top level from the space crumb', async () => {
            const wrapper = inside();

            await wrapper.find('a.c-path-bar__link').trigger('click');
            await flushPromises();

            expect(itemCalls()[0]).toContain('cfiles/7/items');
            expect(itemCalls()[0]).not.toContain('parent=');
        });
    });

    describe('the Space filter', () => {
        const withSpaceFilter = () => {
            window.history.replaceState({}, '', '/files?spaceId=3');
            return browser(globalTop([spaceTile()]), {
                initialFilters: { q: '', spaceId: '3', userId: '', topicId: '', type: '', modified: '' },
            });
        };
        const control = (wrapper) => wrapper.find('.form-search-filter-spaceId');

        it('is there at the top level only, and keeps its value inside a space', async () => {
            const wrapper = withSpaceFilter();
            expect(control(wrapper).exists()).toBe(true);

            await wrapper.find('.cfiles-row h4 a').trigger('click');
            await flushPromises();
            expect(control(wrapper).exists()).toBe(false);

            answer(() => Promise.resolve(globalTop([spaceTile()])));
            await wrapper.find('a.c-path-bar__root').trigger('click');
            await flushPromises();

            expect(control(wrapper).exists()).toBe(true);
            expect(wrapper.findComponent(FilterBar).vm.draft.spaceId).toEqual(['3']);
            expect(itemCalls()[0]).toContain('spaceId=3');
        });

        it('is never sent to a space\'s endpoint', async () => {
            const wrapper = withSpaceFilter();

            await wrapper.find('.cfiles-row h4 a').trigger('click');
            await flushPromises();
            wrapper.vm.reload();
            await flushPromises();

            expect(itemCalls()).toHaveLength(2);
            itemCalls().forEach((url) => {
                expect(url).toContain('cfiles/7/items');
                expect(url).not.toContain('spaceId');
                expect(url).not.toContain('topicId');
            });
            // Kept in the page URL, for the way back.
            expect(new URLSearchParams(window.location.search).get('spaceId')).toBe('3');
        });
    });

    it('hands the edit dialog of a hit the space it lies in', async () => {
        window.history.replaceState({}, '', '/files?q=logo');
        const wrapper = browser(globalResults([hit()]), {
            initialFilters: { q: 'logo', spaceId: '', userId: '', topicId: '', type: '', modified: '' },
        });

        wrapper.vm.openEdit(wrapper.vm.items[0]);
        await flushPromises();

        expect(wrapper.findComponent(CfilesItemForm).props('contentContainerId')).toBe(7);
    });

    describe('the Topic filter inside a space', () => {
        const topicControl = (wrapper) => wrapper.findComponent({ name: 'TopicFilterControl' });

        it('searches the topics of the space open, and sends them to its endpoint only', async () => {
            window.history.replaceState({}, '', '/files?spaceId=3');
            const wrapper = browser(globalTop([spaceTile()]), {
                initialFilters: { q: '', spaceId: '3', userId: '', topicId: '', type: '', modified: '' },
            });

            // The page's definitions of a space are of no space in particular: no props at all.
            expect(wrapper.props('containerFilters').find((filter) => filter.key === 'topicId').props).toBeUndefined();

            await wrapper.find('.cfiles-row h4 a').trigger('click');
            await flushPromises();
            // The island scopes it to the space open.
            expect(topicControl(wrapper).props('filter').props.containerId).toBe(7);

            // The picker resolves the topic chosen.
            const listing = globalThis.humhubStubs.client.get.getMockImplementation();
            globalThis.humhubStubs.client.get = vi.fn((url) => (/topic\/picker/.test(url)
                ? Promise.resolve({ results: [{ id: 4, name: 'Budget', color: null, container: null }] })
                : listing(url)));
            topicControl(wrapper).vm.$emit('update:modelValue', ['4']);
            await flushPromises();

            const url = itemCalls().at(-1);
            expect(url).toContain('cfiles/7/items');
            expect(new URL(url, 'http://localhost').searchParams.get('topicId')).toBe('4');
            expect(url).not.toContain('spaceId');
        });

        /**
         * Topics chosen at the top level may be of several spaces. Entering one, the island asks
         * the picker once which of them are the space's (or global) and filters and shows those
         * only — the space's endpoint would refuse the others —, keeping them all for the way back.
         */
        describe('topics of other spaces', () => {
            const topicId = (url) => new URL(url, 'http://localhost').searchParams.get('topicId');
            // The island's own check: all the ids, for the space (the control resolves what it shows).
            const pickerCalls = () => globalThis.humhubStubs.client.get.mock.calls.map(([url]) => url)
                .filter((url) => /topic\/picker/.test(url))
                .filter((url) => new URL(url, 'http://localhost').searchParams.get('ids') === '4,9')
                .filter((url) => new URL(url, 'http://localhost').searchParams.has('containerId'));
            /**
             * The picker knows `known` of the ids asked for in the space (container 7), every one
             * without a container (the top level's Topic filter).
             */
            const answerWith = (known) => {
                globalThis.humhubStubs.client.get = vi.fn((url) => {
                    if (/topic\/picker/.test(url)) {
                        const params = new URL(url, 'http://localhost').searchParams;
                        const asked = params.get('ids').split(',').map(Number);
                        const ids = params.has('containerId') ? asked.filter((id) => known.includes(id)) : asked;
                        return Promise.resolve({ results: ids.map((id) => ({ id, name: 'Topic ' + id, color: null, container: null })) });
                    }
                    return Promise.resolve(isGlobal(url) ? globalResults([hit()]) : spaceLevel());
                });
            };
            const found = () => {
                window.history.replaceState({}, '', '/files?topicId=4,9');
                return browser(globalResults([hit()]), {
                    initialFilters: { q: '', spaceId: '', userId: '', topicId: '4,9', type: '', modified: '' },
                });
            };
            const enterSpace = async (wrapper) => {
                await wrapper.find('.cfiles-location__space').trigger('click');
                await flushPromises();
            };

            it('filters the space by its own topics and shows only those', async () => {
                answerWith([4]);
                const wrapper = found();

                await enterSpace(wrapper);

                expect(pickerCalls()).toHaveLength(1);
                const picker = new URL(pickerCalls()[0], 'http://localhost').searchParams;
                expect(picker.get('ids')).toBe('4,9');
                expect(picker.get('containerId')).toBe('7');
                const url = itemCalls().at(-1);
                expect(url).toContain('cfiles/7/items');
                expect(topicId(url)).toBe('4');
                expect(topicControl(wrapper).props('modelValue')).toEqual(['4']);
                expect(globalThis.humhubStubs.logCalls.error).toHaveLength(0);
            });

            it('filters without a topic when none is the space\'s', async () => {
                answerWith([]);
                const wrapper = found();

                await enterSpace(wrapper);

                const url = itemCalls().at(-1);
                expect(url).toContain('cfiles/7/items');
                expect(url).not.toContain('topicId');
                expect(topicControl(wrapper).props('modelValue')).toEqual([]);
                expect(globalThis.humhubStubs.logCalls.error).toHaveLength(0);
            });

            it('brings them all back at the top level, and asks the picker once per space', async () => {
                answerWith([4]);
                const wrapper = found();
                await enterSpace(wrapper);

                await wrapper.find('a.c-path-bar__root').trigger('click');
                await flushPromises();

                const url = itemCalls().at(-1);
                expect(isGlobal(url)).toBe(true);
                expect(topicId(url)).toBe('4,9');
                expect(wrapper.vm.filterValues.topicId).toEqual(['4', '9']);

                await enterSpace(wrapper);
                expect(pickerCalls()).toHaveLength(1);
                expect(topicId(itemCalls().at(-1))).toBe('4');
            });

            it('keeps the other spaces\' topics when the topics are changed inside the space', async () => {
                answerWith([4, 5]);
                const wrapper = found();
                await enterSpace(wrapper);

                topicControl(wrapper).vm.$emit('update:modelValue', ['4', '5']);
                await flushPromises();
                expect(topicId(itemCalls().at(-1))).toBe('4,5');

                await wrapper.find('a.c-path-bar__root').trigger('click');
                await flushPromises();
                expect(topicId(itemCalls().at(-1))).toBe('4,5,9');
            });
        });
    });

    describe('results across the spaces', () => {
        const found = () => {
            window.history.replaceState({}, '', '/files?q=logo');
            return browser(globalResults([hit()]), {
                initialFilters: { q: 'logo', spaceId: '', userId: '', topicId: '', type: '', modified: '' },
            });
        };

        it('places each hit in its space and folder', () => {
            const location = found().find('.cfiles-location');

            expect(location.text()).toBe('in Marketing › Brand › Logos');
            expect(location.find('.cfiles-location__space').attributes('href')).toBe('/files?space=3&q=logo');
            expect(location.find('.cfiles-location__folder').attributes('href')).toBe('/files?space=3&fid=8&q=logo');
        });

        it('opens the space of a hit from its space part', async () => {
            const wrapper = found();

            await wrapper.find('.cfiles-location__space').trigger('click');
            await flushPromises();

            expect(itemCalls()[0]).toContain('cfiles/7/items');
            expect(itemCalls()[0]).not.toContain('parent=');
            expect(itemCalls()[0]).toContain('q=logo');
            expect(new URLSearchParams(window.location.search).get('space')).toBe('3');
            expect(wrapper.findAll('a.c-path-bar__link').map((link) => link.text())).toEqual([]);
            expect(wrapper.find('.c-path-bar__current').text()).toBe('Marketing');
        });

        it('opens the folder of a hit in its space from its folder part', async () => {
            const wrapper = found();

            await wrapper.find('.cfiles-location__folder').trigger('click');
            await flushPromises();

            expect(itemCalls()[0]).toContain('cfiles/7/items');
            expect(itemCalls()[0]).toContain('parent=8');
            const params = new URLSearchParams(window.location.search);
            expect(params.get('space')).toBe('3');
            expect(params.get('fid')).toBe('8');
        });

        it('offers no move for a hit, only what its space allows', async () => {
            const wrapper = found();
            const entries = wrapper.vm.entriesFor(hit());
            const move = entries.find((entry) => entry.id === 'cfiles-move');
            const del = entries.find((entry) => entry.id === 'cfiles-delete');

            expect(move.condition({ capabilities: { canEdit: true } })).toBe(false);
            expect(del.condition({ capabilities: { canDelete: true } })).toBe(true);
        });
    });

    it('shows the space opened last, whichever answers first', async () => {
        const wrapper = browser(globalTop());
        const first = deferred();
        const second = deferred();
        const queue = [first, second];
        answer(() => queue.shift().promise);

        const rows = wrapper.findAll('.cfiles-row h4 a');
        await rows[0].trigger('click');
        await rows[1].trigger('click');
        second.resolve(spaceLevel([fileRow({ title: 'Sales.pdf' })]));
        await flushPromises();
        first.resolve(spaceLevel([fileRow({ title: 'Marketing.pdf' })]));
        await flushPromises();

        expect(wrapper.vm.container.contentContainerId).toBe(8);
        expect(wrapper.find('.c-path-bar__current').text()).toBe('Sales');
        expect(wrapper.find('.cfiles-row h4 a').text()).toBe('Sales.pdf');
        expect(new URLSearchParams(window.location.search).get('space')).toBe('4');
    });

    it('goes back to the tiles on popstate', async () => {
        window.history.replaceState({}, '', '/files?space=3');
        const wrapper = browser(spaceLevel(), { space: openSpace(), contentContainerId: 7 });
        answer(() => Promise.resolve(globalTop()));

        window.history.replaceState({}, '', '/files');
        window.dispatchEvent(new Event('popstate'));
        await flushPromises();

        expect(isGlobal(itemCalls()[0])).toBe(true);
        expect(wrapper.vm.container).toBeNull();
        expect(wrapper.find('.cfiles-row h4 a').text()).toBe('Marketing');
    });

    it('goes forward into a space again on popstate', async () => {
        const wrapper = browser(globalTop());

        await wrapper.find('.cfiles-row h4 a').trigger('click');
        await flushPromises();
        const state = window.history.state;

        answer(() => Promise.resolve(globalTop()));
        await wrapper.find('a.c-path-bar__root').trigger('click');
        await flushPromises();

        answer(() => Promise.resolve(spaceLevel()));
        window.history.replaceState(state, '', '/files?space=3');
        window.dispatchEvent(new Event('popstate'));
        await flushPromises();

        expect(itemCalls()[0]).toContain('cfiles/7/items');
        expect(wrapper.vm.container.space.name).toBe('Marketing');
    });

    describe('the first paint', () => {
        it('asks nothing and reports nothing for the space and folder its URL names', async () => {
            window.history.replaceState({}, '', '/files?space=3&fid=9');
            browser(insideFolder([], { canWrite: true }), { space: openSpace(), contentContainerId: 7 });
            await flushPromises();

            expect(itemCalls()).toHaveLength(0);
            expect(globalThis.humhubStubs.logCalls.error).toHaveLength(0);
        });

        // A folder means nothing without its space: the page (and the island) take the top level.
        it('takes a folder without a space as the top level', async () => {
            window.history.replaceState({}, '', '/files?fid=5');
            browser(globalTop());
            await flushPromises();

            expect(itemCalls()).toHaveLength(0);
        });

        it('stamps the entry it was opened with, keeping what PJAX keeps there', () => {
            window.history.replaceState({ url: 'http://localhost/files?space=3', container: '#layout-content' }, '', '/files?space=3');
            browser(spaceLevel(), { space: openSpace(), contentContainerId: 7 });

            expect(window.history.state.container).toBe('#layout-content');
            expect(window.history.state.cfiles.folderId).toBeNull();
            expect(window.history.state.cfiles.space).toMatchObject({ id: 3, contentContainerId: 7, name: 'Marketing' });
        });

        // Opened on a space, left for the hits of other spaces, then Back: the first entry has
        // to say which space it was — nothing on the page shows it any more.
        it('goes back to the space it was opened on', async () => {
            window.history.replaceState({}, '', '/files?space=3&q=logo');
            const wrapper = browser(spaceLevel([], { resultsMode: true }), {
                space: openSpace(), contentContainerId: 7,
                initialFilters: { q: 'logo', spaceId: '', userId: '', topicId: '', type: '', modified: '' },
            });
            const first = window.history.state;

            answer(() => Promise.resolve(globalResults([fileRow({ path: [spaceEntry({ id: 4, title: 'Sales', contentContainerId: 8 })] })])));
            await wrapper.find('a.c-path-bar__root').trigger('click');
            await flushPromises();

            answer(() => Promise.resolve(spaceLevel([], { resultsMode: true })));
            window.history.replaceState(first, '', '/files?space=3&q=logo');
            window.dispatchEvent(new Event('popstate'));
            await flushPromises();

            expect(itemCalls()[0]).toContain('cfiles/7/items');
            expect(wrapper.vm.container.space.name).toBe('Marketing');
        });

        it('finds the space of a history entry among the hits shown', async () => {
            window.history.replaceState({}, '', '/files?q=logo');
            const wrapper = browser(globalResults([hit()]), {
                initialFilters: { q: 'logo', spaceId: '', userId: '', topicId: '', type: '', modified: '' },
            });

            window.history.replaceState({}, '', '/files?space=3&q=logo');
            window.dispatchEvent(new Event('popstate'));
            await flushPromises();

            expect(itemCalls()[0]).toContain('cfiles/7/items');
            expect(wrapper.vm.container.contentContainerId).toBe(7);
        });
    });

    describe('history entries of other pages', () => {
        it('leaves them alone on the global page', async () => {
            browser(globalTop());

            window.history.replaceState({}, '', '/dashboard');
            window.dispatchEvent(new Event('popstate'));
            await flushPromises();

            expect(itemCalls()).toHaveLength(0);
        });

        it('leaves them alone in the space browser', async () => {
            window.history.replaceState({}, '', '/s/x/cfiles/browse/index?fid=9');
            mount(CfilesFileBrowser, { props: browserProps(insideFolder([])) });

            window.history.replaceState({}, '', '/files');
            window.dispatchEvent(new Event('popstate'));
            await flushPromises();

            expect(itemCalls()).toHaveLength(0);
        });
    });

    // The menus link a page by a URL of their own (`/s/x/cfiles/browse`, `r=cfiles/browse`,
    // `/cfiles/global`), not the one the island pushes: going back to that first entry is still
    // going back on this page.
    describe('the entry the page was opened with', () => {
        const backToFirst = async (href, wrapper) => {
            await wrapper.find('.cfiles-row h4 a').trigger('click');
            await flushPromises();
            globalThis.humhubStubs.client.get.mockClear();
            window.history.replaceState(window.history.state, '', href);
            window.dispatchEvent(new Event('popstate'));
            await flushPromises();
        };

        it('goes back to it in the space browser opened from a menu link', async () => {
            window.history.replaceState({}, '', '/s/x/cfiles/browse');
            const wrapper = mount(CfilesFileBrowser, { props: browserProps(topLevel()) });
            answer(() => Promise.resolve(insideFolder([])));

            await backToFirst('/s/x/cfiles/browse', wrapper);

            expect(itemCalls()).toHaveLength(1);
            expect(itemCalls()[0]).toContain('cfiles/5/items');
            expect(itemCalls()[0]).not.toContain('parent=');
        });

        it('goes back to it without pretty URLs', async () => {
            window.history.replaceState({}, '', '/index.php?r=cfiles%2Fbrowse&cguid=abc');
            const wrapper = mount(CfilesFileBrowser, {
                props: browserProps(topLevel(), { browseUrl: '/index.php?r=cfiles%2Fbrowse%2Findex&cguid=abc' }),
            });
            answer(() => Promise.resolve(insideFolder([])));

            await backToFirst('/index.php?r=cfiles%2Fbrowse&cguid=abc', wrapper);

            expect(itemCalls()).toHaveLength(1);
            expect(itemCalls()[0]).not.toContain('parent=');
        });

        it('goes back to it on the global page opened by its route', async () => {
            window.history.replaceState({}, '', '/cfiles/global');
            const wrapper = browser(globalTop());

            await backToFirst('/cfiles/global', wrapper);

            expect(itemCalls()).toHaveLength(1);
            expect(isGlobal(itemCalls()[0])).toBe(true);
            expect(wrapper.vm.container).toBeNull();
        });

        // PJAX restoring a cached page: its embedded level is not the one the URL names, so the
        // island opens that one — and the entry has to say so, not what was embedded.
        it('says which level the global page opened when the URL disagreed with the payload', async () => {
            window.history.replaceState({ container: '#layout-content', cfiles: { folderId: null, space: openSpace() } }, '', '/files?space=3');
            browser(globalTop());
            await flushPromises();

            expect(itemCalls()[0]).toContain('cfiles/7/items');
            expect(window.history.state.container).toBe('#layout-content');
            expect(window.history.state.cfiles.space.id).toBe(3);
        });

        it('says which folder the space browser opened when the URL disagreed with the payload', async () => {
            window.history.replaceState({ container: '#layout-content' }, '', '/s/x/cfiles/browse/index?fid=9');
            answer(() => Promise.resolve(insideFolder([])));
            mount(CfilesFileBrowser, { props: browserProps(topLevel()) });
            await flushPromises();

            expect(window.history.state.container).toBe('#layout-content');
            expect(window.history.state.cfiles).toEqual({ folderId: 9 });
        });
    });

    describe('the top level\'s wording and chrome', () => {
        it('counts the hits in the spaces of the user', () => {
            window.history.replaceState({}, '', '/files?q=logo');
            const wrapper = browser(globalResults([hit()], { total: 1 }), {
                initialFilters: { q: 'logo', spaceId: '', userId: '', topicId: '', type: '', modified: '' },
            });

            expect(wrapper.find('.cfiles-results').text()).toBe('1 result in your spaces');
        });

        it('says there are no results in the spaces of the user', () => {
            window.history.replaceState({}, '', '/files?q=nothing');
            const wrapper = browser(globalResults([], { total: 0 }), {
                initialFilters: { q: 'nothing', spaceId: '', userId: '', topicId: '', type: '', modified: '' },
            });

            expect(wrapper.find('.cfiles-empty').text()).toContain('No results in your spaces.');
        });

        // Nothing hangs off it there: no crumb below the root, no selection.
        it('shows no path bar at the top level', () => {
            expect(browser(globalTop()).find('.c-path-bar').exists()).toBe(false);
        });

        it('shows the path bar inside a space', () => {
            window.history.replaceState({}, '', '/files?space=3');
            expect(browser(spaceLevel(), { space: openSpace(), contentContainerId: 7 }).find('.c-path-bar').exists()).toBe(true);
        });

        it('hides the space images from assistive technology, the name says it all', () => {
            const tiles = browser(globalTop(undefined, { view: 'tiles' }));
            const rows = browser(globalTop());

            expect(tiles.find('.cfiles-tile__badge').attributes('aria-hidden')).toBe('true');
            expect(rows.find('.cfiles-row-icon > span').attributes('aria-hidden')).toBe('true');
        });

        it('names what each part of a hit\'s location opens', () => {
            window.history.replaceState({}, '', '/files?q=logo');
            const location = browser(globalResults([hit()]), {
                initialFilters: { q: 'logo', spaceId: '', userId: '', topicId: '', type: '', modified: '' },
            }).find('.cfiles-location');

            expect(location.find('.cfiles-location__space').attributes('aria-label')).toBe('Open Marketing');
            expect(location.find('.cfiles-location__folder').attributes('aria-label')).toBe('Open Logos');
        });
    });

    describe('inside a space', () => {
        it('offers the add menu where the level says the user may write', () => {
            window.history.replaceState({}, '', '/files?space=3');
            const wrapper = browser(spaceLevel([], { canWrite: true }), { space: openSpace(), contentContainerId: 7 });

            expect(wrapper.find('.cfiles-add-menu').exists()).toBe(true);
            expect(wrapper.find('.c-drop-zone').exists()).toBe(true);
        });

        it('offers no add menu where the level says the user may not write', () => {
            window.history.replaceState({}, '', '/files?space=3');
            const wrapper = browser(spaceLevel([], { canWrite: false }), { space: openSpace(), contentContainerId: 7 });

            expect(wrapper.find('.cfiles-add-menu').exists()).toBe(false);
        });

        it('takes the right to write from each space opened', async () => {
            const wrapper = browser(globalTop());
            answer(() => Promise.resolve(spaceLevel([], { canWrite: true })));

            await wrapper.find('.cfiles-row h4 a').trigger('click');
            await flushPromises();

            expect(wrapper.find('.cfiles-add-menu').exists()).toBe(true);
        });

        // The file handlers' markup is rendered for the page's own request — on the global
        // page that names no space, so a handler would create its document nowhere.
        it('leaves out the server-rendered file handlers', () => {
            window.history.replaceState({}, '', '/files?space=3');
            const wrapper = browser(spaceLevel([], { canWrite: true }), {
                space: openSpace(), contentContainerId: 7, createHandlersHtml: '<li class="handler">New sheet</li>',
            });

            expect(wrapper.find('.handler').exists()).toBe(false);
        });

        it('uploads into the open space', async () => {
            window.history.replaceState({}, '', '/files?space=3');
            const wrapper = browser(spaceLevel([], { canWrite: true }), { space: openSpace(), contentContainerId: 7 });

            wrapper.vm.upload([new File(['x'], 'a.txt')]);
            await flushPromises();

            expect(globalThis.humhubStubs.client.post.mock.calls[0][0]).toContain('cfiles/7/files');
        });
    });

    // One source for the right to write, in the space browser too: the level's payload, the
    // page's prop until a payload says otherwise.
    it('takes the space browser\'s right to write from the levels it loads', async () => {
        window.history.replaceState({}, '', '/s/x/cfiles/browse/index');
        const wrapper = mount(CfilesFileBrowser, { props: browserProps(topLevel()) });
        expect(wrapper.find('.cfiles-add-menu').exists()).toBe(true);

        answer(() => Promise.resolve(topLevel(undefined, { canWrite: false })));
        wrapper.vm.reload();
        await flushPromises();

        expect(wrapper.find('.cfiles-add-menu').exists()).toBe(false);
    });

    it('uses the sales space\'s own endpoint once opened', async () => {
        const wrapper = browser(globalTop([salesTile()]));

        await wrapper.find('.cfiles-row h4 a').trigger('click');
        await flushPromises();

        expect(itemCalls()[0]).toContain('cfiles/8/items');
    });
});
