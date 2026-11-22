/**
 * How the file browser shows a level, and how much of it: the display (tiles or rows, `view`),
 * the page each display asks for, and the further pages ("Show more", `loadMore()`).
 *
 * The display is the user's preference: the first payload says which it is, from there on the
 * island owns it and stores a change on its own.
 *
 * A mixin of the island, see its docblock. Uses the level (`items`, `likeStates`, `page`,
 * `pages`, `total`, `loading`, `loadingMore`, `folderId`, `container`, `loadSeq`,
 * `fetchLevel()`, `reload()`) and the sort.
 */
import { i18n, log } from '@humhub/vue';
import { keyOf, savePreferences } from './api';
import { sameContainer } from './globalLevel';

/**
 * The page each view asks for (`FolderListingService::VIEWS`): a tile grid fits more on a
 * screen than a row list.
 */
export const PAGE_SIZES = { list: 50, tiles: 96 };

export default {
    data() {
        return {
            // The payload's `view` is the stored preference; from here on the island owns it.
            view: this.listing.view,
        };
    },
    computed: {
        viewOptions() {
            return [
                { value: 'tiles', icon: 'layout-grid', label: i18n.t('CfilesModule.base', 'Tiles') },
                { value: 'list', icon: 'list', label: i18n.t('CfilesModule.base', 'List') },
            ];
        },
        viewLabel() {
            return i18n.t('CfilesModule.base', 'View');
        },
    },
    methods: {
        /** The page the current view asks for. */
        pageSize() {
            return PAGE_SIZES[this.view] || PAGE_SIZES.list;
        },
        /**
         * Switches display, remembers it and reloads with the new view's page size.
         *
         * The preference is stored on its own (`PATCH preferences`), fire-and-forget: failing
         * to remember it is logged, the switch happens all the same.
         */
        setView(view) {
            if (view === this.view) {
                return;
            }

            this.view = view;
            savePreferences({ view }).catch((e) => log.error(e, true));
            this.reload();
        },
        loadMore() {
            // While the level (re)loads, its rows are about to be replaced.
            if (this.loading || this.loadingMore || this.page >= this.pages) {
                return;
            }
            this.loadingMore = true;

            const seq = this.loadSeq;
            const folderId = this.folderId;
            const container = this.container;
            // Overtaken by an `open()`, or of a level no longer shown: dropped, and
            // `loadingMore` is `open()`'s to reset then.
            const stale = () => seq !== this.loadSeq || folderId !== this.folderId || !sameContainer(container, this.container);

            this.fetchLevel(container, folderId, {
                sort: this.sort,
                page: this.page + 1,
                pageSize: this.pageSize(),
            })
                .then((payload) => {
                    if (stale()) {
                        return;
                    }
                    // Pages are offsets: an item added to the level since the last one pushed
                    // its rows down, and a row shown already comes again.
                    const shown = new Set(this.items.map(keyOf));
                    this.items = this.items.concat(payload.results.filter((row) => !shown.has(keyOf(row))));
                    // A further page brings the like states of its own rows only.
                    this.likeStates = { ...this.likeStates, ...(payload.likeStates || {}) };
                    this.page = payload.page;
                    this.pages = payload.pages;
                    this.total = payload.total;
                    this.loadingMore = false;
                })
                .catch((e) => {
                    if (stale()) {
                        return;
                    }
                    this.loadingMore = false;
                    log.error(e, true);
                });
        },
    },
};
