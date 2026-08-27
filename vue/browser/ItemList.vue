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
                :drop-target="dropTargetKey === keyOf(item)"
                :entries="entriesFor(item)"
                :folder-url="folderUrl"
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
 * The rows of the open folder, in the platform's `.hh-list` container, plus the empty state
 * and the "show more" step. Paging appends in place — the same behaviour `UserList` and
 * `ActivityBox` have, so a long folder never replaces what the reader is looking at.
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
        dropTargetKey: { type: String, default: null },
        hasMore: { type: Boolean, default: false },
        loading: { type: Boolean, default: false },
        loadingMore: { type: Boolean, default: false },
        canWrite: { type: Boolean, default: false },
        entriesFor: { type: Function, required: true },
        folderUrl: { type: Function, required: true },
    },
    emits: ['open', 'toggle-select', 'load-more', 'drag-start', 'drag-end', 'drop-on'],
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
