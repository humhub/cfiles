<template>
    <div class="cfiles-browser">
        <!-- The handlers are the page's (core JS config), built for its request, whose URL names
             the space they create in: the global page's names none, and a handler would create
             its document in the wrong place, so it offers none. -->
        <BrowserHeader
            :connect-url="connectUrl"
            :settings-url="settingsUrl"
            :writable="writable"
            :handlers="global ? [] : fileHandlers"
            :filters="activeFilters"
            :filter-values="filterValues"
            @upload="pickFiles"
            @create-folder="showCreate = true"
            @handler="onHandlerChosen"
            @filters="onFilters"
        />

        <div class="cfiles-browser-body" :class="{ 'is-details-open': detailsItem }">
            <!-- The global top level holds spaces, not files: nothing to upload into. -->
            <component
                :is="isGlobalTop ? 'div' : 'DropZone'"
                class="card content-card p-2 cfiles-browser-card"
                v-bind="cardProps"
                v-on="cardListeners"
            >
                <!-- At the global top level it is the root alone: no crumb below it, no
                     selection, no drop target (the top level holds spaces). -->
                <PathBar
                    ref="pathBar"
                    :path="crumbs"
                    :root-label="rootLabel"
                    :root-url="rootUrl"
                    :can-drop="canDropOnCrumb"
                    :drop-target-id="crumbDropTargetId"
                    @navigate="onNavigate"
                    @drag-over="onCrumbDragOver"
                    @drag-leave="onCrumbDragLeave"
                    @drop-on="onDropOnCrumb"
                >
                    <template #end>
                        <SelectionMenu
                            :count="selection.length"
                            menu-id="cfiles.selection"
                            :entries="selectionEntries"
                            :context="{ items: selectedItems }"
                        />
                        <ViewSwitch :model-value="view" :options="viewOptions" :label="viewLabel" @update:model-value="setView" />
                    </template>
                </PathBar>

                <p v-if="resultsMode" class="cfiles-results text-muted" role="status">{{ resultsLabel }}</p>

                <component
                    :is="view === 'tiles' ? 'FileTiles' : 'ItemList'"
                    v-bind="viewProps"
                    @open="onOpen"
                    @toggle-select="toggleSelect"
                    @drag-start="onDragStart"
                    @drag-end="onDragEnd"
                    @drag-over="onItemDragOver"
                    @drag-leave="onItemDragLeave"
                    @drop-on="onDropOnItem"
                    @load-more="loadMore"
                >
                    <template #empty>
                        <EmptyLevel
                            :results="resultsMode"
                            :global-top="isGlobalTop"
                            :in-folder="!!folder"
                            :writable="writable"
                            @reset="resetFilters"
                        />
                    </template>
                </component>
            </component>

            <DetailsPanel
                v-if="detailsItem"
                ref="details"
                :key="keyOf(detailsItem)"
                :item="detailsItem"
                :location="detailsLocation"
                :like-state="detailsLikeState"
                :initial-tab="detailsTab"
                :show-download-count="showDownloadCount"
                @tab="(tab) => { detailsTab = tab; }"
                @close="closeDetails"
            />
        </div>

        <input
            ref="fileInput"
            type="file"
            multiple
            class="d-none"
            @change="onFilesPicked"
        />

        <UiModal v-model:show="showCreate" :title="createTitle" @opened="focusForm('createForm')">
            <CfilesItemForm
                v-if="showCreate"
                ref="createForm"
                :content-container-id="container ? container.contentContainerId : null"
                :parent-folder-id="folderId"
                :default-visibility="levelNewItemVisibility"
                @saved="onCreated"
                @cancel="showCreate = false"
            />
        </UiModal>

        <UiModal v-model:show="showEdit" :title="editTitle" @opened="focusForm('editForm')">
            <CfilesItemForm
                v-if="showEdit"
                ref="editForm"
                :item="editItem"
                :content-container-id="editContainerId"
                @saved="onUpdated"
                @cancel="showEdit = false"
            />
        </UiModal>

        <MoveDialog
            v-if="container"
            :show="showMove"
            :content-container-id="container.contentContainerId"
            :items="moveItemsList"
            :mode="transferMode"
            :current-path="path || []"
            :busy="moveBusy"
            :error="moveError"
            @close="showMove = false"
            @confirm="(id) => (transferMode === 'copy' ? copyTo(id, moveItemsList) : moveTo(id, moveItemsList))"
        />

        <ConflictDialog
            :show="conflicts.length > 0"
            :conflicts="conflicts"
            @resolve="settleConflicts"
            @cancel="settleConflicts('cancel')"
        />
    </div>
