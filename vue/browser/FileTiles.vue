<template>
    <TileGrid
        :items="items"
        item-key="key"
        :selectable="selectable"
        :selection="selection"
        :draggable="draggable"
        :can-drop="canDrop"
        :drop-target-key="dropTargetKey"
        :loading="loading"
        :has-more="hasMore"
        :loading-more="loadingMore"
        :level="level"
        :direction="direction"
        :label-for="labelFor"
        @toggle-select="(item, flags) => $emit('toggle-select', item, flags)"
        @context-menu="onContextMenu"
        @drag-start="onDragStart"
        @drag-end="(item, event) => $emit('drag-end', item, event)"
        @drag-over="(item, event) => $emit('drag-over', item, event)"
        @drag-leave="(item, event) => $emit('drag-leave', item, event)"
        @drop-on="(item, event) => $emit('drop-on', item, event)"
        @load-more="$emit('load-more')"
    >
        <template #thumb="{ item }">
            <span v-if="item.type === 'space'" class="cfiles-tile__space">
                <i class="ti ti-folder-filled cfiles-tile__folder"></i>
                <SpaceImage class="cfiles-tile__badge" v-bind="spaceImage(item)" :width="20" aria-hidden="true" />
            </span>
            <i v-else-if="item.type === 'folder'" class="ti ti-folder-filled cfiles-tile__folder"></i>
            <img v-else-if="item.previewUrl" :src="item.previewUrl" alt="" draggable="false" class="cfiles-tile__image" />
            <span v-else class="cfiles-tile__doc"><i :class="'ti ' + fileIcon(item)"></i></span>
        </template>
        <template #name="{ item }">
            <span v-if="item.type === 'upload'">{{ item.title }}</span>
            <a
                v-else
                :href="linkUrl(item)"
                v-bind="linkAttributes(item)"
                draggable="false"
                :title="labelFor(item)"
                @click="onOpen(item, $event)"
            >{{ labelFor(item) }}<i
                v-if="item.visibility === 0"
                class="ti ti-lock ms-1 text-muted"
                role="img"
                :aria-label="privateLabel"
            ></i></a>
        </template>
        <template #meta="{ item }">{{ item.type === 'upload' ? (item.progress || 0) + '%' : tileMeta(item) }}<ItemLocation
            v-if="item.type !== 'upload' && itemLocation(item)"
            class="cfiles-tile__location"
            :location="itemLocation(item)"
            :folder-url="folderUrl"
            @open="(target) => $emit('open', target)"
        /></template>
        <template #actions="{ item }">
            <ContentControls
                v-if="item.type !== 'upload' && item.type !== 'space'"
                :ref="(el) => setControls(item, el)"
                :content-id="item.contentId"
                :view-context="CONTROLS_VIEW_CONTEXT"
                :entries="entriesFor(item)"
                :suppress="SUPPRESSED_CORE_ENTRIES"
                :context="{ item }"
                root-class="nav"
                toggle-class="btn c-icon-button c-icon-button--ghost cfiles-tile__toggle"
                :toggle-aria-label="actionsLabel"
            >
                <!-- A toggle class of its own drops the platform's `dropdown-toggle` glyph. -->
                <template #toggle><i class="ti ti-dots-vertical" aria-hidden="true"></i></template>
            </ContentControls>
        </template>
        <template #empty><slot name="empty"></slot></template>
    </TileGrid>
</template>

<script>
/**
 * The open level as tiles — the file browser's content for the core `TileGrid`: a folder as
 * the filled folder glyph with its item count, a file as a document card with its type icon
 * (or its preview image) and its size, an upload in progress as a card with its percentage.
 * A hit of a result list names the folder it lies in on a second meta line, a link that opens
 * that folder (`open` with the folder, as for a folder tile; see `ItemLocation`). A space of the
 * global files page's top level (`type: 'space'`) is a folder glyph with the space's image as
 * its badge and how much it holds — opened like a folder, with nothing to select or act on.
 *
 * Speaks the same event vocabulary as `ItemList`, so the browser treats both views alike.
 * Items carry a `key` (`type:id`, see `api.keyOf`) the grid is keyed by — a folder and a file
 * may share a numeric id.
 */
