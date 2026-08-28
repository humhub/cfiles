<template>
    <div
        class="cfiles-tile"
        :class="{ 'cfiles-tile-drop': dropTarget, selected: selected }"
        :draggable="draggable"
        @contextmenu="onContextMenu"
        @dragstart="onDragStart"
        @dragend="$emit('drag-end')"
        @dragover="onDragOver"
        @dragleave="onDragLeave"
        @drop="onDrop"
    >
        <div class="cfiles-tile-actions">
            <input
                v-if="selectable"
                type="checkbox"
                class="form-check-input"
                :checked="selected"
                :aria-label="selectLabel"
                @change="$emit('toggle-select', item)"
            />
            <ContentControls
                ref="controls"
                :content-id="item.contentId"
                view-context="browser"
                :entries="entries"
                :suppress="SUPPRESSED_CORE_ENTRIES"
                :context="{ item }"
                toggle-class="nav-link dropdown-toggle cfiles-tile-toggle"
                :toggle-aria-label="actionsLabel"
            />
        </div>

        <a :href="linkUrl" v-bind="linkAttributes" class="cfiles-tile-preview" :title="item.title" @click="onOpen">
            <img v-if="item.previewUrl" :src="item.previewUrl" :alt="''" />
            <i v-else :class="iconClass" aria-hidden="true"></i>
        </a>

        <div class="cfiles-tile-caption">
            <div class="cfiles-tile-name d-flex align-items-center gap-1">
                <a :href="linkUrl" v-bind="linkAttributes" class="cfiles-tile-title" :title="item.title" @click="onOpen">
                    {{ item.title }}
                </a>
                <i
                    v-if="isPrivate"
                    class="fa fa-lock text-muted flex-shrink-0"
                    :title="privateLabel"
                    :aria-label="privateLabel"
                ></i>
            </div>
            <span class="cfiles-tile-meta">{{ meta }}</span>
        </div>
    </div>
</template>

<script>
/**
 * One item as a tile — the same item `ItemRow` renders as a row, in a grid.
 *
 * Deliberately the same props, the same events and the same context menu as `ItemRow`, so
 * `ItemList` can swap one for the other without knowing anything about either. What differs
 * is only what a grid is for: the preview is large and the metadata is trimmed to what still
 * fits under a thumbnail.
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
        linkUrl() {
            // A file links wherever the server said, which is not always the file itself: a
            // module may have contributed a viewer or an editor (see FileSerializer::link()).
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
        /** Without the description: there is no room for it under a thumbnail. */
        meta() {
            return itemMeta(this.item, { description: false });
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
        /** Same right-click menu as a row's — see `ItemRow.onContextMenu()`. */
        onContextMenu(event) {
            if (event.ctrlKey) {
                return;
            }
            if (event.target.closest('.dropdown-menu')) {
                return;
            }

            event.preventDefault();
            this.$refs.controls.open(event);
        },
        onOpen(event) {
            if (!this.isFolder) {
                return;
            }
            if (event.metaKey || event.ctrlKey || event.shiftKey || event.altKey || event.button !== 0) {
                return;
            }
            event.preventDefault();
            this.$emit('open', this.item);
        },
        onDragStart(event) {
            event.dataTransfer.effectAllowed = 'move';
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
