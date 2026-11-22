/**
 * The details panel of the file browser (`CfilesFileBrowser`, `DetailsPanel`): which item it
 * shows, and on which tab.
 *
 * The item is held by its key (`file:<id>`/`folder:<id>`), mirrored into the page URL as
 * `details` — a reload or a copied link opens the panel again — and looked up among the rows
 * loaded, so the panel follows a reload (an edit) and is gone with the item (a delete) or the
 * level (opening another one; the history entry of the level left keeps `details`, so going
 * Back brings the panel back with it — see `open()` and `onPopState()`).
 *
 * A mixin of the island, see its docblock. Reads the level shown: `items`, `likeStates`,
 * `container`, `path`.
 */
import { keyOf } from './api';
import { itemLocation } from './itemPresentation';

export default {
    data() {
        return {
            // The item the details panel shows (`file:<id>`/`folder:<id>`), mirrored into the
            // URL as `details` — see `detailsItem`.
            detailsKey: new URLSearchParams(window.location.search).get('details') || null,
            // The panel's tab, kept when another item's details are opened.
            detailsTab: 'info',
        };
    },
    computed: {
        /**
         * The item the details panel shows: looked up among the rows loaded, so it follows a
         * reload (an edit) and is gone with the item (a delete) or the level (navigating).
         */
        detailsItem() {
            if (!this.detailsKey) {
                return null;
            }
            return this.items.find((item) => (item.type === 'file' || item.type === 'folder') && keyOf(item) === this.detailsKey) || null;
        },
        /** Where the item of the details panel lies: a hit's own path, else the level open. */
        detailsLocation() {
            const location = this.detailsItem ? itemLocation(this.detailsItem) : null;
            if (location) {
                return location.label;
            }
            return [this.container?.space?.name, ...(this.path || []).map((crumb) => crumb.title)].filter(Boolean).join(' › ');
        },
        /**
         * The like state the details' Discussion tab shows: none where the like module is off,
         * or where nobody liked the item and the caller may not.
         */
        detailsLikeState() {
            const state = this.detailsItem ? this.likeStates[this.detailsItem.recordId] : null;
            return state && (state.canLike || state.total > 0) ? state : null;
        },
    },
    methods: {
        showDetails(item) {
            this.detailsKey = keyOf(item);
            this.mirrorDetails();
            this.$nextTick(() => this.$refs.details?.focus());
        },
        closeDetails() {
            this.detailsKey = null;
            this.mirrorDetails();
        },
        /** Writes `details` into the current history entry, so a reload or a copied link keeps it. */
        mirrorDetails() {
            const url = new URL(window.location.href);
            if (this.detailsKey) {
                url.searchParams.set('details', this.detailsKey);
            } else {
                url.searchParams.delete('details');
            }
            if (url.href === window.location.href) {
                return;
            }
            const state = window.history.state;
            // PJAX compares its own `state.url` on popstate (see `alignUrl`); kept current.
            const next = state && typeof state === 'object' && typeof state.url === 'string'
                ? { ...state, url: url.href }
                : state;
            window.history.replaceState(next, '', url.pathname + url.search + url.hash);
        },
    },
};
