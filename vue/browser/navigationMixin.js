/**
 * Navigation without a router: moving between the levels of the file browser, and the page
 * URL and history that follow it.
 *
 * Opening a folder does NOT navigate: the island fetches the folder, swaps its rows and
 * mirrors the change into the address bar with `history.pushState()`. The URL shape is
 * unchanged from the server-rendered browser (`?fid=<id>`), so every existing permalink,
 * notification link and search result still lands in the right folder — and copying the URL
 * out of the address bar still produces one.
 *
 * That is a query parameter being mirrored, not a client-side router: one parameter, one
 * `pushState` call, one `popstate` handler. `jquery.pjax` ignores history states that carry
 * no `container` key, so pushing our own does not disturb platform navigation, and going back
 * from one of ours to a PJAX state still lets PJAX restore its page.
 *
 * On mount the URL wins over the `listing` prop. They agree on a normal page load (the prop
 * is derived from the URL server-side); they can disagree exactly once — PJAX restoring a
 * cached page whose embedded prop still names the folder that was open when it was cached.
 *
 * On the global page the URL is `?space=<space id>&fid=<folder id>` and the filters, and a
 * history entry keeps the space with it (`historyState()`); the path bar's root leads back to
 * the tiles, the space's crumb to its top level (`crumbs`).
 *
 * A mixin of the island, see its docblock. Uses the level (`container`, `folderId`, `path`,
 * `items`, `requested`, `open()`, `targetFolderId()`, `targetContainer()`), the filters
 * (`query`, `sort`, `activeFilters`, `filterValues`, `queryFromUrl()`), `detailsKey` and the
 * `pathBar` ref.
 */
import {
    SPACE_CRUMB, appendFilters, containerOf, isPageLocation, isPageUrl, levelUrl, pageOf, resolveSpace, sameContainer,
    spaceIdFromUrl,
} from './globalLevel';
import { itemLocation } from './itemPresentation';

