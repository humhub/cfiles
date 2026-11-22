/**
 * The filters and the sort of the file browser, and the result list they make of a level.
 *
 * The `FilterBar` holds the list's filters (search, author, file type, modified) and its sort, and
 * mirrors them into the page URL next to `fid` (`replaceState`), so a search can be shared; the
 * page renders its first page with the URL's values (`initialFilters`, `listing`), so the bar
 * has nothing to apply on mount. Folder links and history entries carry them too: opening a
 * folder keeps the filters, and going back restores the ones of that entry.
 *
 * With a filter set the list is a result list (`resultsMode`): the hits in the open folder and
 * all its subfolders (at the top level, in the whole container), each naming the folder it
 * lies in (`path`, rendered by the views as a link that opens that folder, filters and all). A
 * line under the path says how many there are; with none, the empty state offers to reset the
 * filters. Selection, paging, moving, the drop targets (folder hits) and uploads (into the open
 * folder) work as for a level.
 *
 * On the global page the bar's definitions follow the level: `filters` (with Space) at the
 * top level, `containerFilters` inside a space, whose Topic searches the topics of the space
 * open. Every value stays in `query` — a Space filter set at the top is kept inside a space and
 * back —, and a load sends only the keys of its level's definitions: a space's endpoint refuses
 * the others (`422`). A topic chosen at the top level that is not of the space opened (nor
 * global) is refused there too.
 *
 * A mixin of the island, see its docblock. Uses the level shown (`container`, `folder`,
 * `isGlobalTop`, `total`, `reload()`); every load sends `levelFilters()` (`fetchLevel()`).
 */
import { i18n, log } from '@humhub/vue';
import { resolveTopics } from './api';
import {
    TOPIC_KEY, idsOf, isSet, levelFilters, mergeScoped, normalizeQuery, scopedIds, urlValue,
} from './globalLevel';

/** The list's order without a chosen sort (`FolderList::SORT_DEFAULT`). */
export const SORT_DEFAULT = 'default';

