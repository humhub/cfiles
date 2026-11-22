<template>
    <template v-if="results">
        <p class="mb-2"><strong>{{ noResultsTitle }}</strong></p>
        <button type="button" class="btn btn-light btn-sm" @click="$emit('reset')">{{ resetLabel }}</button>
    </template>
    <template v-else>
        <p class="mb-0"><strong>{{ emptyTitle }}</strong></p>
        <p class="mb-0">{{ emptyHint }}</p>
    </template>
</template>

<script>
/**
 * What the file browser shows for a level without items, in the views' `empty` slot: an empty
 * folder says so (and how to fill it, to whoever may); an empty result list says where nothing
 * was found and offers to reset the filters (`reset`) — the only way out of it besides the bar.
 *
 * The global page's top level holds spaces, not files: there it is the spaces the user is not
 * a member of yet, or the results not found in them.
 */
import { i18n } from '@humhub/vue';

export default {
    props: {
        /** Whether the level is a result list (the filters' hits), not a folder's items. */
        results: { type: Boolean, default: false },
        /** The global page's top level: a tile per space. */
        globalTop: { type: Boolean, default: false },
        /** Whether a folder is open — not the top level of the container. */
        inFolder: { type: Boolean, default: false },
        /** Whether the user may add to the level. */
        writable: { type: Boolean, default: false },
    },
    emits: ['reset'],
    computed: {
        emptyTitle() {
            return this.globalTop
                ? i18n.t('CfilesModule.base', 'You are not a member of a space with files yet.')
                : i18n.t('CfilesModule.base', 'This folder is empty.');
        },
        emptyHint() {
            if (this.globalTop) {
                return '';
            }
            return this.writable
                ? i18n.t('CfilesModule.base', 'Drop files here or use the buttons above.')
                : i18n.t('CfilesModule.base', 'Unfortunately you have no permission to upload/edit files.');
        },
        noResultsTitle() {
            if (this.globalTop) {
                return i18n.t('CfilesModule.base', 'No results in your spaces.');
            }
            return this.inFolder
                ? i18n.t('CfilesModule.base', 'No results in this folder and its subfolders.')
                : i18n.t('CfilesModule.base', 'No results in all files.');
        },
        resetLabel() {
            return i18n.t('CfilesModule.base', 'Reset filters');
        },
    },
};
</script>