export default {
    computed: {
        rootUrl() {
            return this.global ? this.folderUrl(null, null) : this.folderUrl(null);
        },
        /**
         * The path bar's levels. On the global page the root is its top level and the space
         * comes first — its top level, then its folders.
         */
        crumbs() {
            const folders = (this.path || []).map((crumb) => ({ ...crumb, url: this.folderUrl(crumb.id) }));

            return this.global && this.container
                ? [{ id: SPACE_CRUMB, title: this.container.space.name, url: this.folderUrl(null) }, ...folders]
                : folders;
        },
    },
    created() {
        // The page as it was opened: a menu may link it by a URL other than the one pushed later
        // (see `onPopState`).
        this.openedAt = pageOf(window.location);
        // The entry the page was opened with says which level it is, as those pushed later do:
        // going Back to it must not depend on what the page happens to show then. Not while the
        // URL names another level than the embedded one (PJAX restoring a cached page): the
        // entry may say which that is, and `mounted()` opens it and stamps it then.
        if (this.urlNames(this.container, this.folderId)) {
            this.stampEntry();
        }
    },
    mounted() {
        window.addEventListener('popstate', this.onPopState);

        const urlFolderId = this.folderIdFromUrl();

        if (this.global) {
            // The same, for the space too: no `space` is the top level, whatever `fid` says.
            const spaceId = this.spaceIdFromUrl();
            if (spaceId !== (this.container?.space?.id ?? null) || (spaceId !== null && (urlFolderId || null) !== this.folderId)) {
                const container = spaceId === null ? null : this.containerOfSpaceId(spaceId);
                if (spaceId === null || container) {
                    this.open(container ? urlFolderId || null : null, { push: false, container }).then(this.stampIfShown);
                }
            }
            return;
        }

        // The URL wins - see the docblock above. `fid=0`/absent is the top level, which the
        // embedded payload already represents, so only a mismatch is worth a request.
        if (urlFolderId !== null && (urlFolderId || null) !== this.folderId) {
            this.open(urlFolderId || null, { push: false }).then(this.stampIfShown);
        }
    },
    beforeUnmount() {
        window.removeEventListener('popstate', this.onPopState);
    },
    methods: {
        /**
         * The page URL of a level, with the filters and the sort the bar shows — what the bar
         * writes into the URL itself, so a folder opened (or a link copied) keeps them.
         */
        folderUrl(folderId, space) {
            if (this.global) {
                // A folder of the space open, unless the caller names another (a hit's).
                return levelUrl(this.globalUrl, space === undefined ? (this.container?.space ?? null) : space, folderId, this.filterValues);
            }
            // `fid=0` is the top level, the shape links have always had.
            const params = appendFilters(new URLSearchParams({ fid: String(folderId || 0) }), this.filterValues);
            return this.browseUrl + (this.browseUrl.indexOf('?') === -1 ? '?' : '&') + params.toString();
        },
        /**
         * What a history entry of a level keeps (`cfiles`): the global page adds the space. A
         * copy of it: the browser clones the state, and the space shown is a reactive object it
         * refuses (`DataCloneError`). Its fields are all plain values (see `containerOf()`).
         */
        historyState(container, folderId) {
            return this.global ? { folderId, space: container?.space ? { ...container.space } : null } : { folderId };
        },
        /** Says in the current history entry which level is shown, keeping PJAX's own keys. */
        stampEntry() {
            window.history.replaceState({ ...(window.history.state || {}), cfiles: this.historyState(this.container, this.folderId) }, '');
        },
        /** `stampEntry()` once an `open()` showed its level — not when a later one overtook it. */
        stampIfShown(shown) {
            if (shown) {
                this.stampEntry();
            }
        },
        folderIdFromUrl() {
            const value = new URLSearchParams(window.location.search).get('fid');
            return value === null ? null : parseInt(value, 10) || 0;
        },
        /** The space the global page's URL names (`space`), null for its top level. */
        spaceIdFromUrl() {
            return spaceIdFromUrl(window.location.search);
        },
        /** Whether the page URL names that level already. A folder only counts in a space. */
        urlNames(container, folderId) {
            if (!this.global) {
                return this.folderIdFromUrl() === (folderId || 0);
            }
            const spaceId = this.spaceIdFromUrl();
            return spaceId === (container?.space?.id ?? null)
                && (spaceId === null || (this.folderIdFromUrl() || 0) === (folderId || 0));
        },
        /**
         * The container state of a space the global page's URL names, from what the island
         * knows of it (`resolveSpace()`), null when it knows nothing of it.
         */
        containerOfSpaceId(spaceId) {
            return resolveSpace(spaceId, {
                known: [this.container, this.requested?.container],
                state: window.history.state,
                items: this.items,
            });
        },
        /**
         * Opens what a view emits `open` with: a space tile (its top level), a folder of the
         * open level, or — on the global page — a folder in a space of its own (a hit, or the
         * folder part of a hit's location: its `space`) or a hit's space part.
         */
        onOpen(item) {
            if (item.type === 'space') {
                return this.go(null, { container: containerOf(item) });
            }
            const space = item.space ?? itemLocation(item)?.space ?? null;
            if (this.global && space && !sameContainer(containerOf(space), this.container)) {
                return this.go(item.id, { container: containerOf(space) });
            }
            return this.go(item.id);
        },
        /** The path bar's `navigate`: on the global page the root is its top level. */
        onNavigate(id) {
            if (this.global && id === null) {
                return this.go(null, { container: null });
            }
            return this.go(id === SPACE_CRUMB ? null : id);
        },
        /**
         * `open()` for where the user went in the browser: once the level is shown, the focus
         * is on it in the path bar — the row or crumb that had it may be gone with the level
         * left, and the next Tab continues in the level reached.
         */
        go(folderId, options = {}) {
            return this.open(folderId, options).then((shown) => {
                if (shown) {
                    this.$nextTick(() => this.$refs.pathBar?.focusCurrent());
                }
                return shown;
            });
        },
        onPopState() {
            // An entry of another page (PJAX's, after navigating away) is none of ours. This page
            // is the one the island pushes, and the one it was opened at.
            if (!isPageUrl(this.global ? this.globalUrl : this.browseUrl, window.location)
                && !isPageLocation(this.openedAt, window.location)) {
                return;
            }
            // Without `fid` an entry is the top level (a link such as `browse?q=…`).
            let target = this.folderIdFromUrl() || null;
            let container = this.container;
            if (this.global) {
                // Without `space` the global top level. A space it knows nothing of (a history
                // entry of another page's making) is the top level too.
                const spaceId = this.spaceIdFromUrl();
                container = spaceId === null ? null : this.containerOfSpaceId(spaceId);
                target = container ? target : null;
            }
            // The filters and the sort of the entry gone back to; the bar takes them as its new
            // values. An entry without a sort (or with one the select does not offer) keeps the
            // current one: the bar only writes a chosen sort.
            this.detailsKey = new URLSearchParams(window.location.search).get('details') || null;

            const query = this.queryFromUrl();
            const filtersChanged = JSON.stringify(query) !== JSON.stringify(this.query);
            const urlSort = new URLSearchParams(window.location.search).get('sort');
            const select = this.activeFilters.find((filter) => filter.key === 'sort');
            const sort = (select?.options || []).some((option) => option.value === urlSort) ? urlSort : this.sort;
            const sortChanged = sort !== this.sort;

            this.query = query;
            this.sort = sort;

            if (filtersChanged || sortChanged || target !== this.targetFolderId() || !sameContainer(container, this.targetContainer())) {
                this.open(target, { push: false, container });
            }
        },
    },
};
