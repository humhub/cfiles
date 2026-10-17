<template>
    <div>
        <div v-if="items.length" class="hh-list cfiles-list">
            <ItemRow
                v-for="item in items"
                :key="keyOf(item)"
                :item="item"
                :selected="isSelected(item)"
                :selectable="selectable"
                :draggable="draggable"
                :can-drop="canDrop"
                :drop-target="dropTargetKey !== null && dropTargetKey === keyOf(item)"
                :entries="entriesFor(item)"
                :folder-url="folderUrl"
                :like-states="likeStates"
                @open="(item) => $emit('open', item)"
                @toggle-select="(item, flags) => $emit('toggle-select', item, flags)"
                @drag-start="(item, event) => $emit('drag-start', item, event)"
                @drag-end="(item, event) => $emit('drag-end', item, event)"
                @drag-over="(item, event) => $emit('drag-over', item, event)"
                @drag-leave="(item, event) => $emit('drag-leave', item, event)"
                @drop-on="(item, event) => $emit('drop-on', item, event)"
            />
        </div>

        <div v-else-if="!loading" class="cfiles-empty text-center text-muted p-4">
            <slot name="empty">
                <p class="mb-0"><strong>{{ emptyTitle }}</strong></p>
                <p class="mb-0">{{ emptyHint }}</p>
            </slot>
        </div>

        <div v-if="hasMore" class="text-center p-2">
            <button type="button" class="btn btn-light btn-sm" :disabled="loadingMore" @click="$emit('load-more')">
                {{ loadingMore ? loadingLabel : moreLabel }}
            </button>
        </div>
    </div>
</template>

<script>
/**
 * The open level as rows (`ItemRow` in the platform's `.hh-list`), plus the empty state
 * (slot `empty`, the folder-is-empty text by default) and the "show more" step.
 *
 * Speaks the same event vocabulary as `FileTiles` (the core `TileGrid`), so the browser
 * treats both views alike: `open(item)`, `toggle-select(item, { range })`,
 * `drag-start`/`drag-end`/`drag-over`/`drag-leave`/`drop-on(item, event)`, `load-more`.
 * `canDrop(item, event)` decides where a drag may land; `dropTargetKey` (`null` = none)
 * highlights one row.
 */
import { i18n } from '@humhub/vue';
import ItemRow from './ItemRow.vue';
import { keyOf } from './api';

export default {
    components: { ItemRow },
    props: {
        items: { type: Array, default: () => [] },
        selection: { type: Array, default: () => [] },
        selectable: { type: Boolean, default: false },
        draggable: { type: Boolean, default: false },
        canDrop: { type: Function, default: () => false },
        dropTargetKey: { type: [String, Number], default: null },
        hasMore: { type: Boolean, default: false },
        loading: { type: Boolean, default: false },
        loadingMore: { type: Boolean, default: false },
        canWrite: { type: Boolean, default: false },
        entriesFor: { type: Function, required: true },
        folderUrl: { type: Function, required: true },
        /** `recordId => {total, liked, canLike}`, handed straight to `ItemRow`. */
        likeStates: { type: Object, default: () => ({}) },
    },
    emits: ['open', 'toggle-select', 'drag-start', 'drag-end', 'drag-over', 'drag-leave', 'drop-on', 'load-more'],
    computed: {
        emptyTitle() {
            return i18n.t('CfilesModule.base', 'This folder is empty.');
        },
        emptyHint() {
            return this.canWrite
                ? i18n.t('CfilesModule.base', 'Drop files here or use the buttons above.')
                : i18n.t('CfilesModule.base', 'Unfortunately you have no permission to upload/edit files.');
        },
        moreLabel() {
            return i18n.t('base', 'Show more');
        },
        loadingLabel() {
            return i18n.t('base', 'Loading...');
        },
    },
    methods: {
        keyOf,
        isSelected(item) {
            return this.selection.indexOf(keyOf(item)) !== -1;
        },
    },
};
</script>
