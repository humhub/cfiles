<template>
    <div class="cfiles-browser">
        <PageToolbar :title="titleLabel">
            <template #actions>
                <ViewSwitch :model-value="view" :options="viewOptions" :label="viewLabel" @update:model-value="setView" />
                <a
                    v-if="settingsUrl"
                    :href="settingsUrl"
                    class="btn btn-light c-icon-button"
                    :aria-label="settingsLabel"
                    :title="settingsLabel"
                ><i class="ti ti-settings" aria-hidden="true"></i></a>
                <!-- The handlers' markup is rendered for the page's own request, whose URL names
                     the space and folder they create in: the global page's names neither, and a
                     handler would create its document in the wrong place, so it has none. -->
                <AddMenu
                    v-if="writable"
                    :handlers-html="global ? '' : createHandlersHtml"
                    @upload="pickFiles"
                    @create-folder="showCreate = true"
                />
            </template>
            <FilterBar
                v-if="activeFilters.length"
                :filters="activeFilters"
                :model-value="filterValues"
                id-prefix="cfiles-filter"
                @update:model-value="onFilters"
            />
        </PageToolbar>

        <!-- The global top level holds spaces, not files: nothing to upload into. -->
        <component
            :is="isGlobalTop ? 'div' : 'DropZone'"
            class="cfiles-browser__card"
            v-bind="cardProps"
            v-on="cardListeners"
        >
            <!-- Nothing hangs off it at the global top level: no crumb below the root, no
                 selection, no drop target (the top level holds spaces). -->
            <PathBar
                v-if="!isGlobalTop"
                :path="crumbs"
                :root-label="rootLabel"
                :root-url="rootUrl"
                :can-drop="canDropOnCrumb"
                :drop-target-id="crumbDropTargetId"
                @navigate="onNavigate"
                @drag-over="(id) => { crumbDropTargetId = id; }"
                @drag-leave="(id) => { if (crumbDropTargetId === id) crumbDropTargetId = undefined; }"
                @drop-on="onDropOnCrumb"
            >
                <template #end>
                    <SelectionMenu
                        :count="selection.length"
                        menu-id="cfiles.selection"
                        :entries="selectionEntries"
                        :context="{ items: selectedItems }"
                    />
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
                @drag-over="(item) => { itemDropTargetKey = keyOf(item); }"
                @drag-leave="(item) => { if (itemDropTargetKey === keyOf(item)) itemDropTargetKey = null; }"
                @drop-on="onDropOnItem"
                @load-more="loadMore"
            >
                <template #empty>
                    <template v-if="resultsMode">
                        <p class="mb-2"><strong>{{ noResultsTitle }}</strong></p>
                        <button type="button" class="btn btn-light btn-sm" @click="resetFilters">{{ resetFiltersLabel }}</button>
                    </template>
                    <template v-else>
                        <p class="mb-0"><strong>{{ emptyTitle }}</strong></p>
                        <p class="mb-0">{{ emptyHint }}</p>
                    </template>
                </template>
            </component>
        </component>

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
            :busy="moveBusy"
            :error="moveError"
            @close="showMove = false"
            @confirm="(id) => moveTo(id, moveItemsList)"
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
 * Built on the core's item-browser kit: a `PageToolbar` (title, `ViewSwitch`, the settings link,
 * the `AddMenu`) with a `FilterBar` (the filters and the sort), and below it a `DropZone` card
 * holding the `PathBar` (with the `SelectionMenu` at its end) and the open level as `FileTiles`
 * or as an `ItemList`. Both views speak the same event vocabulary, so selection (Shift ranges
 * included), drop targets and uploads are resolved here, once, for either.
 *
 * ## Drag & drop
 *
 * Drop targets are folder tiles/rows and the path's ancestors: desktop files dropped on one
 * upload into it, dragged items move there. Files dropped anywhere else in the card upload into
 * the open level (`DropZone`). One owner, one vocabulary for tiles and rows.
 *
 * ## Uploads
 *
 * An upload into the open level shows a placeholder per file (`{ type: 'upload', id, title,
 * uploading: true, progress }`) after the items; one into a folder shown here marks that
 * folder as uploading instead. Both go once the batch is in and the level reloads.
 */
