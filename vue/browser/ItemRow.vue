<template>
    <div
        class="cfiles-row d-flex align-items-center gap-2"
        :class="{ 'cfiles-row-drop': dropTarget, selected: selected }"
        :draggable="draggable"
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
            <h4 class="mb-0 text-truncate">
                <a :href="linkUrl" @click="onOpen">{{ displayTitle }}</a>
                <i
                    v-if="isPrivate"
                    class="fa fa-lock text-muted ms-1"
                    :title="privateLabel"
                    :aria-label="privateLabel"
                ></i>
            </h4>
            <h5 class="mb-0 text-truncate cfiles-row-meta">{{ meta }}</h5>
        </div>

        <div class="cfiles-row-creator">
            <UserImage v-if="item.creator" v-bind="item.creator" :size="21" />
        </div>

        <div class="cfiles-row-controls">
            <ContentControls
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
import { getConfig, i18n } from '@humhub/vue';

/**
 * Core control entries the browser renders itself, so the server must not send them too —
 * exactly the set the server-rendered `FileListContextMenu` used to switch off. Without it
 * the row menu shows the whole stream-entry stack (pin, archive, permalink, …) next to the
 * file actions.
 */
const SUPPRESSED_CORE_ENTRIES = ['edit', 'delete', 'permalink', 'pin', 'move', 'archive'];

const WEEK_IN_SECONDS = 7 * 24 * 60 * 60;

/** Largest first, so the first match is the coarsest unit that still fits. */
const RELATIVE_UNITS = [
    ['day', 24 * 60 * 60],
    ['hour', 60 * 60],
    ['minute', 60],
    ['second', 1],
];

const MIME_ICONS = {
    'mime-image': 'fa-file-image-o',
    'mime-pdf': 'fa-file-pdf-o',
    'mime-archive': 'fa-file-archive-o',
    'mime-audio': 'fa-file-audio-o',
    'mime-video': 'fa-file-video-o',
    'mime-text': 'fa-file-text-o',
    'mime-code': 'fa-file-code-o',
    'mime-excel': 'fa-file-excel-o',
    'mime-word': 'fa-file-word-o',
    'mime-powerpoint': 'fa-file-powerpoint-o',
};

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
        displayTitle() {
            return this.item.title;
        },
        linkUrl() {
            // A folder link is a real page URL even though opening it never navigates — that
            // is what keeps middle-click, "open in new tab" and copy-link working.
            return this.isFolder ? this.folderUrl(this.item.id) : (this.item.url || '#');
        },
        iconClass() {
            if (this.isFolder) {
                return 'fa fa-folder cfiles-icon-folder';
            }
            return 'fa ' + (MIME_ICONS[this.item.mimeIcon] || 'fa-file-o') + ' cfiles-icon-file';
        },
        meta() {
            const parts = [];

            if (this.isFolder) {
                if (typeof this.item.itemCount === 'number') {
                    parts.push(i18n.t('CfilesModule.base', '{count, plural, =0{empty} one{# item} other{# items}}', {
                        count: this.item.itemCount,
                    }));
                }
            } else {
                parts.push(this.formattedSize);
            }

            parts.push(this.relativeTime);

            if (this.item.description) {
                parts.push(this.item.description);
            }

            return parts.filter(Boolean).join(' · ');
        },
        formattedSize() {
            const size = this.item.size || 0;
            const units = ['B', 'KB', 'MB', 'GB', 'TB'];
            let value = size;
            let unit = 0;
            while (value >= 1024 && unit < units.length - 1) {
                value /= 1024;
                unit++;
            }
            return (unit === 0 ? value : value.toFixed(1)) + ' ' + units[unit];
        },
        /**
         * Recent changes read as "3 days ago", older ones as a date — the same split the
         * platform's own `TimeAgo` widget makes, and what the server-rendered list showed
         * before. Formatted in the HumHub language rather than the browser's, which is what
         * `toLocaleDateString()` with no locale would have used.
         */
        relativeTime() {
            const stamp = this.item.updatedAt || this.item.createdAt;

            if (!stamp) {
                return '';
            }

            const date = new Date(stamp);
            const locale = getConfig('i18n').language || undefined;
            const seconds = Math.round((Date.now() - date.getTime()) / 1000);

            if (seconds >= 0 && seconds < WEEK_IN_SECONDS) {
                const [unit, size] = RELATIVE_UNITS.find(([, unitSize]) => seconds >= unitSize)
                    ?? ['second', 1];

                return new Intl.RelativeTimeFormat(locale, { numeric: 'auto' })
                    .format(-Math.floor(seconds / size), unit);
            }

            return new Intl.DateTimeFormat(locale, { dateStyle: 'medium' }).format(date);
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
