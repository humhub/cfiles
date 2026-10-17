<template>
    <a
        v-if="!location.space"
        class="cfiles-location"
        :href="folderUrl(location.folder.id)"
        :title="location.label"
        :aria-label="openLabel(location.folder.title)"
        draggable="false"
        @click="follow(location.folder, $event)"
    >{{ location.label }}</a>
    <span v-else class="cfiles-location cfiles-location--split" :title="location.label">{{ location.before }}<a
        class="cfiles-location__space"
        :href="folderUrl(null, location.space)"
        :aria-label="openLabel(location.space.title)"
        draggable="false"
        @click="follow(location.space, $event)"
    >{{ location.space.title }}</a><template v-if="location.folder"> › <a
        class="cfiles-location__folder"
        :href="folderUrl(location.folder.id, location.space)"
        :aria-label="openLabel(location.folder.title)"
        draggable="false"
        @click="follow(location.folder, $event)"
    >{{ location.folderLabel }}</a></template>{{ location.after }}</span>
</template>

<script>
/**
 * Where a hit of a result list lies ({@see itemLocation}), shared by the row and the tile: one
 * link to the folder it is in ("in Brand › Logos") — or, over several spaces (the global files
 * page), two: the space, which opens that space's top level, and its folders, which open the
 * folder in that space ("in Marketing › Brand › Logos").
 *
 * A plain click emits `open` with what it names — the folder (`{type: 'folder', id, title,
 * space?}`) or the space path entry (`{type: 'space', …}`) —; any other click follows the link.
 * `folderUrl(folderId, space)` gives the links; `space` is left out for a folder of the open
 * container.
 */
import { i18n } from '@humhub/vue';
import { isPlainClick } from './itemPresentation';

export default {
    props: {
        /** What `itemLocation()` answers for the item — never null here. */
        location: { type: Object, required: true },
        folderUrl: { type: Function, required: true },
    },
    emits: ['open'],
    methods: {
        /** Each link's name says what it opens; the line itself (its title) where it lies. */
        openLabel(name) {
            return i18n.t('CfilesModule.base', 'Open {name}', { name });
        },
        follow(target, event) {
            if (!isPlainClick(event)) {
                return;
            }
            event.preventDefault();
            this.$emit('open', target);
        },
    },
};
</script>
