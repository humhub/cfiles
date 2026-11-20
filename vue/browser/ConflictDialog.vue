<template>
    <UiModal :show="show" :title="title" @update:show="$emit('cancel')">
        <p>{{ intro }}</p>

        <ul class="cfiles-conflicts list-unstyled">
            <li v-for="conflict in conflicts" :key="conflict.name" class="py-1">
                <i class="ti icon-fw text-muted" :class="conflict.type === 'folder' ? 'ti-folder-filled' : 'ti-file'" aria-hidden="true"></i>
                {{ conflict.name }}
                <small v-if="!conflict.canReplace" class="cfiles-conflict-note text-muted">({{ noteOf(conflict) }})</small>
            </li>
        </ul>

        <p v-if="replaceable" class="text-muted small mb-0">{{ versionNote }}</p>

        <template #footer>
            <button type="button" class="btn btn-light" @click="$emit('resolve', 'skip')">{{ skipLabel }}</button>
            <button
                type="button"
                class="btn"
                :class="replaceable ? 'btn-light' : 'btn-primary'"
                @click="$emit('resolve', 'keep')"
            >{{ keepLabel }}</button>
            <button
                v-if="replaceable"
                type="button"
                class="btn btn-primary"
                @click="$emit('resolve', 'replace')"
            >{{ replaceLabel }}</button>
        </template>
    </UiModal>
</template>

<script>
/**
 * Asks what an upload does with the names that are taken in its level (`conflicts`, see
 * `findConflicts()`): `replace` them — a new version of each file the user may change, its
 * previous content kept as an older version —, `keep` both (an indexed name, `report(1).pdf`)
 * or `skip` them. Dismissing the dialog is a `cancel`: nothing is uploaded.
 *
 * A name taken by a folder, or by a file the user may not change, cannot be replaced: such an
 * item is kept both when the user replaces the others, and Replace is not offered at all when
 * nothing can be.
 */
import { i18n } from '@humhub/vue';

export default {
    props: {
        show: { type: Boolean, default: false },
        /** `{name, type, id, canReplace}` per name taken. */
        conflicts: { type: Array, default: () => [] },
    },
    emits: ['resolve', 'cancel'],
    computed: {
        replaceable() {
            return this.conflicts.some((conflict) => conflict.canReplace);
        },
        title() {
            return i18n.t('CfilesModule.base', 'Names already taken');
        },
        intro() {
            return i18n.t('CfilesModule.base', '{count, plural, one{An item with this name already exists here:} other{Items with these names already exist here:}}', {
                count: this.conflicts.length,
            });
        },
        versionNote() {
            return i18n.t('CfilesModule.base', 'A replaced file keeps its previous content as an older version.');
        },
        skipLabel() {
            return i18n.t('CfilesModule.base', 'Skip');
        },
        keepLabel() {
            return i18n.t('CfilesModule.base', 'Keep both');
        },
        replaceLabel() {
            return i18n.t('CfilesModule.base', 'Replace');
        },
    },
    methods: {
        noteOf(conflict) {
            return conflict.type === 'folder'
                ? i18n.t('CfilesModule.base', 'a folder')
                : i18n.t('CfilesModule.base', 'you may not replace it');
        },
    },
};
</script>
