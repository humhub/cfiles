<template>
    <div
        class="cfiles-row d-flex align-items-center gap-2"
        :class="{ 'is-drop-target': dropTarget, 'is-selected': selected, 'is-uploading': item.uploading }"
        :draggable="draggable && !item.uploading"
        @click="onRowClick"
        @contextmenu="onContextMenu"
        @dragstart="onDragStart"
        @dragend="onDragEnd"
        @dragover="onDragOver"
        @dragleave="onDragLeave"
        @drop="onDrop"
    >
        <template v-if="isUpload">
            <div class="cfiles-row-icon">
                <i :class="iconClass" aria-hidden="true"></i>
            </div>

            <div class="flex-grow-1 min-width-0">
                <h4 class="mb-0 text-truncate">{{ displayTitle }}</h4>
                <div class="progress cfiles-row__progress">
                    <div
                        class="progress-bar"
                        role="progressbar"
                        :style="{ width: (item.progress || 0) + '%' }"
                        :aria-valuenow="item.progress || 0"
                        aria-valuemin="0"
                        aria-valuemax="100"
                        :aria-label="uploadingLabel"
                    ></div>
                </div>
            </div>
        </template>

        <template v-else>
            <div v-if="selectable" class="cfiles-row-select">
                <input
                    type="checkbox"
                    class="form-check-input"
                    :checked="selected"
                    :aria-label="selectLabel"
                    @click="onCheck"
                />
            </div>

            <div class="cfiles-row-icon">
                <img v-if="item.previewUrl" :src="item.previewUrl" :alt="''" draggable="false" class="cfiles-thumb" />
                <i v-else :class="iconClass" aria-hidden="true"></i>
            </div>

            <div class="flex-grow-1 min-width-0">
                <h4 class="mb-0 d-flex align-items-center gap-1">
                    <a
                        ref="titleLink"
                        :href="linkUrl"
                        v-bind="linkAttributes"
                        draggable="false"
                        class="text-truncate"
                        @click="onOpen"
                    >{{ displayTitle }}</a>
                    <i
                        v-if="isPrivate"
                        class="ti ti-lock text-muted flex-shrink-0"
                        :title="privateLabel"
                        :aria-label="privateLabel"
                    ></i>
                </h4>
                <h5 class="mb-0 text-truncate cfiles-row-meta">{{ meta }}<template v-if="location"> · <a
                    class="cfiles-location"
                    :href="folderUrl(location.folder.id)"
                    draggable="false"
                    @click="onOpenLocation"
                >{{ location.label }}</a></template></h5>
            </div>

            <!-- The avatar and the like link are links (and an image) that would start a native
                 drag of their own; in Chrome an image drag carries `Files` and would read as an
                 upload onto a folder. -->
            <div v-if="likeState" class="cfiles-row-social" @dragstart.prevent.stop>
                <LikeButton
                    :record-id="item.recordId"
                    :like-count="likeState.total"
                    :current-user-liked="likeState.liked"
                />
            </div>

            <div class="cfiles-row-creator" @dragstart.prevent.stop>
                <UserImage v-if="item.creator" v-bind="item.creator" :size="21" />
            </div>

            <div class="cfiles-row-controls">
                <ContentControls
                    ref="controls"
                    :content-id="item.contentId"
                    :view-context="CONTROLS_VIEW_CONTEXT"
                    :entries="entries"
                    :suppress="SUPPRESSED_CORE_ENTRIES"
                    :context="{ item }"
                    toggle-class="nav-link dropdown-toggle cfiles-row-toggle"
                    :toggle-aria-label="actionsLabel"
                />
            </div>
        </template>
    </div>
</template>

