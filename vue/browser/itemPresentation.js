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

/**
 * The platform's mime classes (`mime-pdf`) mapped onto the FontAwesome icons this browser
 * draws. `FileSerializer` ships the class; the icon set is a client concern.
 */
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

const WEEK_IN_SECONDS = 7 * 24 * 60 * 60;

/** Largest first, so the first match is the coarsest unit that still fits. */
const RELATIVE_UNITS = [
    ['day', 24 * 60 * 60],
    ['hour', 60 * 60],
    ['minute', 60],
    ['second', 1],
];

export const mimeIconClass = (item) => MIME_ICONS[item.mimeIcon] || 'fa-file-o';

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

/**
 * The dot-separated line under an item's name: how much it holds or how big it is, when it
 * last changed, and its description where there is room for one.
 */
export const itemMeta = (item, { description = true } = {}) => {
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