import { i18n } from '@humhub/vue';
import ItemLocation from './ItemLocation.vue';
import {
    CONTROLS_VIEW_CONTEXT, SUPPRESSED_CORE_ENTRIES, fileIcon, isPlainClick, itemLocation, spaceImage, tileMeta,
} from './itemPresentation';

export default {
    components: { ItemLocation },
    props: {
        items: { type: Array, default: () => [] },
        selection: { type: Array, default: () => [] },
        selectable: { type: Boolean, default: false },
        draggable: { type: Boolean, default: false },
        canDrop: { type: Function, default: () => false },
        // `null` is no target (TileGrid's convention, unlike PathBar's `dropTargetId`).
        dropTargetKey: { type: [String, Number], default: null },
        hasMore: { type: Boolean, default: false },
        loading: { type: Boolean, default: false },
        loadingMore: { type: Boolean, default: false },
        level: { type: [String, Number], default: 0 },
        direction: { type: String, default: 'forward' },
        entriesFor: { type: Function, required: true },
        folderUrl: { type: Function, required: true },
    },
    emits: ['open', 'toggle-select', 'drag-start', 'drag-end', 'drag-over', 'drag-leave', 'drop-on', 'load-more'],
    data() {
        return { CONTROLS_VIEW_CONTEXT, SUPPRESSED_CORE_ENTRIES };
    },
    computed: {
        privateLabel() {
            return i18n.t('CfilesModule.base', 'Private');
        },
        actionsLabel() {
            return i18n.t('base', 'Actions');
        },
    },
    created() {
        // Each tile's ContentControls by item key — a plain map, not reactive: the function refs
        // that fill it run on every render, and writing reactive state there would re-render.
        this.controls = {};
    },
    methods: {
        fileIcon,
        itemLocation,
        tileMeta,
        spaceImage,
        labelFor(item) {
            // A space tile names its space the core's way, `name` (see GlobalListingService).
            return (item.type === 'space' ? item.name : item.title) ?? '';
        },
        linkUrl(item) {
            if (item.type === 'space') {
                return this.folderUrl(null, item);
            }
            // A folder hit of the global page lies in a space of its own (its path says which).
            // A file links wherever the server said (a viewer, an editor — see FileSerializer::link()).
            return item.type === 'folder'
                ? this.folderUrl(item.id, itemLocation(item)?.space ?? undefined)
                : (item.link?.url || item.url || '#');
        },
        linkAttributes(item) {
            return item.type === 'folder' || item.type === 'space' ? {} : (item.link?.attributes || {});
        },
        setControls(item, el) {
            if (el) {
                this.controls[item.key] = el;
            } else {
                delete this.controls[item.key];
            }
        },
        onOpen(item, event) {
            if ((item.type !== 'folder' && item.type !== 'space') || !isPlainClick(event)) {
                return;
            }
            event.preventDefault();
            this.$emit('open', item);
        },
        onContextMenu(item, event) {
            // The open menu lives inside the tile: a right-click on it is not a new request.
            if (event.target.closest?.('.dropdown-menu')) {
                return;
            }
            // A keyboard-raised menu (Menu key, Shift+F10) has no pointer position worth using:
            // open it under the tile's own toggle instead.
            this.controls[item.key]?.open(event.button === 2 ? event : null);
        },
        onDragStart(item, event) {
            if (event.dataTransfer) {
                event.dataTransfer.effectAllowed = 'move';
                // Firefox ignores a drag that sets no data at all.
                event.dataTransfer.setData('text/plain', item.key);
            }
            this.$emit('drag-start', item, event);
        },
    },
};
</script>
