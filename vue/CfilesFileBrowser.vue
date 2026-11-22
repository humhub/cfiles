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
 * The file browser island: the files of a space, or — with `global` — of every space of the
 * user (see below).
 *
 * ## Layout
 *
 * Built on the core's item-browser kit: a `PageHeader` (`BrowserHeader`: title, the settings
 * link, the `AddMenu`) with a `FilterBar` (the filters and the sort), and below it a `DropZone`
 * card holding the `PathBar` (with the `SelectionMenu` and the `ViewSwitch` at its end) and the
 * open level as `FileTiles` or as an `ItemList`. Both views speak the same event vocabulary, so
 * selection (Shift ranges included), drop targets and uploads are resolved here, once, for
 * either.
 *
 * ## Structure
 *
 * One instance, split by concern into mixins the way the core's form fields share
 * `fieldMixin`: the views, the menus and the extension points (the `cfiles.selection` menu, the
 * `cfiles.details` slot, `ContentControls`) talk to that one instance, and a concern reaches
 * what it needs of another through it. Each mixin's docblock names what it uses without
 * defining it; the island itself only wires their state into its children.
 *
 * - `levelMixin`: the level shown and how it is loaded (`open()`, `reload()`, `refresh()`)
 * - `pagingMixin`: the display (tiles or rows) and the further pages
 * - `navigationMixin`: going from level to level, the page URL and the history
 * - `filtersMixin`: the filters, the sort and the result list they make
 * - `selectionMixin`: the selection and its menu
 * - `itemActionsMixin`: creating, editing, moving, copying, deleting; the menu of one item
 * - `uploadMixin`: uploads, their placeholders and the names already taken
 * - `dragDropMixin`: items and desktop files dragged onto folders and crumbs
 * - `detailsMixin`: the item of the details panel, mirrored into the URL
 * - `fileHandlersMixin`: the file handlers of the add menu
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
 */
import { i18n } from '@humhub/vue';
import BrowserHeader from './browser/BrowserHeader.vue';
import ConflictDialog from './browser/ConflictDialog.vue';
import DetailsPanel from './browser/DetailsPanel.vue';
import EmptyLevel from './browser/EmptyLevel.vue';
import FileTiles from './browser/FileTiles.vue';
import ItemList from './browser/ItemList.vue';
import MoveDialog from './browser/MoveDialog.vue';
import { keyOf } from './browser/api';
import detailsMixin from './browser/detailsMixin';
import dragDropMixin from './browser/dragDropMixin';
import fileHandlersMixin from './browser/fileHandlersMixin';
import filtersMixin from './browser/filtersMixin';
import itemActionsMixin from './browser/itemActionsMixin';
import levelMixin from './browser/levelMixin';
import navigationMixin from './browser/navigationMixin';
import pagingMixin from './browser/pagingMixin';
import selectionMixin from './browser/selectionMixin';
import uploadMixin from './browser/uploadMixin';

export default {
    components: { BrowserHeader, ConflictDialog, DetailsPanel, EmptyLevel, FileTiles, ItemList, MoveDialog },
    // Their hooks run in this order: the level is set up before what reads it, and the edit a
    // link asks for opens before the URL may open another level.
    mixins: [
        levelMixin, pagingMixin, itemActionsMixin, navigationMixin, filtersMixin,
        selectionMixin, uploadMixin, dragDropMixin, detailsMixin, fileHandlersMixin,
    ],
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
    computed: {
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
        rootLabel() {
            return i18n.t('CfilesModule.base', 'Files');
        },
    },
    methods: {
        keyOf,
    },
};
</script>
