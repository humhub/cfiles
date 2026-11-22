/**
 * Drag & drop in the file browser.
 *
 * Drop targets are folder tiles/rows and the path's ancestors: desktop files dropped on one
 * upload into it, dragged items move there. Files dropped anywhere else in the card upload into
 * the open level (`DropZone`, see `cardProps`). One owner, one vocabulary for tiles and rows:
 * both views and the `PathBar` report the drag, the targets and the drop here.
 *
 * A mixin of the island, see its docblock. Uses the level shown (`writable`, `isGlobalTop`,
 * `folderId`, `crumbs`), the selection (`selection`, `selectedItems`), `upload()` and `moveTo()`.
 */
import { i18n } from '@humhub/vue';
import { keyOf } from './api';
import { SPACE_CRUMB } from './globalLevel';

export default {
    data() {
        return {
            // What an item drag in flight carries: the selection, or the one item dragged.
            dragged: [],
            // Two values, two conventions: TileGrid's/ItemList's `null` is "no target", while
            // PathBar's `null` is the root crumb and `undefined` is "no target".
            itemDropTargetKey: null,
            crumbDropTargetId: undefined,
        };
    },
    computed: {
        /** The card's `DropZone` — none at the global top level, which holds spaces. */
        cardProps() {
            return this.isGlobalTop ? {} : { accept: this.writable, label: this.dropLabel, refusedLabel: this.refusedLabel };
        },
        cardListeners() {
            return this.isGlobalTop ? {} : { drop: (files) => this.upload(files, this.folderId) };
        },
        dropLabel() {
            return i18n.t('CfilesModule.base', 'Drop files here to upload them');
        },
        refusedLabel() {
            return i18n.t('CfilesModule.base', 'You cannot upload files here');
        },
    },
    methods: {
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
        onItemDragOver(item) {
            this.itemDropTargetKey = keyOf(item);
        },
        onItemDragLeave(item) {
            if (this.itemDropTargetKey === keyOf(item)) {
                this.itemDropTargetKey = null;
            }
        },
        onCrumbDragOver(id) {
            this.crumbDropTargetId = id;
        },
        onCrumbDragLeave(id) {
            if (this.crumbDropTargetId === id) {
                this.crumbDropTargetId = undefined;
            }
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