<script>
/**
 * One row of the file browser: a folder or a file, in the platform's own `.hh-list` row
 * shape (`h4` title, `h5` meta line) so it inherits theme colours, hover and the accent
 * border without a stylesheet of its own.
 *
 * Both kinds share this component on purpose — they differ in the icon, what the title links
 * to and which context-menu entries apply, and in nothing else. An upload in progress
 * (`uploading: true`) is a row too: its name and a progress bar, nothing to select, drag or
 * open a menu on. A hit of a result list names the folder it lies in at the end of its meta
 * line, a link that opens that folder.
 *
 * Speaks the core `TileGrid`'s event vocabulary — `toggle-select(item, { range })`,
 * `drag-start`/`drag-end`/`drag-over`/`drag-leave`/`drop-on(item, event)`, with `canDrop`
 * deciding where a drag may land — so the browser treats the list and the tiles alike.
 */
import {
    itemLocation, itemMeta, fileIcon, isPlainClick, CONTROLS_VIEW_CONTEXT, SUPPRESSED_CORE_ENTRIES,
} from './itemPresentation';
import { i18n } from '@humhub/vue';

export default {
    props: {
        item: { type: Object, required: true },
        selected: { type: Boolean, default: false },
        selectable: { type: Boolean, default: false },
        draggable: { type: Boolean, default: false },
        dropTarget: { type: Boolean, default: false },
        /** `(item, event) => bool` — whether a drag may land on this row (TileGrid's `canDrop`). */
        canDrop: { type: Function, default: () => false },
        entries: { type: Array, default: () => [] },
        folderUrl: { type: Function, required: true },
        /**
         * `recordId => {total, liked, canLike}` for the whole page, as the listing payload
         * carries it. Empty where the like module is off, which is what hides the button.
         */
        likeStates: { type: Object, default: () => ({}) },
    },
    emits: ['open', 'toggle-select', 'drag-start', 'drag-end', 'drag-over', 'drag-leave', 'drop-on'],
    data() {
        return { CONTROLS_VIEW_CONTEXT, SUPPRESSED_CORE_ENTRIES };
    },
    created() {
        // Whether this row started the drag in flight - a plain field, not reactive: `dragend`
        // is paired with our own `dragstart`, even if the row stopped being draggable since.
        this.dragging = false;
    },
    computed: {
        isUpload() {
            return !!this.item.uploading;
        },
        isFolder() {
            return this.item.type === 'folder';
        },
        isPrivate() {
            return this.item.visibility === 0;
        },
        displayTitle() {
            return this.item.title;
        },
        linkUrl() {
            // A folder link is a real page URL even though opening it never navigates — that
            // is what keeps middle-click, "open in new tab" and copy-link working. A file
            // links wherever the server said, which is not always the file itself: a module
            // may have contributed a viewer or an editor for it (see FileSerializer::link()).
            return this.isFolder
                ? this.folderUrl(this.item.id)
                : (this.item.link?.url || this.item.url || '#');
        },
        /** Attributes the file's link needs — the download hooks, or the modal target. */
        linkAttributes() {
            return this.isFolder ? {} : (this.item.link?.attributes || {});
        },
        iconClass() {
            return this.isFolder
                ? 'ti ti-folder-filled cfiles-icon-folder'
                : 'ti ' + fileIcon(this.item) + ' cfiles-icon-file';
        },
        meta() {
            // The line is cut at its end: where a hit lies takes the description's place.
            return itemMeta(this.item, { description: !this.location });
        },
        /** Where a hit of a result list lies — null for an item directly in the open folder. */
        location() {
            return itemLocation(this.item);
        },
        /** This row's like state, or null when there is nothing to render a button from. */
        likeState() {
            const state = this.likeStates[this.item.recordId];

            return state && (state.canLike || state.total > 0) ? state : null;
        },
        privateLabel() {
            return i18n.t('CfilesModule.base', 'Private');
        },
        selectLabel() {
            return i18n.t('CfilesModule.base', 'Select {name}', { name: this.item.title });
        },
        uploadingLabel() {
            return i18n.t('base', 'Uploading...');
        },
        actionsLabel() {
            return i18n.t('base', 'Actions');
        },
    },
    methods: {
        /**
         * The row is one big click target for the item it shows — a file browser where only
         * the name is clickable makes every open a precision exercise.
         *
         * Everything inside the row that means something else keeps its own click: the select
         * checkbox, the context menu, the creator's profile link, and the title link itself,
         * which is also what a click here ends up going through.
         */
        onRowClick(event) {
            if (event.target.closest('a, button, input, label, .dropdown-menu')) {
                return;
            }
            if (event.metaKey || event.ctrlKey || event.shiftKey || event.altKey || event.button !== 0) {
                return;
            }
            // A click that ends a text selection inside this row is a selection, not an open.
            const selection = window.getSelection ? window.getSelection() : null;
            if (selection && !selection.isCollapsed && this.$el.contains(selection.anchorNode)) {
                return;
            }

            this.openItem();
        },
        /**
         * Raises this item's context menu where the cursor is, the way the platform's legacy
         * `$.fn.contextMenu` did for server-rendered lists (see `humhub.ui.additions.js`).
         */
        onContextMenu(event) {
            // An upload has no menu of its own: leave the browser's.
            if (this.isUpload) {
                return;
            }
            // Ctrl+right-click asks for the browser's own menu — the same escape hatch the
            // legacy plugin left open.
            if (event.ctrlKey) {
                return;
            }
            // A right-click inside the open menu belongs to the menu.
            if (event.target.closest('.dropdown-menu')) {
                return;
            }

            event.preventDefault();
            // A keyboard-raised menu (Menu key, Shift+F10) has no pointer position worth
            // using: open it under the row's own toggle instead.
            this.$refs.controls?.open(event.button === 2 ? event : null);
        },
        onCheck(event) {
            this.$emit('toggle-select', this.item, { range: event.shiftKey });
            // The browser has already flipped the box; put it back to what `selected` says, in
            // case the owner did not (or not yet) change it.
            this.$nextTick(() => {
                event.target.checked = this.selected;
            });
        },
        openItem() {
            if (this.isFolder) {
                this.$emit('open', this.item);
                return;
            }

            // A file's link is not always the file itself: a module may have contributed a
            // viewer, an editor, a modal or a download carrying its own data attributes (see
            // `FileSerializer::link()`), some of them read off the DOM by delegated document
            // handlers. Clicking the real anchor is what keeps every one of those working.
            if (this.$refs.titleLink) {
                this.$refs.titleLink.click();
            }
        },
        onOpen(event) {
            if (!this.isFolder) {
                return;
            }
            // Let the browser handle any click that means "somewhere else": modifier keys and
            // anything but the primary button.
            if (event.metaKey || event.ctrlKey || event.shiftKey || event.altKey || event.button !== 0) {
                return;
            }
            event.preventDefault();
            this.$emit('open', this.item);
        },
        /**
         * Opens the folder a hit of a result list lies in (`open` with that folder, as a
         * folder row emits it); any other click follows the link.
         */
        onOpenLocation(event) {
            if (!isPlainClick(event)) {
                return;
            }
            event.preventDefault();
            this.$emit('open', this.location.folder);
        },
        onDragStart(event) {
            // Images are draggable on their own, and their dragstart bubbles up here.
            if (!this.draggable || this.item.uploading) {
                return;
            }
            if (event.dataTransfer) {
                event.dataTransfer.effectAllowed = 'move';
                // Firefox ignores a drag that sets no data at all.
                event.dataTransfer.setData('text/plain', this.item.type + ':' + this.item.id);
            }
            this.dragging = true;
            this.$emit('drag-start', this.item, event);
        },
        onDragEnd(event) {
            if (this.dragging) {
                this.dragging = false;
                this.$emit('drag-end', this.item, event);
            }
        },
        onDragOver(event) {
            if (this.canDrop(this.item, event)) {
                event.preventDefault();
                this.$emit('drag-over', this.item, event);
            }
        },
        onDragLeave(event) {
            // Moving onto the row's own children fires `dragleave` too. Older WebKit sends
            // `relatedTarget` null - then every leave emits, which is acceptable.
            if (!event.currentTarget.contains(event.relatedTarget)) {
                this.$emit('drag-leave', this.item, event);
            }
        },
        onDrop(event) {
            if (!this.canDrop(this.item, event)) {
                return;
            }
            event.preventDefault();
            event.stopPropagation();
            this.$emit('drop-on', this.item, event);
        },
    },
};
</script>