</template>

<script>
/**
 * The file browser island.
 *
 * ## Navigation without a router
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
 * ## Filters and results
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
 * ## The global files page
 *
 * With `global` the island is the page `/files` (`GlobalController`): the files of every space
 * of the user. Its top level (`container` null) is a tile per space (`type: 'space'` items: the
 * folder glyph with the space's image as a badge, how much it holds), loaded from
 * `GET cfiles/items` — nothing there to select, drag, drop on or add to. Opening a tile makes
 * that space the `container` and browses it as the space browser does, through the space's own
 * endpoints, with the right to write its level payload's `canWrite` says. The path bar's root
 * leads back to the tiles, the space's crumb to its top level. The URL is
 * `?space=<space id>&fid=<folder id>` and the filters; a history entry keeps the space with it.
 *
 * With a result filter the top level lists the hits across all spaces, each placed "in Space ›
 * Folder" (`ItemLocation`): the space part opens the space, the folder part the folder in it.
 * Moving works in one container, so a hit there has no Move; open its space for that.
 *
 * The bar's definitions follow the level: `filters` (with Space) at the top level,
 * `containerFilters` inside a space, whose Topic searches the topics of the space open. Every
 * value stays in `query` — a Space filter set at the top is kept inside a space and back —,
 * and a load sends only the keys of its level's definitions: a space's endpoint refuses the
 * others (`422`). A topic chosen at the top level that is not of the space opened (nor global)
 * is refused there too.
 *
 * ## Layout
 *
 * Built on the core's item-browser kit: a `PageHeader` (`BrowserHeader`: title, the settings
 * link, the `AddMenu`) with a `FilterBar` (the filters and the sort), and below it a `DropZone` card
 * holding the `PathBar` (with the `SelectionMenu` and the `ViewSwitch` at its end) and the open
 * level as `FileTiles` or as an `ItemList`. Both views speak the same event vocabulary, so selection (Shift ranges
 * included), drop targets and uploads are resolved here, once, for either.
 *
 * ## Structure
 *
 * One instance, split by concern into mixins (`browser/*Mixin.js`) the way the core's form
 * fields share `fieldMixin`: the views, the menus and the extension points talk to that one
 * instance, and a concern reaches what it needs of another through it. Each mixin's docblock
 * names what it uses without defining it.
 *
 */
import { i18n, log } from '@humhub/vue';
import BrowserHeader from './browser/BrowserHeader.vue';
import ConflictDialog from './browser/ConflictDialog.vue';
import DetailsPanel from './browser/DetailsPanel.vue';
import EmptyLevel from './browser/EmptyLevel.vue';
import FileTiles from './browser/FileTiles.vue';
import ItemList from './browser/ItemList.vue';
import MoveDialog from './browser/MoveDialog.vue';
import {
    keyOf, loadGlobalItems, loadItems, resolveTopics, savePreferences,
} from './browser/api';
import {
    SPACE_CRUMB, TOPIC_KEY, appendFilters, containerOf, idsOf, isPageLocation, isPageUrl, isSet, levelFilters, levelKey, levelUrl,
    mergeScoped, normalizeQuery, pageOf, resolveSpace, sameContainer, scopedIds, spaceIdFromUrl, urlValue,
} from './browser/globalLevel';
import { itemLocation } from './browser/itemPresentation';
import detailsMixin from './browser/detailsMixin';
import dragDropMixin from './browser/dragDropMixin';
import fileHandlersMixin from './browser/fileHandlersMixin';
import itemActionsMixin from './browser/itemActionsMixin';
import selectionMixin from './browser/selectionMixin';
import uploadMixin from './browser/uploadMixin';

/**
 * The page each view asks for (`FolderListingService::VIEWS`): a tile grid fits more on a
 * screen than a row list.
 */
export const PAGE_SIZES = { list: 50, tiles: 96 };

/** The list's order without a chosen sort (`FolderList::SORT_DEFAULT`). */
const SORT_DEFAULT = 'default';

