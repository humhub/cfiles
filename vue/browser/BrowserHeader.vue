<template>
    <PageHeader :title="titleLabel">
        <template #actions>
            <!-- The core's dialog of the device connection (`DeviceConnection`): the address
                 and a password for the device. -->
            <a
                v-if="connectUrl"
                href="#"
                class="btn btn-light btn-icon-only"
                data-action-click="ui.modal.load"
                :data-action-url="connectUrl"
                :aria-label="connectLabel"
                :title="connectLabel"
            ><i class="ti ti-plug-connected" aria-hidden="true"></i></a>
            <a
                v-if="settingsUrl"
                :href="settingsUrl"
                class="btn btn-light btn-icon-only"
                :aria-label="settingsLabel"
                :title="settingsLabel"
            ><i class="ti ti-settings" aria-hidden="true"></i></a>
            <AddMenu
                v-if="writable"
                :handlers="handlers"
                @upload="(accept) => $emit('upload', accept)"
                @create-folder="$emit('create-folder')"
                @handler="$emit('handler')"
            />
        </template>
        <FilterBar
            v-if="filters.length"
            :filters="filters"
            :model-value="filterValues"
            id-prefix="cfiles-filter"
            @update:model-value="(values) => $emit('filters', values)"
        />
    </PageHeader>
</template>

<script>
/**
 * The file browser's page header: the title, the links to the network drive dialog and to the
 * settings, the `AddMenu` — and below them the `FilterBar` with the list's filters and its sort.
 *
 * Holds nothing of its own: the island owns what the bar shows (`filterValues`) and what the
 * menu does, and gets everything back as events (`upload` with the type a handler asks for,
 * `create-folder`, `handler`, `filters` with the bar's values).
 */
import { i18n } from '@humhub/vue';
import AddMenu from './AddMenu.vue';

export default {
    components: { AddMenu },
    props: {
        /** The network drive dialog (`DeviceConnection`), or null while it is not offered. */
        connectUrl: { type: String, default: null },
        /** The container's cfiles settings page, or null for those who may not change them. */
        settingsUrl: { type: String, default: null },
        /** Whether the user may add to the level shown: no `AddMenu` otherwise. */
        writable: { type: Boolean, default: false },
        /** The file handlers of the `AddMenu` (`{label, icon, attributes}`). */
        handlers: { type: Array, default: () => [] },
        /** The FilterBar definitions of the level shown; no bar without any. */
        filters: { type: Array, default: () => [] },
        /** The bar's values: the filters and the sort. */
        filterValues: { type: Object, default: () => ({}) },
    },
    emits: ['upload', 'create-folder', 'handler', 'filters'],
    computed: {
        titleLabel() {
            return i18n.t('CfilesModule.base', 'Files');
        },
        settingsLabel() {
            return i18n.t('CfilesModule.base', 'Settings');
        },
        connectLabel() {
            return i18n.t('CfilesModule.base', 'Connect as network drive');
        },
    },
};
</script>
