<template>
    <div
        class="cfiles-browser"
        :class="{ 'cfiles-dropping': fileDragActive }"
        @dragenter="onFileDragEnter"
        @dragover="onFileDragOver"
        @dragleave="onFileDragLeave"
        @drop="onFileDrop"
    >
        <div class="panel-body">
            <BrowserToolbar
                :path="path"
                :sort="sort"
                :order="order"
                :can-write="canWrite"
                :view="view"
                :create-handlers-html="createHandlersHtml"
                :drop-target-id="crumbDropTargetId"
                :folder-url="folderUrl"
                @open="open"
                @sort="setSort"
                @view="setView"
                @create-folder="showCreate = true"
                @pick-files="pickFiles"
                @crumb-drag-over="crumbDropTargetId = $event"
                @crumb-drag-leave="crumbDropTargetId = undefined"
                @crumb-drop="moveTo($event)"
            />

            <div v-if="uploadProgress !== null" class="progress cfiles-upload-progress my-2">
                <div
                    class="progress-bar"
                    role="progressbar"
                    :style="{ width: uploadProgress + '%' }"
                    :aria-valuenow="uploadProgress"
                    aria-valuemin="0"
                    aria-valuemax="100"
                >{{ uploadProgress }}%</div>
            </div>

            <ItemList
                :items="items"
                :selection="selection"
                :selectable="canWrite"
                :draggable="canWrite"
                :drop-target-key="itemDropTargetKey"
                :has-more="page < pages"
                :loading="loading"
                :loading-more="loadingMore"
                :can-write="canWrite"
                :view="view"
                :entries-for="entriesFor"
                :folder-url="folderUrl"
                :like-states="likeStates"
                @open="open($event.id)"
                @toggle-select="toggleSelect"
                @toggle-all="toggleAll"
                @move-selection="openMove(selectedItems)"
                @delete-selection="confirmDelete(selectedItems)"
                @load-more="loadMore"
                @drag-start="dragged = [$event]"
                @drag-end="itemDropTargetKey = null"
                @drop-on="onDropOnFolder"
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
                :content-container-id="contentContainerId"
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
                @saved="onUpdated"
                @cancel="showEdit = false"
            />
        </UiModal>

        <MoveDialog
            :show="showMove"
            :content-container-id="contentContainerId"
            :items="moveItemsList"
            :busy="moveBusy"
            :error="moveError"
            @close="showMove = false"
            @confirm="moveTo"
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
 * ## Drag & drop
 *
 * Two drags, no library. Files from the desktop onto the list upload into the open folder;
 * rows onto a folder row or onto a breadcrumb segment move them there (the breadcrumb is how
 * you move something UP, until Phase 2 adds the directory pane).
 */
import { i18n, log, modal, status } from '@humhub/vue';
import BrowserToolbar from './browser/BrowserToolbar.vue';
import ItemList from './browser/ItemList.vue';
import MoveDialog from './browser/MoveDialog.vue';
import { deleteItems, keyOf, loadItems, moveItems, uploadFiles } from './browser/api';