export default {
    components: { BrowserHeader, ConflictDialog, DetailsPanel, EmptyLevel, FileTiles, ItemList, MoveDialog },
    mixins: [detailsMixin, dragDropMixin, fileHandlersMixin, itemActionsMixin, selectionMixin, uploadMixin],
    // A top-level island declares what its whole subtree needs, not only its own messages.
    i18nCategories: ['ActivityModule.base', 'CfilesModule.base', 'CommentModule.base', 'base', 'ContentModule.base', 'LikeModule.base', 'UserModule.base'],
    props: {
        /** The first page, embedded by the page controller so the first paint needs no request. */
        listing: { type: Object, required: true },
        /** Whether the user may write in the container — the space browser's; see `writable`. */
        canWrite: { type: Boolean, default: false },
        /**
         * Whether the user may manage the container's files — move them, and change those of
         * others. The space browser's, like `canWrite`; see `movable`.
         */
        canManage: { type: Boolean, default: false },
        /** Base URL of the browser page — `?fid=` is appended to it. The space browser's. */
        browseUrl: { type: String, default: '' },
        /**
         * The container whose tree this is. The API addresses levels by container plus an
         * optional parent folder, because the top level has no folder record to name.
         *
         * Required for the space browser. On the global page (`global`) it is the space open
         * on the first paint, `null` at its top level — `space` says which.
         */
        contentContainerId: { type: Number, default: null },
        /**
         * The global files page (`/files`, `GlobalController`): its top level is a tile per
         * space of the user (`loadGlobalItems()`), opening one browses that space's files. See
         * the class docblock.
         */
        global: { type: Boolean, default: false },
        /**
         * On the global page: the space open on the first paint, `{ id, contentContainerId,
         * guid, name, color, imageUrl }`, or null for the top level.
         */
        space: { type: Object, default: null },
        /** On the global page: its URL (`/files`) — `?space=&fid=` is appended to it. */
        globalUrl: { type: String, default: '' },
        /**
         * On the global page: the FilterBar definitions of a space's level (`FolderList`), used
         * inside a space. The global ones (`filters`) add Space, which a space's endpoint does
         * not know, and their Topic takes the topics of every space.
         */
        containerFilters: { type: Array, default: () => [] },
        /**
         * Key of an item to open the edit dialog for on mount, as `file:<id>` /
         * `folder:<id>`. A stream entry's Edit control links here rather than loading an edit
         * form of its own — this browser owns that dialog, and one form beats two.
         */
        editKey: { type: String, default: null },
        /** The FilterBar definitions (`FolderList::definitions()`): the filters and the sort. */
        filters: { type: Array, default: () => [] },
        /**
         * The values of the list's filters the first page was built with, by key (`''` = not
         * set) — the page URL's (`BrowseController::firstListing()`).
         */
        initialFilters: { type: Object, default: () => ({}) },
        /** The container's cfiles settings page, or null for those who may not change them. */
        settingsUrl: { type: String, default: null },
        /**
         * The dialog that connects the files as a network drive (WebDAV), or null while it is
         * not offered (`Module::getDavConnectionUrl()`).
         */
        connectUrl: { type: String, default: null },
        /** Whether the details show how often a file was downloaded (`Module::getDisplayDownloadCount()`). */
        showDownloadCount: { type: Boolean, default: false },
    },
    data() {
        return {
            folder: this.listing.folder,
            path: this.listing.path,
            items: this.listing.results,
            likeStates: this.listing.likeStates || {},
            // A key of the list's sorts, `default` for none chosen.
            sort: this.listing.sort || SORT_DEFAULT,
            // The values of the list's filters by key, `''` = not set; sent with every load (on
            // the global page only those the level's endpoint knows, see `levelFilters()`).
            query: normalizeQuery({ ...this.initialFilters }, [...this.filters, ...this.containerFilters]),
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
            // The payload's `view` is the stored preference; from here on the island owns it.
            view: this.listing.view,
            total: this.listing.total,
            page: this.listing.page,
            pages: this.listing.pages,

            loading: false,
            loadingMore: false,

            // On the global page: which topic ids each space takes (its own and the global ones),
            // by content container id — `{ [id]: true|false }`, asked once per id and space.
            topicScopes: {},

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
        rootUrl() {
            return this.global ? this.folderUrl(null, null) : this.folderUrl(null);
        },
        /** What both views take; each view only what it declares. */
        viewProps() {
            const common = {
                items: this.displayItems,
                selection: this.selection,
                selectable: this.writable,
                draggable: this.movable,
                canDrop: this.canDropOnItem,
                dropTargetKey: this.itemDropTargetKey,
                hasMore: this.page < this.pages,
                loading: this.loading,
                loadingMore: this.loadingMore,
                entriesFor: this.entriesFor,
                folderUrl: this.folderUrl,
            };
            return this.view === 'tiles'
                ? { ...common, level: this.level, direction: this.direction }
                : { ...common, canWrite: this.writable };
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
        /**
         * The bar's values: the filters, and the sort — the select shows an offered key;
         * `default` (and a key it does not offer) is its label.
         */
        filterValues() {
            const select = this.activeFilters.find((filter) => filter.key === 'sort');
            const offered = (select?.options || []).some((option) => option.value === this.sort);
            return this.scopeTopics({ ...this.query, sort: offered ? this.sort : '' }, this.container);
        },
        viewOptions() {
            return [
                { value: 'tiles', icon: 'layout-grid', label: i18n.t('CfilesModule.base', 'Tiles') },
                { value: 'list', icon: 'list', label: i18n.t('CfilesModule.base', 'List') },
            ];
        },
        rootLabel() {
            return i18n.t('CfilesModule.base', 'Files');
        },
        viewLabel() {
            return i18n.t('CfilesModule.base', 'View');
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
        // Not reactive, nothing renders from them. The latest `open()` wins: a response to an
        // earlier one is dropped, and so is a further page asked for before it.
        this.loadSeq = 0;
        // What the latest `open()` asked for — the level a reload or a popstate compares
        // against while it is still on its way.
        this.requested = { folderId: this.folderId, push: false, container: this.container };
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
        // Before the FilterBar reads the URL (it is created with the first render).
        this.alignUrl();
        // Opened inside a space with topics: the page filtered by those of the space
        // (GlobalController), the bar shows them once the picker said which they are.
        if (this.global && this.container && isSet(this.query[TOPIC_KEY])) {
            this.checkTopics(this.container);
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

        // The URL wins - see the class docblock. `fid=0`/absent is the top level, which the
        // embedded payload already represents, so only a mismatch is worth a request.
        if (urlFolderId !== null && (urlFolderId || null) !== this.folderId) {
            this.open(urlFolderId || null, { push: false }).then(this.stampIfShown);
        }
    },
    beforeUnmount() {
        // A load still on its way answers into a page PJAX has replaced: dropped like one
        // overtaken by a later load, so it pushes and stamps nothing there (see `open()`).
        this.loadSeq++;
        window.removeEventListener('popstate', this.onPopState);
    },
    methods: {
        keyOf,
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
        /** The page the current view asks for. */
        pageSize() {
            return PAGE_SIZES[this.view] || PAGE_SIZES.list;
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
        /**
         * Switches display, remembers it and reloads with the new view's page size.
         *
         * The preference is stored on its own (`PATCH preferences`), fire-and-forget: failing
         * to remember it is logged, the switch happens all the same.
         */
        setView(view) {
            if (view === this.view) {
                return;
            }

            this.view = view;
            savePreferences({ view }).catch((e) => log.error(e, true));
            this.reload();
        },
        loadMore() {
            // While the level (re)loads, its rows are about to be replaced.
            if (this.loading || this.loadingMore || this.page >= this.pages) {
                return;
            }
            this.loadingMore = true;

            const seq = this.loadSeq;
            const folderId = this.folderId;
            const container = this.container;
            // Overtaken by an `open()`, or of a level no longer shown: dropped, and
            // `loadingMore` is `open()`'s to reset then.
            const stale = () => seq !== this.loadSeq || folderId !== this.folderId || !sameContainer(container, this.container);

            this.fetchLevel(container, folderId, {
                sort: this.sort,
                page: this.page + 1,
                pageSize: this.pageSize(),
            })
                .then((payload) => {
                    if (stale()) {
                        return;
                    }
                    // Pages are offsets: an item added to the level since the last one pushed
                    // its rows down, and a row shown already comes again.
                    const shown = new Set(this.items.map(keyOf));
                    this.items = this.items.concat(payload.results.filter((row) => !shown.has(keyOf(row))));
                    // A further page brings the like states of its own rows only.
                    this.likeStates = { ...this.likeStates, ...(payload.likeStates || {}) };
                    this.page = payload.page;
                    this.pages = payload.pages;
                    this.total = payload.total;
                    this.loadingMore = false;
                })
                .catch((e) => {
                    if (stale()) {
                        return;
                    }
                    this.loadingMore = false;
                    log.error(e, true);
                });
        },
    },
};
</script>