import { i18n, log, modal, status } from '@humhub/vue';
import AddMenu from './browser/AddMenu.vue';
import FileTiles from './browser/FileTiles.vue';
import ItemList from './browser/ItemList.vue';
import MoveDialog from './browser/MoveDialog.vue';
import {
    deleteItems, keyOf, loadGlobalItems, loadItems, moveItems, resolveTopics, savePreferences, uploadFiles,
} from './browser/api';
import {
    SPACE_CRUMB, TOPIC_KEY, appendFilters, containerOf, idsOf, isPageLocation, isPageUrl, isSet, levelFilters, levelKey, levelUrl,
    mergeScoped, normalizeQuery, pageOf, resolveSpace, sameContainer, scopedIds, spaceIdFromUrl, urlValue,
} from './browser/globalLevel';
import { itemLocation } from './browser/itemPresentation';

// Ids for upload placeholders, unique for the page's lifetime.
let uploadSeq = 0;

/**
 * The page each view asks for (`FolderListingService::VIEWS`): a tile grid fits more on a
 * screen than a row list.
 */
export const PAGE_SIZES = { list: 50, tiles: 96 };

/** The list's order without a chosen sort (`FolderList::SORT_DEFAULT`). */
const SORT_DEFAULT = 'default';

export default {
    components: { AddMenu, FileTiles, ItemList, MoveDialog },
    // A top-level island declares what its whole subtree needs, not only its own messages.
    i18nCategories: ['CfilesModule.base', 'base', 'ContentModule.base', 'LikeModule.base', 'UserModule.base'],
    props: {
        /** The first page, embedded by the page controller so the first paint needs no request. */
        listing: { type: Object, required: true },
        /** Whether the user may write in the container — the space browser's; see `writable`. */
        canWrite: { type: Boolean, default: false },
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
        /**
         * Server-rendered `<li>` markup of the file handlers modules contributed ("New
         * spreadsheet", "Import from …"), appended to the `AddMenu`.
         */
        createHandlersHtml: { type: String, default: '' },
        /** The FilterBar definitions (`FolderList::definitions()`): the filters and the sort. */
        filters: { type: Array, default: () => [] },
        /**
         * The values of the list's filters the first page was built with, by key (`''` = not
         * set) — the page URL's (`BrowseController::firstListing()`).
         */
        initialFilters: { type: Object, default: () => ({}) },
        /** The container's cfiles settings page, or null for those who may not change them. */
        settingsUrl: { type: String, default: null },
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
            // Whether the items are the filters' hits in the folder and its subfolders.
            resultsMode: this.listing.resultsMode === true,
            // The payload's `view` is the stored preference; from here on the island owns it.
            view: this.listing.view,
            total: this.listing.total,
            page: this.listing.page,
            pages: this.listing.pages,

            loading: false,
            loadingMore: false,
            selection: [],

            showCreate: false,
            showEdit: false,
            editItem: null,

            showMove: false,
            moveItemsList: [],
            moveBusy: false,
            moveError: null,

            // What an item drag in flight carries: the selection, or the one item dragged.
            dragged: [],
            // Two values, two conventions: TileGrid's/ItemList's `null` is "no target", while
            // PathBar's `null` is the root crumb and `undefined` is "no target".
            itemDropTargetKey: null,
            crumbDropTargetId: undefined,

            // On the global page: which topic ids each space takes (its own and the global ones),
            // by content container id — `{ [id]: true|false }`, asked once per id and space.
            topicScopes: {},

            // Placeholders for the files being uploaded into the open level.
            uploads: [],
            // Folders shown here that files are being uploaded into: key => { batch => progress }.
            uploadTargets: {},

            // The item the last plain selection click was on, where a Shift range starts.
            anchorKey: null,
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
         * The container of the item being edited: the one open, or — a hit of the global page's
         * results — the space its path starts with.
         */
        editContainerId() {
            return this.container?.contentContainerId ?? this.editItem?.path?.[0]?.contentContainerId ?? null;
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
        cardProps() {
            return this.isGlobalTop ? {} : { accept: this.writable, label: this.dropLabel, refusedLabel: this.refusedLabel };
        },
        cardListeners() {
            return this.isGlobalTop ? {} : { drop: (files) => this.upload(files, this.folderId) };
        },
        rootUrl() {
            return this.global ? this.folderUrl(null, null) : this.folderUrl(null);
        },
        selectedItems() {
            return this.items.filter((item) => this.selection.indexOf(keyOf(item)) !== -1);
        },
        /** The items with their grid key, the uploads in progress appended. */
        displayItems() {
            const items = this.items.map((item) => {
                const key = keyOf(item);
                const batches = this.uploadTargets[key];
                return batches
                    // The slowest batch: the folder is done once they all are.
                    ? { ...item, key, uploading: true, progress: Math.min(...Object.values(batches)) }
                    : { ...item, key };
            });
            const uploads = this.uploads.filter((upload) => upload.parent === this.folderId
                && upload.containerId === this.container?.contentContainerId);
            return items.concat(uploads.map((upload) => ({ ...upload, key: keyOf(upload) })));
        },
        /** What both views take; each view only what it declares. */
        viewProps() {
            const common = {
                items: this.displayItems,
                selection: this.selection,
                selectable: this.writable,
                draggable: this.writable,
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
                : { ...common, canWrite: this.writable, likeStates: this.likeStates };
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
        selectionEntries() {
            return [
                { id: 'select-all', sortOrder: 10, icon: 'checks', label: i18n.t('CfilesModule.base', 'Select all'), onClick: () => this.selectAll() },
                { id: 'move', sortOrder: 20, icon: 'arrows-left-right', label: i18n.t('CfilesModule.base', 'Move'), onClick: () => this.openMove(this.selectedItems) },
                { id: 'delete', sortOrder: 30, icon: 'trash', label: i18n.t('CfilesModule.base', 'Delete'), onClick: () => this.confirmDelete(this.selectedItems) },
                { id: 'divider', sortOrder: 90, divider: true },
                { id: 'clear', sortOrder: 100, icon: 'x', label: i18n.t('CfilesModule.base', 'Clear selection'), onClick: () => this.clearSelection() },
            ];
        },
        viewOptions() {
            return [
                { value: 'tiles', icon: 'layout-grid', label: i18n.t('CfilesModule.base', 'Tiles') },
                { value: 'list', icon: 'list', label: i18n.t('CfilesModule.base', 'List') },
            ];
        },
        titleLabel() {
            return i18n.t('CfilesModule.base', 'Files');
        },
        rootLabel() {
            return i18n.t('CfilesModule.base', 'Files');
        },
        viewLabel() {
            return i18n.t('CfilesModule.base', 'View');
        },
        settingsLabel() {
            return i18n.t('CfilesModule.base', 'Settings');
        },
        dropLabel() {
            return i18n.t('CfilesModule.base', 'Drop files here to upload them');
        },
        refusedLabel() {
            return i18n.t('CfilesModule.base', 'You cannot upload files here');
        },
        emptyTitle() {
            return this.isGlobalTop
                ? i18n.t('CfilesModule.base', 'You are not a member of a space with files yet.')
                : i18n.t('CfilesModule.base', 'This folder is empty.');
        },
        resultsLabel() {
            if (this.isGlobalTop) {
                return i18n.t('CfilesModule.base', '{count, plural, one{# result} other{# results}} in your spaces', { count: this.total });
            }
            return this.folder
                ? i18n.t('CfilesModule.base', '{count, plural, one{# result} other{# results}} in this folder and its subfolders', { count: this.total })
                : i18n.t('CfilesModule.base', '{count, plural, one{# result} other{# results}} in all files', { count: this.total });
        },
        noResultsTitle() {
            if (this.isGlobalTop) {
                return i18n.t('CfilesModule.base', 'No results in your spaces.');
            }
            return this.folder
                ? i18n.t('CfilesModule.base', 'No results in this folder and its subfolders.')
                : i18n.t('CfilesModule.base', 'No results in all files.');
        },
        resetFiltersLabel() {
            return i18n.t('CfilesModule.base', 'Reset filters');
        },
        emptyHint() {
            if (this.isGlobalTop) {
                return '';
            }
            return this.writable
                ? i18n.t('CfilesModule.base', 'Drop files here or use the buttons above.')
                : i18n.t('CfilesModule.base', 'Unfortunately you have no permission to upload/edit files.');
        },
        createTitle() {
            return i18n.t('CfilesModule.base', 'Add folder');
        },
        editTitle() {
            return this.editItem && this.editItem.type === 'folder'
                ? i18n.t('CfilesModule.base', 'Edit folder')
                : i18n.t('CfilesModule.base', 'Edit file');
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
        document.addEventListener('keydown', this.onKeydown);

        this.openRequestedEdit();

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
        window.removeEventListener('popstate', this.onPopState);
        document.removeEventListener('keydown', this.onKeydown);
    },
    methods: {
        keyOf,
        /**
         * Puts the cursor in a dialog's first field once the dialog is actually open.
         *
         * The modal focuses its own dialog element first (so Escape and the tab ring work
         * from the moment it appears), which is why this waits for `opened` rather than
         * focusing on mount.
         */
        focusForm(ref) {
            this.$refs[ref]?.focus();
        },
        /**
         * Opens the edit dialog for the item a deep link asked for.
         *
         * Looked up among the rows already received, so a link to something that is not on
         * this page — or no longer exists — simply opens the folder instead of failing.
         */
        openRequestedEdit() {
            if (!this.editKey) {
                return;
            }

            const match = this.items.find((item) => keyOf(item) === this.editKey);

            if (match) {
                this.openEdit(match);
            }
        },
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
        /** What a history entry of a level keeps (`cfiles`): the global page adds the space. */
        historyState(container, folderId) {
            return this.global ? { folderId, space: container?.space ?? null } : { folderId };
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
                return this.open(null, { container: containerOf(item) });
            }
            const space = item.space ?? itemLocation(item)?.space ?? null;
            if (this.global && space && !sameContainer(containerOf(space), this.container)) {
                return this.open(item.id, { container: containerOf(space) });
            }
            return this.open(item.id);
        },
        /** The path bar's `navigate`: on the global page the root is its top level. */
        onNavigate(id) {
            if (this.global && id === null) {
                return this.open(null, { container: null });
            }
            return this.open(id === SPACE_CRUMB ? null : id);
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
                    this.items = this.items.concat(payload.results);
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
        /**
         * Flips one item, or — with `range` (Shift) — adds everything between the last item
         * clicked and this one, in the order both views show. A Shift-click without an
         * earlier click (or after the anchor left the page) is a plain toggle.
         */
        toggleSelect(item, { range } = {}) {
            const key = keyOf(item);
            const keys = this.items.map(keyOf);

            if (range && this.anchorKey !== null && keys.includes(this.anchorKey) && keys.includes(key)) {
                const [from, to] = [keys.indexOf(this.anchorKey), keys.indexOf(key)].sort((a, b) => a - b);
                const span = keys.slice(from, to + 1);
                this.selection = keys.filter((k) => this.selection.includes(k) || span.includes(k));
            } else if (this.selection.includes(key)) {
                this.selection = this.selection.filter((k) => k !== key);
            } else {
                this.selection = this.selection.concat(key);
            }

            this.anchorKey = key;
        },
        /**
         * Selects every LOADED item.
         *
         * Deliberately not "everything in this folder": with paging that would arm the delete
         * action with rows the reader has never seen.
         */
        selectAll() {
            this.selection = this.items.map(keyOf);
        },
        clearSelection() {
            this.selection = [];
            this.anchorKey = null;
        },
        onKeydown(event) {
            // An open dialog or menu takes its own Escape; the selection it acts on stays.
            if (event.key !== 'Escape' || !this.selection.length || event.defaultPrevented
                || this.showMove || this.showCreate || this.showEdit) {
                return;
            }
            this.clearSelection();
        },

        // --- context menu ------------------------------------------------------------
        /**
         * The module's own entries. `ContentControls` merges them with what the server's
         * `WallEntryControls` stack resolves and with anything a module registered
         * client-side, so this list is only what cfiles itself contributes.
         */
        entriesFor(item) {
            const isFolder = item.type === 'folder';

            return [
                {
                    id: 'cfiles-open',
                    sortOrder: 10,
                    label: isFolder
                        ? i18n.t('CfilesModule.base', 'Open')
                        : i18n.t('CfilesModule.base', 'Download'),
                    icon: isFolder ? 'folder-open-filled' : 'download',
                    url: isFolder ? this.folderUrl(item.id, itemLocation(item)?.space ?? undefined) : (item.downloadUrl || item.url),
                    onClick: isFolder ? () => this.onOpen(item) : undefined,
                },
                {
                    id: 'cfiles-edit',
                    sortOrder: 40,
                    label: i18n.t('CfilesModule.base', 'Edit'),
                    icon: 'pencil',
                    condition: (context) => context.capabilities.canEdit === true,
                    onClick: () => this.openEdit(item),
                },
                {
                    id: 'cfiles-move',
                    sortOrder: 50,
                    label: i18n.t('CfilesModule.base', 'Move'),
                    icon: 'arrows-left-right',
                    // Not at the global page's top level: its hits lie in spaces of their own, and
                    // the move dialog and endpoint work in one container.
                    condition: (context) => this.writable && context.capabilities.canEdit === true,
                    onClick: () => this.openMove([item]),
                },
                {
                    id: 'cfiles-delete',
                    sortOrder: 60,
                    label: i18n.t('CfilesModule.base', 'Delete'),
                    icon: 'trash',
                    condition: (context) => context.capabilities.canDelete === true,
                    onClick: () => this.confirmDelete([item]),
                },
            ];
        },

        // --- mutations ---------------------------------------------------------------
        openEdit(item) {
            this.editItem = item;
            this.showEdit = true;
        },
        onCreated() {
            this.showCreate = false;
            this.reload();
        },
        onUpdated() {
            this.showEdit = false;
            this.editItem = null;
            this.reload();
        },
        openMove(items) {
            if (!items.length) {
                return;
            }
            this.moveItemsList = items;
            this.moveError = null;
            this.showMove = true;
        },
        moveTo(targetFolderId, items) {
            // In a result list the items may lie in subfolders: only a move of items that all
            // are in the target already is none.
            if (!items.length || items.every((item) => (item.parentFolderId ?? null) === targetFolderId)) {
                this.showMove = false;
                return;
            }

            this.moveBusy = true;

            moveItems(this.container.contentContainerId, items, targetFolderId).then((response) => {
                this.moveBusy = false;
                this.showMove = false;
                this.moveItemsList = [];
                this.reportErrors(response.errors);
                this.reload();
            }).catch((response) => {
                this.moveBusy = false;
                const first = response && response.errors && response.errors[0];
                this.moveError = first ? first.message : null;
                if (!this.showMove) {
                    // A dragged move has no dialog to show the error in; the reload puts back
                    // whatever did not move after all.
                    log.error(response, true);
                    this.reload();
                }
            });
        },
        confirmDelete(items) {
            if (!items.length) {
                return;
            }

            modal.confirm({
                header: i18n.t('CfilesModule.base', '<strong>Confirm</strong> delete'),
                body: i18n.t('CfilesModule.base', 'Do you really want to delete {count, plural, one{this item} other{these # items}} with all subcontent?', {
                    count: items.length,
                }),
                confirmText: i18n.t('CfilesModule.base', 'Delete'),
            }).then((confirmed) => {
                if (!confirmed) {
                    return;
                }

                deleteItems(items).then((response) => {
                    this.reportErrors(response.errors);
                    this.reload();
                }).catch((response) => {
                    log.error(response, true);
                });
            });
        },
        reportErrors(errors) {
            (errors || []).forEach((error) => {
                status('error', error.message || error.messages || '');
            });
        },

        // --- upload -----------------------------------------------------------------
        pickFiles() {
            this.$refs.fileInput.click();
        },
        onFilesPicked(event) {
            this.upload(event.target.files);
            // Clear it, or picking the same file twice in a row is a no-op.
            event.target.value = '';
        },
        /**
         * Uploads a batch into `parentId` (the open level by default).
         *
         * Into the open level each file gets a placeholder after the items (kept with that
         * level: `parent`); into a folder shown here (`targetKey`) that folder shows the
         * progress instead. A batch into a level not open says where it went (`title`) once
         * it is in. The level reloads only when the upload shows on it, and the placeholders
         * stay until the reloaded rows are there.
         */
        upload(files, parentId = this.folderId, { targetKey = null, title = null } = {}) {
            if (!files || !files.length || !this.writable) {
                return;
            }

            const batch = ++uploadSeq;
            // Where it goes: on the global page another space may be open by the time it is in.
            const container = this.container;
            const intoLevel = parentId === this.folderId;
            // Where it goes, named now: by the time it is in, another level may be open.
            const folderTitle = title ?? (intoLevel ? this.folder?.title : null);
            const placeholders = intoLevel
                ? Array.from(files).map((file) => ({
                    type: 'upload', id: ++uploadSeq, parent: parentId, containerId: container.contentContainerId, title: file.name, uploading: true, progress: 0, icon: 'file',
                }))
                : [];
            const ids = placeholders.map((upload) => upload.id);
            const mine = (upload) => ids.includes(upload.id);

            const progress = (percent) => {
                if (intoLevel) {
                    this.uploads = this.uploads.map((upload) => (mine(upload) ? { ...upload, progress: percent } : upload));
                } else if (targetKey) {
                    this.uploadTargets = {
                        ...this.uploadTargets,
                        [targetKey]: { ...this.uploadTargets[targetKey], [batch]: percent },
                    };
                }
            };
            const done = () => {
                this.uploads = this.uploads.filter((upload) => !mine(upload));
                if (targetKey && this.uploadTargets[targetKey]) {
                    const { [batch]: gone, ...others } = this.uploadTargets[targetKey];
                    const { [targetKey]: all, ...rest } = this.uploadTargets;
                    this.uploadTargets = Object.keys(others).length ? { ...rest, [targetKey]: others } : rest;
                }
            };

            this.uploads = this.uploads.concat(placeholders);
            progress(0);

            uploadFiles(container.contentContainerId, parentId, files, progress).then((response) => {
                this.reportUploadErrors(response.errors);

                const here = sameContainer(container, this.targetContainer());
                const open = here && parentId === this.targetFolderId();
                const shown = open
                    || (here && targetKey !== null && this.items.some((item) => keyOf(item) === targetKey));
                const count = (response.results || []).length;

                if (!open && count) {
                    status('success', i18n.t('CfilesModule.base', '{count, plural, one{# file} other{# files}} uploaded to {folder}', {
                        count,
                        folder: folderTitle || container.space?.name || this.rootLabel,
                    }));
                }

                if (shown) {
                    this.reload().then(done);
                } else {
                    done();
                }
            }).catch((response) => {
                done();

                // The endpoint answers 422 only when NOTHING landed, and then says why per
                // file — that is a result, not a transport failure.
                if (response && response.status === 422 && Array.isArray(response.errors)) {
                    this.reportUploadErrors(response.errors);
                    return;
                }

                log.error(response, true);
            });
        },
        reportUploadErrors(errors) {
            (errors || []).forEach((error) => {
                status('error', error.fileName + ': ' + (error.messages || []).join(' '));
            });
        },

        // --- drag & drop ------------------------------------------------------------
        /** Whether a drag carries desktop files rather than one of our own items. */
        isFileDrag(event) {
            return Array.prototype.includes.call(event?.dataTransfer?.types || [], 'Files');
        },
        /**
         * A folder takes desktop files and the items being dragged — never itself, nor
         * anything from a reader who may not write.
         */
        canDropOnItem(item, event) {
            if (!this.writable || item.type !== 'folder') {
                return false;
            }
            if (this.isFileDrag(event)) {
                return true;
            }
            return this.dragged.length > 0 && !this.dragged.some((dragged) => keyOf(dragged) === keyOf(item));
        },
        canDropOnCrumb(id, event) {
            // The global page's root is its top level, which holds spaces, not files.
            if (this.global && id === null) {
                return false;
            }
            return this.writable && (this.isFileDrag(event) || this.dragged.length > 0);
        },
        onDragStart(item) {
            // Dragging one of the selected items moves the whole selection.
            this.dragged = this.selection.includes(keyOf(item)) ? this.selectedItems : [item];
        },
        /**
         * Ends a drag — on a drop (no `drag-leave` follows one, so the target is cleared here)
         * and on the `drag-end` the browser fires after it or after a cancelled drag.
         * Idempotent: whichever comes second finds nothing left to clear.
         */
        onDragEnd() {
            this.dragged = [];
            this.itemDropTargetKey = null;
            this.crumbDropTargetId = undefined;
        },
        onDropOnItem(folder, event) {
            const items = this.dragged;
            this.onDragEnd();

            if (this.isFileDrag(event)) {
                this.upload(event.dataTransfer.files, folder.id, { targetKey: keyOf(folder), title: folder.title });
                return;
            }

            this.moveTo(folder.id, items);
        },
        onDropOnCrumb(id, event) {
            const items = this.dragged;
            this.onDragEnd();
            // The space's crumb is its top level.
            const folderId = id === SPACE_CRUMB ? null : id;

            if (this.isFileDrag(event)) {
                const crumb = this.crumbs.find((level) => level.id === id);
                this.upload(event.dataTransfer.files, folderId, { title: crumb ? crumb.title : null });
                return;
            }

            this.moveTo(folderId, items);
        },
    },
};
</script>
