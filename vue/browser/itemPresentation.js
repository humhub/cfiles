/**
 * How an item reads, shared by the row and the tile.
 *
 * Both render the same item in different shapes, and everything that turns API data into
 * something human — the icon, the size, the timestamp, the meta line — is the same job in
 * both. Kept out of the components so the two cannot drift.
 */
import { getConfig, i18n } from '@humhub/vue';

/**
 * Core control entries the browser renders itself, so the server must not send them too —
 * exactly the set the server-rendered `FileListContextMenu` used to switch off. Without it
 * the item menu shows the whole stream-entry stack (pin, archive, permalink, …) next to the
 * file actions.
 */
export const SUPPRESSED_CORE_ENTRIES = ['edit', 'delete', 'permalink', 'pin', 'move', 'archive'];

/**
 * The render-options profile the server resolves this menu under. It has to be one of core's
 * `StreamEntryOptions::VIEW_CONTEXT_*` values — `browser`, which this used to pass, is not
 * one and only worked by accident: any unknown string is "neither `default` nor `detail`",
 * which is what kept the stream-only Pin entry away.
 *
 * `detail` is the honest one here: a row shows a single content record on its own, outside a
 * stream. The remaining values name core's own stream surfaces (`default`, `dashboard`,
 * `search`) or a modal, none of which this is.
 */
export const CONTROLS_VIEW_CONTEXT = 'detail';

const WEEK_IN_SECONDS = 7 * 24 * 60 * 60;

/** Largest first, so the first match is the coarsest unit that still fits. */
const RELATIVE_UNITS = [
    ['day', 24 * 60 * 60],
    ['hour', 60 * 60],
    ['minute', 60],
    ['second', 1],
];

/** The Tabler glyph of an item's file type, as the serializer names it (`file` if none). */
export const fileIcon = (item) => 'ti-' + (item.icon || 'file');

export const formatSize = (size) => {
    const units = ['B', 'KB', 'MB', 'GB', 'TB'];
    let value = size || 0;
    let unit = 0;

    while (value >= 1024 && unit < units.length - 1) {
        value /= 1024;
        unit++;
    }

    return (unit === 0 ? value : value.toFixed(1)) + ' ' + units[unit];
};

/**
 * Recent changes read as "3 days ago", older ones as a date — the same split the platform's
 * own `TimeAgo` widget makes, and what the server-rendered list showed before. Formatted in
 * the HumHub language rather than the browser's, which is what `toLocaleDateString()` with no
 * locale would have used.
 */
export const formatTimestamp = (stamp) => {
    if (!stamp) {
        return '';
    }

    const date = new Date(stamp);
    const locale = getConfig('i18n').language || undefined;
    const seconds = Math.round((Date.now() - date.getTime()) / 1000);

    if (seconds >= 0 && seconds < WEEK_IN_SECONDS) {
        const [unit, size] = RELATIVE_UNITS.find(([, unitSize]) => seconds >= unitSize) ?? ['second', 1];

        return new Intl.RelativeTimeFormat(locale, { numeric: 'auto' })
            .format(-Math.floor(seconds / size), unit);
    }

    return new Intl.DateTimeFormat(locale, { dateStyle: 'medium' }).format(date);
};

/** How much a space holds at its top level (a tile of the global files page), as a folder reads. */
const spaceCount = (item) => i18n.t('CfilesModule.base', '{count, plural, =0{empty} one{# item} other{# items}}', {
    count: item.itemCount || 0,
});

/**
 * The dot-separated line under an item's name: how much it holds or how big it is, when it
 * last changed, and its description where there is room for one.
 */
export const itemMeta = (item, { description = true } = {}) => {
    if (item.type === 'space') {
        return spaceCount(item);
    }

    const parts = [];

    if (item.type === 'folder') {
        if (typeof item.itemCount === 'number') {
            parts.push(i18n.t('CfilesModule.base', '{count, plural, =0{empty} one{# item} other{# items}}', {
                count: item.itemCount,
            }));
        }
    } else {
        parts.push(formatSize(item.size));
    }

    parts.push(formatTimestamp(item.updatedAt || item.createdAt));

    if (description && item.description) {
        parts.push(item.description);
    }

    return parts.filter(Boolean).join(' · ');
};

/**
 * The line under a tile's name: how much a folder holds, how big a file is.
 *
 * `itemCount` is only known inside a listing (see `FolderListingService`) — a folder payload
 * from a create/update response has it `null`, and there is nothing honest to show for "how
 * many items" then, so this reads empty rather than claiming zero.
 */
export const tileMeta = (item) => (item.type === 'space' ? spaceCount(item) : item.type === 'folder'
    ? (typeof item.itemCount === 'number'
        ? i18n.t('CfilesModule.base', '{count, plural, =0{empty} one{# item} other{# items}}', {
            count: item.itemCount,
        })
        : '')
    : formatSize(item.size));

/**
 * Where a hit of a result list lies, relative to the open folder (the payload's `path`,
 * see `FolderListingService`), and the line naming it ("in Brand › Logos"). `null` for an item
 * directly in the open folder — and for every item of a level, which are all there.
 *
 * A path over several spaces (the global files page, `FolderListBuilder::$prefixContainer`)
 * starts with the item's space (`{type: 'space', id, contentContainerId, guid, title}`); the
 * rest are its folders. So the location has two parts, each opened on its own:
 *
 * - `space`: that path entry, `null` for a path of folders only;
 * - `folder`: the folder the item is in, as the item the views emit `open` with — with the
 *   `space` it lies in, when the path names one —, `null` for an item at a space's top level;
 * - `label`: the whole line, `folderLabel` the folders' part of it;
 * - `before`/`after`: what the translated sentence puts around the path, for a line that
 *   links its parts separately.
 */
export const itemLocation = (item) => {
    const path = item.path || [];

    if (!path.length) {
        return null;
    }

    const space = path[0].type === 'space' ? path[0] : null;
    const folders = space ? path.slice(1) : path;
    const parent = folders.length ? folders[folders.length - 1] : null;
    const folderLabel = folders.map((level) => level.title).join(' › ');
    const whole = [space?.title, folderLabel].filter(Boolean).join(' › ');
    // A marker no title contains, where the translation puts the path.
    const [before, after = ''] = i18n.t('CfilesModule.base', 'in {path}', { path: '\u0000' }).split('\u0000');

    return {
        space,
        folder: parent ? { type: 'folder', id: parent.id, title: parent.title, ...(space ? { space } : {}) } : null,
        label: before + whole + after,
        folderLabel,
        before,
        after,
    };
};

/**
 * What `SpaceImage` takes of a space tile — the tile carries more (`browseUrl`, `itemCount`),
 * which would otherwise fall through to the DOM as attributes.
 */
export const spaceImage = (space) => ({
    id: space.id,
    name: space.name,
    color: space.color ?? null,
    imageUrl: space.imageUrl ?? null,
    contentContainerId: space.contentContainerId ?? null,
});

/** A click that means "open it here", not "somewhere else" (a new tab, a download …). */
export const isPlainClick = (event) => !(event.metaKey || event.ctrlKey || event.shiftKey || event.altKey || event.button > 0);
