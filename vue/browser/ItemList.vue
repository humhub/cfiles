<template>
    <div>
        <div v-if="selectable && items.length" class="cfiles-list-header d-flex align-items-center gap-2">
            <input
                ref="selectAll"
                type="checkbox"
                class="form-check-input"
                :checked="allSelected"
                :aria-label="selectAllLabel"
                @change="$emit('toggle-all')"
            />

            <template v-if="selection.length">
                <span class="text-muted small flex-grow-1">{{ selectionLabel }}</span>
                <button type="button" class="btn btn-light btn-sm" @click="$emit('move-selection')">
                    <i class="fa fa-arrows" aria-hidden="true"></i>
                    <span class="d-none d-sm-inline ms-1">{{ moveLabel }}</span>
                </button>
                <button type="button" class="btn btn-danger btn-sm" @click="$emit('delete-selection')">
                    <i class="fa fa-trash" aria-hidden="true"></i>
                    <span class="d-none d-sm-inline ms-1">{{ deleteLabel }}</span>
                </button>
            </template>
            <span v-else class="text-muted small">{{ selectAllLabel }}</span>
        </div>

        <div v-if="items.length" :class="containerClass">
            <component
                :is="itemComponent"
                v-for="item in items"
                :key="keyOf(item)"
                :item="item"
                :selected="isSelected(item)"
                :selectable="selectable"
                :draggable="draggable"
                :drop-target="dropTargetKey === keyOf(item)"
                :entries="entriesFor(item)"
                :folder-url="folderUrl"
                v-bind="socialProps"
                @open="$emit('open', $event)"
                @toggle-select="$emit('toggle-select', $event)"
                @drag-start="$emit('drag-start', $event)"
                @drag-end="$emit('drag-end')"
                @drop-on="$emit('drop-on', $event)"
            />
        </div>

        <div v-else-if="!loading" class="cfiles-empty text-center text-muted p-4">
            <p class="mb-0"><strong>{{ emptyTitle }}</strong></p>
            <p class="mb-0">{{ emptyHint }}</p>
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
 * The items of the open level, in whichever shape the reader chose, plus the empty state, the
 * "show more" step and the selection header.
 *
 * The two shapes are `ItemRow` and `ItemTile`, and this component does not know the difference
 * between them: both take the same props and emit the same events, so switching is one
 * `:is`. What changes with them is the container — `.hh-list` styles its own children, which
 * is right for rows and wrong for a grid.
 *
 * ## Select all
 *
 * Covers the items that are LOADED, not everything in the folder. With paging, a checkbox that
 * silently included rows the reader has never seen would make the delete button far more
 * dangerous than it looks. The box shows an indeterminate state while only some are selected,
 * so it never claims more than it did.
 */
import { i18n } from '@humhub/vue';
import ItemRow from './ItemRow.vue';
import ItemTile from './ItemTile.vue';
import { keyOf } from './api';

export default {
    components: { ItemRow, ItemTile },
    props: {
        items: { type: Array, default: () => [] },
        selection: { type: Array, default: () => [] },
        selectable: { type: Boolean, default: false },
        draggable: { type: Boolean, default: false },
        dropTargetKey: { type: String, default: null },
        hasMore: { type: Boolean, default: false },
        loading: { type: Boolean, default: false },
        loadingMore: { type: Boolean, default: false },
        canWrite: { type: Boolean, default: false },
        view: { type: String, default: 'list' },
        entriesFor: { type: Function, required: true },
        folderUrl: { type: Function, required: true },
        /** Handed straight to `ItemRow` — see `socialProps` below. */
        likeStates: { type: Object, default: () => ({}) },
    },
    emits: [
        'open', 'toggle-select', 'toggle-all', 'load-more',
        'drag-start', 'drag-end', 'drop-on', 'move-selection', 'delete-selection',
    ],
    computed: {
        itemComponent() {
            return this.view === 'tiles' ? 'ItemTile' : 'ItemRow';
        },
        /**
         * The like state map, bound only in the row list.
         *
         * A tile has no room for a like link and `ItemTile` declares no such prop, so binding
         * it there would put a stray attribute on every tile's root element rather than
         * nothing at all.
         */
        socialProps() {
            return this.view === 'tiles' ? {} : { likeStates: this.likeStates };
        },
        containerClass() {
            // `.hh-list` is the platform's row-list styling and applies to its direct
            // children; a grid brings its own.
            return this.view === 'tiles' ? 'cfiles-tiles' : 'hh-list cfiles-list';
        },
        allSelected() {
            return this.items.length > 0 && this.selection.length === this.items.length;
        },
        someSelected() {
            return this.selection.length > 0 && !this.allSelected;
        },
        selectAllLabel() {
            return i18n.t('CfilesModule.base', 'Select all');
        },
        selectionLabel() {
            return i18n.t('CfilesModule.base', '{count, plural, one{# selected} other{# selected}}', {
                count: this.selection.length,
            });
        },
        moveLabel() {
            return i18n.t('CfilesModule.base', 'Move');
        },
        deleteLabel() {
            return i18n.t('CfilesModule.base', 'Delete');
        },
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
    watch: {
        // `indeterminate` is a DOM property, not an attribute, so it cannot be bound in the
        // template.
        someSelected: {
            immediate: true,
            handler(partial) {
                this.$nextTick(() => {
                    if (this.$refs.selectAll) {
                        this.$refs.selectAll.indeterminate = partial;
                    }
                });
            },
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
