/**
 * The level the file browser shows — a folder, the container's top level or, on the global
 * page, its tile per space — and how it is loaded.
 *
 * The state is the level's payload (`FolderListingService::payload()`): its folder and path,
 * the rows and their like states, what the user may do there (`writable`, `movable`) and the
 * paging. The first one comes embedded (`listing`); every other is asked for by `open()`, which
 * is all a change of level is. `reload()` asks for the level shown again, `refresh()` brings it
 * up to date where it stands, the further pages and the selection kept.
 *
 * The latest load wins: a response to an `open()` overtaken by a later one is dropped, and so
 * is a further page asked for before it and a load answering after PJAX replaced the page
 * (`loadSeq`). While a level is on its way, the level an action is for is that one
 * (`targetFolderId()`, `targetContainer()`).
 *
 * A mixin of the island, see its docblock. Uses the filters (`sort`, `query`, `levelFilters()`,
 * `checkTopics()`), the history (`urlNames()`, `historyState()`, `folderUrl()`), `pageSize()`,
 * and resets the selection (`selection`, `anchorKey`) and the details (`detailsKey`) with the
 * level.
 */
import { log } from '@humhub/vue';
import { keyOf, loadGlobalItems, loadItems } from './api';
import { SORT_DEFAULT } from './filtersMixin';
import { TOPIC_KEY, containerOf, isSet, levelKey } from './globalLevel';

