<template>
    <div class="cfiles-toolbar d-flex flex-wrap align-items-center gap-2">
        <nav class="cfiles-breadcrumb flex-grow-1 min-width-0" :aria-label="breadcrumbLabel">
            <ol class="breadcrumb mb-0">
                <li
                    v-for="(crumb, index) in crumbs"
                    :key="crumb.id ?? 'top'"
                    class="breadcrumb-item"
                    :class="{ active: index === crumbs.length - 1, 'cfiles-crumb-drop': dropTargetId === crumb.id }"
                    @dragover="onCrumbDragOver($event, crumb, index)"
                    @dragleave="$emit('crumb-drag-leave')"
                    @drop="onCrumbDrop($event, crumb, index)"
                >
                    <span v-if="index === crumbs.length - 1">{{ crumbTitle(crumb) }}</span>
                    <a v-else :href="folderUrl(crumb.id)" @click="onCrumbClick($event, crumb)">{{ crumbTitle(crumb) }}</a>
                </li>
            </ol>
        </nav>

        <div v-if="selectionCount" class="cfiles-selection d-flex align-items-center gap-2">
            <span class="text-muted small">{{ selectionLabel }}</span>
            <button type="button" class="btn btn-light btn-sm" @click="$emit('move-selection')">
                <i class="fa fa-arrows" aria-hidden="true"></i> {{ moveLabel }}
            </button>
            <button type="button" class="btn btn-danger btn-sm" @click="$emit('delete-selection')">
                <i class="fa fa-trash" aria-hidden="true"></i> {{ deleteLabel }}
            </button>
        </div>

        <DropdownMenu
            menu-id="cfiles.sort"
            :entries="sortEntries"
            :toggle-aria-label="sortLabel"
            toggle-class="btn btn-light btn-sm dropdown-toggle"
            root-class="cfiles-sort-menu"
            :align-end="true"
        >
            <template #toggle>{{ activeSortLabel }}</template>
        </DropdownMenu>

        <template v-if="canWrite">
            <button type="button" class="btn btn-light btn-sm" @click="$emit('create-folder')">
                <i class="fa fa-folder" aria-hidden="true"></i>
                <span class="d-none d-sm-inline ms-1">{{ addFolderLabel }}</span>
            </button>
            <button type="button" class="btn btn-accent btn-sm" @click="$emit('pick-files')">
                <i class="fa fa-upload" aria-hidden="true"></i>
                <span class="d-none d-sm-inline ms-1">{{ addFilesLabel }}</span>
            </button>
        </template>
    </div>
</template>

<script>
/**
 * Breadcrumb, sort control, create/upload buttons and — while rows are selected — the bulk
 * actions.
 *
 * The breadcrumb doubles as a drop target: dragging rows onto an ancestor moves them there,
 * which is how you move something UP a tree without a folder pane. Phase 2's directory pane
 * takes over that job; until then this is the only way up.
 */
import { i18n } from '@humhub/vue';

const SORTS = ['name', 'size', 'updatedAt'];

export default {
    props: {
        path: { type: Array, default: () => [] },
        sort: { type: String, default: 'name' },
        order: { type: String, default: 'asc' },
        canWrite: { type: Boolean, default: false },
        selectionCount: { type: Number, default: 0 },
        /**
         * The crumb currently being dragged over, or `undefined` when nothing is.
         *
         * NOT `null` for "nothing": the top-level crumb's own id IS null, so a null default
         * would make `dropTargetId === crumb.id` permanently true and paint it as an active
         * drop target at all times.
         */
        dropTargetId: { type: Number, default: undefined },
        folderUrl: { type: Function, required: true },
    },
    emits: [
        'open', 'sort', 'create-folder', 'pick-files',
        'move-selection', 'delete-selection', 'crumb-drag-over', 'crumb-drag-leave', 'crumb-drop',
    ],
    computed: {
        /**
         * The path the API sends, preceded by the container's top level. That level has no
         * folder record, so it is not something the server could have sent — it is a client
         * entry with a null id, which is exactly what every endpoint takes for "no parent".
         */
        crumbs() {
            return [{ id: null, title: null }].concat(this.path);
        },
        breadcrumbLabel() {
            return i18n.t('CfilesModule.base', 'Folder path');
        },
        sortLabel() {
            return i18n.t('CfilesModule.base', 'Sort by');
        },
        moveLabel() {
            return i18n.t('CfilesModule.base', 'Move');
        },
        deleteLabel() {
            return i18n.t('CfilesModule.base', 'Delete');
        },
        addFolderLabel() {
            return i18n.t('CfilesModule.base', 'Add folder');
        },
        addFilesLabel() {
            return i18n.t('CfilesModule.base', 'Add files');
        },
        rootLabel() {
            return i18n.t('CfilesModule.base', 'Files');
        },
        selectionLabel() {
            return i18n.t('CfilesModule.base', '{count, plural, one{# selected} other{# selected}}', {
                count: this.selectionCount,
            });
        },
        sortLabels() {
            return {
                name: i18n.t('CfilesModule.base', 'Name'),
                size: i18n.t('CfilesModule.base', 'Size'),
                updatedAt: i18n.t('CfilesModule.base', 'Updated'),
            };
        },
        /** What the trigger reads: the column in force and which way it points. */
        activeSortLabel() {
            return (this.sortLabels[this.sort] ?? this.sortLabels.name)
                + (this.order === 'asc' ? ' ↑' : ' ↓');
        },
        sortEntries() {
            return SORTS.map((key, index) => ({
                id: 'sort-' + key,
                sortOrder: (index + 1) * 10,
                // The active column is marked, so picking it again reads as "reverse it".
                label: this.sortLabels[key] + (this.sort === key ? (this.order === 'asc' ? ' ↑' : ' ↓') : ''),
                onClick: () => this.$emit('sort', key),
            }));
        },
    },
    methods: {
        crumbTitle(crumb) {
            return crumb.id === null ? this.rootLabel : crumb.title;
        },
        onCrumbClick(event, crumb) {
            if (event.metaKey || event.ctrlKey || event.shiftKey || event.altKey || event.button !== 0) {
                return;
            }
            event.preventDefault();
            this.$emit('open', crumb.id);
        },
        onCrumbDragOver(event, crumb, index) {
            // Dropping onto the folder you are already in would be a no-op.
            if (index === this.crumbs.length - 1) {
                return;
            }
            event.preventDefault();
            event.dataTransfer.dropEffect = 'move';
            this.$emit('crumb-drag-over', crumb.id);
        },
        onCrumbDrop(event, crumb, index) {
            if (index === this.crumbs.length - 1) {
                return;
            }
            event.preventDefault();
            event.stopPropagation();
            this.$emit('crumb-drop', crumb.id);
        },
    },
};
</script>
