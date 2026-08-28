<template>
    <div
        class="cfiles-row d-flex align-items-center gap-2"
        :class="{ 'cfiles-row-drop': dropTarget, selected: selected }"
        :draggable="draggable"
        @click="onRowClick"
        @contextmenu="onContextMenu"
        @dragstart="onDragStart"
        @dragend="$emit('drag-end')"
        @dragover="onDragOver"
        @dragleave="onDragLeave"
        @drop="onDrop"
    >
        <div v-if="selectable" class="cfiles-row-select">
            <input
                type="checkbox"
                class="form-check-input"
                :checked="selected"
                :aria-label="selectLabel"
                @change="$emit('toggle-select', item)"
            />
        </div>

        <div class="cfiles-row-icon">
            <img v-if="item.previewUrl" :src="item.previewUrl" :alt="''" class="cfiles-thumb" />
            <i v-else :class="iconClass" aria-hidden="true"></i>
        </div>

        <div class="flex-grow-1 min-width-0">
            <h4 class="mb-0 d-flex align-items-center gap-1">
                <a
                    ref="titleLink"
                    :href="linkUrl"
                    v-bind="linkAttributes"
                    class="text-truncate"
                    @click="onOpen"
                >{{ displayTitle }}</a>
                <i
                    v-if="isPrivate"
                    class="fa fa-lock text-muted flex-shrink-0"
                    :title="privateLabel"
                    :aria-label="privateLabel"
                ></i>
            </h4>
            <h5 class="mb-0 text-truncate cfiles-row-meta">{{ meta }}</h5>
        </div>

        <div v-if="likeState" class="cfiles-row-social">
            <LikeButton
                :record-id="item.recordId"
                :like-count="likeState.total"
                :current-user-liked="likeState.liked"
            />
        </div>

        <div class="cfiles-row-creator">
            <UserImage v-if="item.creator" v-bind="item.creator" :size="21" />
        </div>

        <div class="cfiles-row-controls">
            <ContentControls
                ref="controls"
                :content-id="item.contentId"
                view-context="browser"
                :entries="entries"
                :suppress="SUPPRESSED_CORE_ENTRIES"
                :context="{ item }"
                toggle-class="nav-link dropdown-toggle cfiles-row-toggle"
                :toggle-aria-label="actionsLabel"
            />
        </div>
    </div>
</template>

<script>
/**
 * One row of the file browser: a folder or a file, in the platform's own `.hh-list` row
 * shape (`h4` title, `h5` meta line) so it inherits theme colours, hover and the accent
 * border without a stylesheet of its own.
 *
 * Both kinds share this component on purpose — they differ in the icon, what the title links
 * to and which context-menu entries apply, and in nothing else.
 */
import { itemMeta, mimeIconClass, SUPPRESSED_CORE_ENTRIES } from './itemPresentation';
import { i18n } from '@humhub/vue';

export default {
    props: {
        item: { type: Object, required: true },
        selected: { type: Boolean, default: false },
        selectable: { type: Boolean, default: false },
        draggable: { type: Boolean, default: false },
        dropTarget: { type: Boolean, default: false },
        entries: { type: Array, default: () => [] },
        folderUrl: { type: Function, required: true },
        /**
         * `recordId => {total, liked, canLike}` for the whole page, as the listing payload
         * carries it. Empty where the like module is off, which is what hides the button.
         */
        likeStates: { type: Object, default: () => ({}) },
    },
    emits: ['open', 'toggle-select', 'drag-start', 'drag-end', 'drop-on'],
    data() {
        return { SUPPRESSED_CORE_ENTRIES };
    },
    computed: {
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
                ? 'fa fa-folder cfiles-icon-folder'
                : 'fa ' + mimeIconClass(this.item) + ' cfiles-icon-file';
        },
        meta() {
            return itemMeta(this.item);
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
            this.$refs.controls.open(event);
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
        onDragStart(event) {
            event.dataTransfer.effectAllowed = 'move';
            // Firefox ignores a drag that sets no data at all.
            event.dataTransfer.setData('text/plain', this.item.type + ':' + this.item.id);
            this.$emit('drag-start', this.item);
        },
        onDragOver(event) {
            if (!this.isFolder) {
                return;
            }
            event.preventDefault();
            event.dataTransfer.dropEffect = 'move';
        },
        onDragLeave() {
            if (this.isFolder) {
                this.$emit('drag-end');
            }
        },
        onDrop(event) {
            if (!this.isFolder) {
                return;
            }
            event.preventDefault();
            event.stopPropagation();
            this.$emit('drop-on', this.item);
        },
    },
};
</script>