export default {
    data() {
        return {
            folder: this.listing.folder,
            path: this.listing.path,
            items: this.listing.results,
            likeStates: this.listing.likeStates || {},
            // The container the open level is of: `{ contentContainerId, space }`, `space` set on
            // the global page only. `null` is the global page's top level. Every request goes to
            // it; the space browser's never changes.
            container: this.global
                ? (this.space ? containerOf(this.space) : null)
                : { contentContainerId: this.contentContainerId, space: null },
            // Whether the user may write in the level shown: its payload's (see `writable`), the
            // page's `canWrite` until a payload says.
            levelCanWrite: typeof this.listing.canWrite === 'boolean' ? this.listing.canWrite : this.canWrite,
            // Whether the user may manage the files of the level shown, the same way.
            levelCanManage: typeof this.listing.canManage === 'boolean' ? this.listing.canManage : this.canManage,
            /** What a new item of the level shown is visible to (`newItemVisibility`). */
            levelNewItemVisibility: this.listing.newItemVisibility || 'public',
            // Whether the items are the filters' hits in the folder and its subfolders.
            resultsMode: this.listing.resultsMode === true,
            total: this.listing.total,
            page: this.listing.page,
            pages: this.listing.pages,

            loading: false,
            loadingMore: false,

            // The level the tiles show, and which way the last change went (for the slide).
            level: levelKey(this.global, this.global ? (this.space ? containerOf(this.space) : null) : null, this.listing.folder),
            direction: 'forward',
        };
    },
    computed: {
        /** The id of the level currently open — null at the top. */
        folderId() {
            return this.folder ? this.folder.id : null;
        },
        /** The global page's top level: a tile per space. */
        isGlobalTop() {
            return this.global && this.container === null;
        },
        /**
         * Whether the user may write in the level shown: what the level's payload says
         * (`canWrite`, see FolderListingService) — the space browser's page prop until one did —,
         * never at the global page's top level.
         */
        writable() {
            return this.container !== null && this.levelCanWrite;
        },
        /**
         * Whether the user may move the items of the level shown: the right to manage its
         * files (`canManage` of the level's payload) — the server's rule for a move.
         */
        movable() {
            return this.writable && this.levelCanManage;
        },
    },
    created() {
        // Not reactive, nothing renders from them. The latest `open()` wins: a response to an
        // earlier one is dropped, and so is a further page asked for before it.
        this.loadSeq = 0;
        // What the latest `open()` asked for — the level a reload or a popstate compares
        // against while it is still on its way.
        this.requested = { folderId: this.folderId, push: false, container: this.container };
    },
    beforeUnmount() {
        // A load still on its way answers into a page PJAX has replaced: dropped like one
        // overtaken by a later load, so it pushes and stamps nothing there (see `open()`).
        this.loadSeq++;
    },
    methods: {
        /** One page of a level: a space's (or the container's) or the global top level. */
        fetchLevel(container, folderId, options) {
            if (!container) {
                return loadGlobalItems({ ...options, filters: this.levelFilters(null) });
            }
            const load = () => loadItems(container.contentContainerId, folderId, { ...options, filters: this.levelFilters(container) });
            // Asked at once where there is nothing to check first.
            return this.global && isSet(this.query[TOPIC_KEY]) ? this.checkTopics(container).then(load) : load();
        },
        /** How deep a level is, for the direction the tiles slide in. */
        depthOf(container, path) {
            return (this.global && container ? 1 : 0) + (path || []).length;
        },
        applyPayload(payload, container = this.container) {
            // Set with the items, not before: the tile grid slides a new `level` in with
            // whatever items it has at that moment.
            this.direction = this.depthOf(container, payload.path) >= this.depthOf(this.container, this.path) ? 'forward' : 'back';
            this.container = container;
            // An endpoint that does not say (none of this module's) leaves it as it was.
            if (typeof payload.canWrite === 'boolean') {
                this.levelCanWrite = payload.canWrite;
            }
            if (typeof payload.canManage === 'boolean') {
                this.levelCanManage = payload.canManage;
            }
            if (payload.newItemVisibility) {
                this.levelNewItemVisibility = payload.newItemVisibility;
            }
            this.level = levelKey(this.global, container, payload.folder);
            this.folder = payload.folder;
            this.path = payload.path;
            this.items = payload.results;
            this.resultsMode = payload.resultsMode === true;
            this.likeStates = payload.likeStates || {};
            this.sort = payload.sort || SORT_DEFAULT;
            // Not `view`: the preference is saved on its own, and a payload may be served
            // before the new one is stored.
            this.total = payload.total;
            this.page = payload.page;
            this.pages = payload.pages;
            this.selection = [];
            this.anchorKey = null;
        },
        /**
         * Loads a level and shows it. Returns a promise that settles once it is shown (`true`),
         * overtaken or failed (`false`/nothing); a later call overtakes an earlier one still on
         * its way.
         */
        open(folderId, { push = true, container } = {}) {
            // On the global page a level may be of another space (`container`), or the top.
            const target = container === undefined ? this.container : container;
            const seq = ++this.loadSeq;
            this.requested = { folderId, push, container: target };
            this.loading = true;
            // A further page still on its way is stale by `seq` now (see `loadMore`).
            this.loadingMore = false;

            return this.fetchLevel(target, folderId, {
                sort: this.sort,
                pageSize: this.pageSize(),
            }).then((payload) => {
                if (seq !== this.loadSeq) {
                    return false;
                }
                this.applyPayload(payload, target);
                this.loading = false;

                // Not for the level the URL already names (a reload). Without `fid` it names
                // none: the page may have been opened from a link without one.
                if (push && !this.urlNames(target, folderId)) {
                    // The details were of an item of the level left. Its history entry keeps
                    // `details`, so going Back brings them back with it.
                    this.detailsKey = null;
                    // No `container` key: pjax's own popstate handler skips this state and
                    // leaves it to ours (see the class docblock). The global page keeps the
                    // space with it, for going back to it.
                    window.history.pushState({ cfiles: this.historyState(target, folderId) }, '', this.folderUrl(folderId));
                }
                return true;
            }).catch((e) => {
                if (seq !== this.loadSeq) {
                    return;
                }
                this.loading = false;
                log.error(e, true);
            });
        },
        /** The level shown — or, while one is loading, the level on its way. */
        targetFolderId() {
            return this.loading ? this.requested.folderId : this.folderId;
        },
        /** The container of the level shown — or, while one is loading, of the one on its way. */
        targetContainer() {
            return this.loading ? this.requested.container : this.container;
        },
        /** Reloads the level shown — or the one still opening, keeping its history entry. */
        reload() {
            return this.loading
                ? this.open(this.requested.folderId, { push: this.requested.push, container: this.requested.container })
                : this.open(this.folderId, { push: false });
        },
        /**
         * Brings the level shown up to date where it stands — after an upload into it, which adds
         * to the level rather than leaving it. Unlike `reload()` it fetches every page loaded so
         * far (the new file may sort onto any of them) and keeps what the reader set up there:
         * the further pages, the selection of the rows still there, the details. Like a load it
         * overtakes a further page on its way and is overtaken by an `open()`.
         *
         * While a level is opening there is nothing to keep: that one is reloaded instead.
         */
        refresh() {
            if (this.loading) {
                return this.reload();
            }
            const seq = ++this.loadSeq;
            // A further page still on its way is stale by `seq` now (see `loadMore`).
            this.loadingMore = false;
            const pages = Array.from({ length: this.page || 1 }, (_, index) => index + 1);

            return Promise.all(pages.map((page) => this.fetchLevel(this.container, this.folderId, {
                sort: this.sort,
                page,
                pageSize: this.pageSize(),
            }))).then((payloads) => {
                if (seq !== this.loadSeq) {
                    return false;
                }
                // Pages are offsets: a row the upload pushed onto the next page comes twice.
                const shown = new Set();
                this.items = payloads.flatMap((payload) => payload.results).filter((row) => {
                    const key = keyOf(row);
                    return !shown.has(key) && shown.add(key);
                });
                this.likeStates = Object.assign({}, ...payloads.map((payload) => payload.likeStates || {}));
                const last = payloads[payloads.length - 1];
                this.total = last.total;
                this.page = last.page;
                this.pages = last.pages;
                this.selection = this.selection.filter((key) => shown.has(key));
                if (!shown.has(this.anchorKey)) {
                    this.anchorKey = null;
                }
                return true;
            }).catch((e) => {
                if (seq === this.loadSeq) {
                    log.error(e, true);
                }
                return false;
            });
        },
    },
};
