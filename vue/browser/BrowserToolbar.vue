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

        <div class="btn-group btn-group-sm cfiles-view-switch" role="group" :aria-label="viewLabel">
            <button
                v-for="option in viewOptions"
                :key="option.value"
                type="button"
                class="btn btn-light"
                :class="{ active: view === option.value }"
                :aria-pressed="view === option.value ? 'true' : 'false'"
                :title="option.label"
                :aria-label="option.label"
                @click="$emit('view', option.value)"
            ><i :class="'fa fa-' + option.icon" aria-hidden="true"></i></button>
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
            <div class="btn-group btn-group-sm cfiles-add-files">
                <button type="button" class="btn btn-accent" @click="$emit('pick-files')">
                    <i class="fa fa-upload" aria-hidden="true"></i>
                    <span class="d-none d-sm-inline ms-1">{{ addFilesLabel }}</span>
                </button>
                <template v-if="createHandlersHtml">
                    <button
                        type="button"
                        class="btn btn-accent dropdown-toggle dropdown-toggle-split"
                        data-bs-toggle="dropdown"
                        aria-haspopup="true"
                        aria-expanded="false"
                    ><span class="visually-hidden">{{ handlersLabel }}</span></button>
                    <ul class="dropdown-menu dropdown-menu-end" v-additions v-html="createHandlersHtml"></ul>
                </template>
            </div>
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
        /** Which display is in force, one of `FolderListingService::VIEWS`. */
        view: { type: String, default: 'list' },
        /**
         * Server-rendered `<li>` entries for the file handlers a module contributed — "new
         * spreadsheet", "import from …". They stay server-rendered because they are menu
         * entries carrying legacy `data-action-click` attributes and build their own URLs from
         * the request; the same arrangement the core's `UploadField` uses.
         */
        createHandlersHtml: { type: String, default: '' },
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
        'open', 'sort', 'view', 'create-folder', 'pick-files',
        'crumb-drag-over', 'crumb-drag-leave', 'crumb-drop',
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
        addFolderLabel() {
            return i18n.t('CfilesModule.base', 'Add folder');
        },
        addFilesLabel() {
            return i18n.t('CfilesModule.base', 'Add files');
        },
        handlersLabel() {
            return i18n.t('CfilesModule.base', 'More ways to add a file');
        },
        rootLabel() {
            return i18n.t('CfilesModule.base', 'Files');
        },
        viewLabel() {
            return i18n.t('CfilesModule.base', 'View');
        },
        viewOptions() {
            return [
                { value: 'list', icon: 'list', label: i18n.t('CfilesModule.base', 'List') },
                { value: 'tiles', icon: 'th', label: i18n.t('CfilesModule.base', 'Tiles') },
            ];
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