export default {
    data() {
        return {
            // A key of the list's sorts, `default` for none chosen.
            sort: this.listing.sort || SORT_DEFAULT,
            // The values of the list's filters by key, `''` = not set; sent with every load (on
            // the global page only those the level's endpoint knows, see `levelFilters()`).
            query: normalizeQuery({ ...this.initialFilters }, [...this.filters, ...this.containerFilters]),
            // On the global page: which topic ids each space takes (its own and the global ones),
            // by content container id — `{ [id]: true|false }`, asked once per id and space.
            topicScopes: {},
        };
    },
    computed: {
        /**
         * The FilterBar definitions of the level shown: inside a space the space's own. The page
         * builds those for no space in particular (`GlobalController`): the Topic searches the
         * topics of the one open (`props.containerId`), as the space's endpoint takes them.
         */
        activeFilters() {
            if (!this.global || !this.container) {
                return this.filters;
            }
            const containerId = this.container.contentContainerId;
            return this.containerFilters.map((filter) => (filter.type === 'topic'
                ? { ...filter, props: { ...(filter.props || {}), containerId } }
                : filter));
        },
        /**
         * The bar's values: the filters, and the sort — the select shows an offered key;
         * `default` (and a key it does not offer) is its label.
         */
        filterValues() {
            const select = this.activeFilters.find((filter) => filter.key === 'sort');
            const offered = (select?.options || []).some((option) => option.value === this.sort);
            return this.scopeTopics({ ...this.query, sort: offered ? this.sort : '' }, this.container);
        },
        resultsLabel() {
            if (this.isGlobalTop) {
                return i18n.t('CfilesModule.base', '{count, plural, one{# result} other{# results}} in your spaces', { count: this.total });
            }
            return this.folder
                ? i18n.t('CfilesModule.base', '{count, plural, one{# result} other{# results}} in this folder and its subfolders', { count: this.total })
                : i18n.t('CfilesModule.base', '{count, plural, one{# result} other{# results}} in all files', { count: this.total });
        },
    },
    created() {
        // Before the FilterBar reads the URL (it is created with the first render).
        this.alignUrl();
        // Opened inside a space with topics: the page filtered by those of the space
        // (GlobalController), the bar shows them once the picker said which they are.
        if (this.global && this.container && isSet(this.query[TOPIC_KEY])) {
            this.checkTopics(this.container);
        }
    },
    methods: {
        /**
         * Puts the page URL's filters and sort in line with what the first page was built with
         * (`initialFilters`, the payload's sort): a value the server trimmed or refused, or a
         * sort it did not use, would otherwise be applied by the bar on mount — a second load,
         * a `422`, or `sort=default`, which forgets the user's stored sort.
         */
        alignUrl() {
            const params = new URLSearchParams(window.location.search);
            const before = params.toString();

            Object.entries(this.query).forEach(([key, value]) => {
                if (isSet(value)) {
                    params.set(key, urlValue(value));
                } else {
                    params.delete(key);
                }
            });
            // Kept only when it is the sort the list used (and the select offers).
            if (params.has('sort') && params.get('sort') !== this.filterValues.sort) {
                params.delete('sort');
            }

            if (params.toString() === before) {
                return;
            }

            const query = params.toString();
            const path = window.location.pathname + (query ? '?' + query : '') + window.location.hash;
            const state = window.history.state;
            // PJAX compares its own `state.url` on popstate (see the FilterBar); kept current.
            const next = state && typeof state === 'object' && typeof state.url === 'string'
                ? { ...state, url: window.location.origin + path }
                : state;
            window.history.replaceState(next, '', path);
        },
        /** The values of the list's filters the page URL carries (`''` = not set). */
        queryFromUrl() {
            const params = new URLSearchParams(window.location.search);
            return normalizeQuery(
                Object.fromEntries(Object.keys(this.query).map((key) => [key, params.get(key) ?? ''])),
                [...this.filters, ...this.containerFilters],
            );
        },
        /**
         * The filters a level's endpoint knows, with their values: the space browser sends them
         * all; the global page those of the level's definitions — a space's endpoint answers
         * `422` for the global page's own (Space), whose value is kept for the way back.
         */
        levelFilters(container) {
            return this.scopeTopics(
                levelFilters(this.query, this.global ? (container ? this.containerFilters : this.filters) : null),
                container,
            );
        },
        /**
         * The values as a space of the global page takes them: of the topics, those that are the
         * space's or global (`topicScopes`) — the space's endpoint refuses the others, which
         * `query` keeps for the way back. Unchanged elsewhere.
         */
        scopeTopics(values, container) {
            if (!this.global || !container || !(TOPIC_KEY in values)) {
                return values;
            }
            return { ...values, [TOPIC_KEY]: scopedIds(values[TOPIC_KEY], this.topicScopes[container.contentContainerId]) };
        },
        /**
         * Asks the picker, once, which of the chosen topics a space of the global page takes
         * (`topicScopes`); settles when it knows — also when asking failed, which leaves the ids
         * it could not check out of that space's filter.
         */
        checkTopics(container) {
            const containerId = container.contentContainerId;
            const unknown = idsOf(this.query[TOPIC_KEY]).filter((id) => !(id in (this.topicScopes[containerId] || {})));
            if (!unknown.length) {
                return Promise.resolve();
            }
            return resolveTopics(containerId, unknown).then((taken) => {
                const scope = { ...(this.topicScopes[containerId] || {}) };
                unknown.forEach((id) => (scope[id] = taken.includes(id)));
                this.topicScopes = { ...this.topicScopes, [containerId]: scope };
            }).catch((error) => log.error(error));
        },
        /**
         * Applies what the FilterBar emits: the filters and the sort select. Nothing changed
         * (the bar's first look at a URL the page was built from) is no reload.
         *
         * A cleared select (`''`) asks for `default` explicitly: sending no sort would have the
         * server re-apply the stored one, and the clear would do nothing. `default` is the
         * module's order, and the server forgets the stored one.
         */
        onFilters(values) {
            const { sort: chosen, ...filters } = values;
            const sort = chosen || SORT_DEFAULT;
            if (this.global && this.container && TOPIC_KEY in filters) {
                // The bar shows the space's topics only: the others stay for the way back.
                filters[TOPIC_KEY] = mergeScoped(filters[TOPIC_KEY], this.query[TOPIC_KEY], this.topicScopes[this.container.contentContainerId]);
            }
            const query = { ...this.query, ...filters };

            if (sort === this.sort && JSON.stringify(query) === JSON.stringify(this.query)) {
                return;
            }

            this.sort = sort;
            this.query = query;
            this.reload();
        },
        /**
         * Clears the filters, keeps the sort — the empty result list's way out. The bar takes
         * the new values as they are (a `modelValue` from outside), so this is one load.
         */
        resetFilters() {
            this.query = Object.fromEntries(Object.keys(this.query).map((key) => [key, '']));
            this.reload();
        },
    },
};