export default {
    components: { BrowserToolbar, ItemList, MoveDialog },
    // A top-level island declares what its whole subtree needs, not only its own messages.
    i18nCategories: ['CfilesModule.base', 'base', 'ContentModule.base', 'UserModule.base'],
    props: {
        /** The first page, embedded by the page controller so the first paint needs no request. */
        listing: { type: Object, required: true },
        canWrite: { type: Boolean, default: false },
        /** Base URL of the browser page — `?fid=` is appended to it. */
        browseUrl: { type: String, required: true },
        /**
         * The container whose tree this is. The API addresses levels by container plus an
         * optional parent folder, because the top level has no folder record to name.
         */
        contentContainerId: { type: Number, required: true },
        /**
         * Key of an item to open the edit dialog for on mount, as `file:<id>` /
         * `folder:<id>`. A stream entry's Edit control links here rather than loading an edit
         * form of its own — this browser owns that dialog, and one form beats two.
         */
        editKey: { type: String, default: null },
        /** See `BrowserToolbar`'s prop of the same name. */
        createHandlersHtml: { type: String, default: '' },
    },
    data() {
        return {
            folder: this.listing.folder,
            path: this.listing.path,
            items: this.listing.results,
            likeStates: this.listing.likeStates || {},
            sort: this.listing.sort,
            order: this.listing.order,
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

            dragged: [],
            itemDropTargetKey: null,
            // undefined, not null: null is the top-level crumb's own id (see BrowserToolbar).
            crumbDropTargetId: undefined,
            fileDragDepth: 0,

            uploadProgress: null,
        };
    },
    computed: {
        /** The id of the level currently open — null at the top. */
        folderId() {
            return this.folder ? this.folder.id : null;
        },
        selectedItems() {
            return this.items.filter((item) => this.selection.indexOf(keyOf(item)) !== -1);
        },
        fileDragActive() {
            return this.canWrite && this.fileDragDepth > 0;
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
    mounted() {
        window.addEventListener('popstate', this.onPopState);

        this.openRequestedEdit();

        const urlFolderId = this.folderIdFromUrl();

        // The URL wins - see the class docblock. `fid=0`/absent is the top level, which the
        // embedded payload already represents, so only a mismatch is worth a request.
        if (urlFolderId !== null && (urlFolderId || null) !== this.folderId) {
            this.open(urlFolderId || null, { push: false });
        }
    },
    beforeUnmount() {
        window.removeEventListener('popstate', this.onPopState);
    },
    methods: {
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
        folderUrl(folderId) {
            // `fid=0` is the top level, the shape links have always had.
            return this.browseUrl + (this.browseUrl.indexOf('?') === -1 ? '?' : '&')
                + 'fid=' + (folderId || 0);
        },
        folderIdFromUrl() {
            const value = new URLSearchParams(window.location.search).get('fid');
            return value === null ? null : parseInt(value, 10) || 0;
        },
        applyPayload(payload) {
            this.folder = payload.folder;
            this.path = payload.path;
            this.items = payload.results;
            this.likeStates = payload.likeStates || {};
            this.sort = payload.sort;
            this.order = payload.order;
            this.view = payload.view;
            this.total = payload.total;
            this.page = payload.page;
            this.pages = payload.pages;
            this.selection = [];
        },
        open(folderId, { push = true } = {}) {
            if (this.loading) {
                return;
            }
            this.loading = true;

            loadItems(this.contentContainerId, folderId, {
                sort: this.sort,
                order: this.order,
                view: this.view,
            }).then((payload) => {
                this.applyPayload(payload);
                this.loading = false;

                if (push) {
                    // No `container` key: pjax's own popstate handler skips this state and
                    // leaves it to ours (see the class docblock).
                    window.history.pushState({ cfiles: { folderId } }, '', this.folderUrl(folderId));
                }
            }).catch((e) => {
                this.loading = false;
                log.error(e, true);
            });
        },
        onPopState() {
            const folderId = this.folderIdFromUrl();

            if (folderId === null) {
                return;
            }

            const target = folderId === 0 ? null : folderId;

            if (target !== this.folderId) {
                this.open(target, { push: false });
            }
        },
        reload() {
            this.open(this.folderId, { push: false });
        },
        setSort(sort) {
            // Same column again reverses it; a different column starts ascending.
            this.order = this.sort === sort && this.order === 'asc' ? 'desc' : 'asc';
            this.sort = sort;
            this.reload();
        },
        /**
         * Switches display and reloads, the same way a sort change does.
         *
         * The reload is not just for symmetry: the endpoint is what remembers the preference
         * per user, and a tile grid asks for a bigger page than a row list.
         */
        setView(view) {
            if (view === this.view) {
                return;
            }

            this.view = view;
            this.reload();
        },
        loadMore() {
            if (this.loadingMore || this.page >= this.pages) {
                return;
            }
            this.loadingMore = true;

            loadItems(this.contentContainerId, this.folderId, {
                sort: this.sort,
                order: this.order,
                view: this.view,
                page: this.page + 1,
            })
                .then((payload) => {
                    this.items = this.items.concat(payload.results);
                    // A further page brings the like states of its own rows only.
                    this.likeStates = { ...this.likeStates, ...(payload.likeStates || {}) };
                    this.page = payload.page;
                    this.pages = payload.pages;
                    this.total = payload.total;
                    this.loadingMore = false;
                })
                .catch((e) => {
                    this.loadingMore = false;
                    log.error(e, true);
                });
        },
        toggleSelect(item) {
            const key = keyOf(item);
            const at = this.selection.indexOf(key);
            if (at === -1) {
                this.selection.push(key);
            } else {
                this.selection.splice(at, 1);
            }
        },
        /**
         * Selects every LOADED item, or clears the selection when they already are.
         *
         * Deliberately not "everything in this folder": with paging that would arm the delete
         * button with rows the reader has never seen.
         */
        toggleAll() {
            this.selection = this.selection.length === this.items.length
                ? []
                : this.items.map(keyOf);
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
                    icon: isFolder ? 'folder-open' : 'cloud-download',
                    url: isFolder ? this.folderUrl(item.id) : (item.downloadUrl || item.url),
                    onClick: isFolder ? () => this.open(item.id) : undefined,
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
                    icon: 'arrows',
                    condition: (context) => this.canWrite && context.capabilities.canEdit === true,
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
        moveTo(targetFolderId) {
            const items = this.moveItemsList.length ? this.moveItemsList : this.dragged;

            this.crumbDropTargetId = undefined;
            this.itemDropTargetKey = null;

            if (!items.length || targetFolderId === this.folderId) {
                this.showMove = false;
                return;
            }

            this.moveBusy = true;

            moveItems(this.contentContainerId, items, targetFolderId).then((response) => {
                this.moveBusy = false;
                this.showMove = false;
                this.moveItemsList = [];
                this.dragged = [];
                this.reportErrors(response.errors);
                this.reload();
            }).catch((response) => {
                this.moveBusy = false;
                const first = response && response.errors && response.errors[0];
                this.moveError = first ? first.message : null;
                if (!this.showMove) {
                    log.error(response, true);
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
        upload(files) {
            if (!files || !files.length || !this.canWrite) {
                return;
            }

            this.uploadProgress = 0;

            uploadFiles(this.contentContainerId, this.folderId, files, (percent) => {
                this.uploadProgress = percent;
            }).then((response) => {
                this.uploadProgress = null;
                this.reportUploadErrors(response.errors);
                this.reload();
            }).catch((response) => {
                this.uploadProgress = null;

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
        /** Whether a drag carries desktop files rather than one of our own rows. */
        isFileDrag(event) {
            const types = event.dataTransfer ? Array.prototype.slice.call(event.dataTransfer.types) : [];
            return types.indexOf('Files') !== -1;
        },
        onFileDragEnter(event) {
            if (!this.isFileDrag(event)) {
                return;
            }
            // Counting enter/leave rather than toggling: dragging over a child element fires
            // leave on the parent, which would otherwise flicker the overlay away.
            this.fileDragDepth++;
        },
        onFileDragOver(event) {
            if (!this.isFileDrag(event) || !this.canWrite) {
                return;
            }
            event.preventDefault();
            event.dataTransfer.dropEffect = 'copy';
        },
        onFileDragLeave(event) {
            if (!this.isFileDrag(event)) {
                return;
            }
            this.fileDragDepth = Math.max(0, this.fileDragDepth - 1);
        },
        onFileDrop(event) {
            if (!this.isFileDrag(event)) {
                return;
            }
            event.preventDefault();
            this.fileDragDepth = 0;
            this.upload(event.dataTransfer.files);
        },
        onDropOnFolder(folder) {
            const items = this.dragged.filter((item) => keyOf(item) !== keyOf(folder));
            this.itemDropTargetKey = null;

            if (!items.length) {
                return;
            }

            this.moveItemsList = items;
            this.moveTo(folder.id);
        },
    },
};
</script>
