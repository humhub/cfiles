/**
 * What the file browser does to items: create a folder, edit an item, move, copy and delete
 * items — the dialogs' state, the requests and what they report — and the menu of one item
 * that offers it (`entriesFor`).
 *
 * Each of them changes the level shown, which is reloaded once the request is in. The batch
 * endpoints answer per item (`{results, errors}`): what could not be done is reported item by
 * item (`reportErrors()`), or in the folder dialog when nothing could.
 *
 * A stream entry's Edit control links to the browser with the item (`editKey`) rather than
 * loading an edit form of its own: the browser owns that dialog, and one form beats two
 * (`openRequestedEdit()`).
 *
 * A mixin of the island, see its docblock. Uses the level shown (`items`, `container`,
 * `writable`, `movable`, `reload()`), the details (`detailsKey`, `showDetails()`,
 * `closeDetails()`), `folderUrl()` and `onOpen()`; the template holds the dialogs.
 */
import { i18n, log, modal, status } from '@humhub/vue';
import { copyItems, deleteItems, errorText, keyOf, moveItems } from './api';
import { itemLocation } from './itemPresentation';

export default {
    data() {
        return {
            showCreate: false,
            showEdit: false,
            editItem: null,

            showMove: false,
            /** What the folder dialog is open for: `move` or `copy`. */
            transferMode: 'move',
            moveItemsList: [],
            moveBusy: false,
            moveError: null,
        };
    },
    computed: {
        /**
         * The container of the item being edited: the one open, or — a hit of the global page's
         * results — the space its path starts with.
         */
        editContainerId() {
            return this.container?.contentContainerId ?? this.editItem?.path?.[0]?.contentContainerId ?? null;
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
        this.openRequestedEdit();
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
                    id: 'cfiles-details',
                    sortOrder: 5,
                    label: i18n.t('CfilesModule.base', 'Details'),
                    icon: 'info-circle',
                    onClick: () => this.showDetails(item),
                },
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
                    condition: (context) => this.movable && context.capabilities.canEdit === true,
                    onClick: () => this.openMove([item]),
                },
                {
                    id: 'cfiles-copy',
                    sortOrder: 55,
                    label: i18n.t('CfilesModule.base', 'Copy'),
                    icon: 'copy',
                    // A copy is new content of the caller: reading the item and adding to the
                    // level is all it takes. Not at the global page's top level, as Move.
                    condition: () => this.writable,
                    onClick: () => this.openCopy([item]),
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
            this.openFolderDialog(items, 'move');
        },
        openCopy(items) {
            this.openFolderDialog(items, 'copy');
        },
        /** Opens the folder dialog for moving or copying (`mode`) the items. */
        openFolderDialog(items, mode) {
            if (!items.length) {
                return;
            }
            this.transferMode = mode;
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
                // Nothing moved: the reasons per item, or a request that failed as a whole.
                const errors = Array.isArray(response?.errors) ? response.errors : null;
                this.moveError = errors ? errorText(errors[0]) : i18n.t('CfilesModule.base', 'Could not move the item!');
                if (!this.showMove) {
                    // A dragged move has no dialog to show the error in; the reload puts back
                    // whatever did not move after all.
                    if (errors) {
                        this.reportErrors(errors);
                    } else {
                        log.error(response, true);
                    }
                    this.reload();
                }
            });
        },
        /**
         * Copies the items into the folder `parentFolderId` of the open container, null for its
         * top level — also the level they are on, where the copies get indexed names.
         */
        copyTo(parentFolderId, items) {
            if (!items.length) {
                this.showMove = false;
                return;
            }

            this.moveBusy = true;

            copyItems(this.container.contentContainerId, items, parentFolderId).then((response) => {
                this.moveBusy = false;
                this.showMove = false;
                this.moveItemsList = [];
                this.reportErrors(response.errors);
                status('success', i18n.t('CfilesModule.base', '{count, plural, one{# item} other{# items}} copied', {
                    count: (response.results || []).length,
                }));
                this.reload();
            }).catch((response) => {
                this.moveBusy = false;
                // Nothing copied: the reasons per item, or a request that failed as a whole.
                const errors = Array.isArray(response?.errors) ? response.errors : null;
                this.moveError = errors ? errorText(errors[0]) : i18n.t('CfilesModule.base', 'Could not copy the item!');
                if (!errors) {
                    log.error(response);
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
                    if ((response.results || []).some((deleted) => keyOf(deleted) === this.detailsKey)) {
                        this.closeDetails();
                    }
                    this.reload();
                }).catch((response) => {
                    if (Array.isArray(response?.errors)) {
                        this.reportErrors(response.errors);
                    } else {
                        log.error(response, true);
                    }
                    // Part of the selection may be gone even so.
                    this.reload();
                });
            });
        },
        reportErrors(errors) {
            (errors || []).forEach((error) => {
                status('error', errorText(error));
            });
        },
    },
};
