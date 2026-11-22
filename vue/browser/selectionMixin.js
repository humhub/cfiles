/**
 * The selection of the file browser: which items of the level shown are selected — by their
 * key, so a selection survives a reload of the rows — and the selection's menu.
 *
 * Both views report clicks the same way (`toggle-select`, with `range` for Shift), so ranges
 * work alike in tiles and rows, in the order the level shows. Escape clears the selection, the
 * menu offers what can be done with it (`selectionEntries`, the `cfiles.selection` menu other
 * modules add to).
 *
 * A mixin of the island, see its docblock. Uses the level shown (`items`, `writable`,
 * `movable`), the dialogs open (`showMove`, `showCreate`, `showEdit`), the actions
 * (`openMove()`, `openCopy()`, `confirmDelete()`) and the `pathBar` ref.
 */
import { i18n } from '@humhub/vue';
import { keyOf } from './api';

export default {
    data() {
        return {
            selection: [],
            // The item the last plain selection click was on, where a Shift range starts.
            anchorKey: null,
        };
    },
    computed: {
        selectedItems() {
            return this.items.filter((item) => this.selection.indexOf(keyOf(item)) !== -1);
        },
        selectionEntries() {
            return [
                { id: 'select-all', sortOrder: 10, icon: 'checks', label: i18n.t('CfilesModule.base', 'Select all'), onClick: () => this.selectAll() },
                ...(this.movable ? [{ id: 'move', sortOrder: 20, icon: 'arrows-left-right', label: i18n.t('CfilesModule.base', 'Move'), onClick: () => this.openMove(this.selectedItems) }] : []),
                ...(this.writable ? [{ id: 'copy', sortOrder: 25, icon: 'copy', label: i18n.t('CfilesModule.base', 'Copy'), onClick: () => this.openCopy(this.selectedItems) }] : []),
                { id: 'delete', sortOrder: 30, icon: 'trash', label: i18n.t('CfilesModule.base', 'Delete'), onClick: () => this.confirmDelete(this.selectedItems) },
                { id: 'divider', sortOrder: 90, divider: true },
                {
                    id: 'clear',
                    sortOrder: 100,
                    icon: 'x',
                    label: i18n.t('CfilesModule.base', 'Clear selection'),
                    // The menu goes with the selection, and the focus it had with it.
                    onClick: () => {
                        this.clearSelection();
                        this.$nextTick(() => this.$refs.pathBar?.focusCurrent());
                    },
                },
            ];
        },
    },
    mounted() {
        document.addEventListener('keydown', this.onKeydown);
    },
    beforeUnmount() {
        document.removeEventListener('keydown', this.onKeydown);
    },
    methods: {
        /**
         * Flips one item, or — with `range` (Shift) — adds everything between the last item
         * clicked and this one, in the order both views show. A Shift-click without an
         * earlier click (or after the anchor left the page) is a plain toggle.
         */
        toggleSelect(item, { range } = {}) {
            const key = keyOf(item);
            const keys = this.items.map(keyOf);

            if (range && this.anchorKey !== null && keys.includes(this.anchorKey) && keys.includes(key)) {
                const [from, to] = [keys.indexOf(this.anchorKey), keys.indexOf(key)].sort((a, b) => a - b);
                const span = keys.slice(from, to + 1);
                this.selection = keys.filter((k) => this.selection.includes(k) || span.includes(k));
            } else if (this.selection.includes(key)) {
                this.selection = this.selection.filter((k) => k !== key);
            } else {
                this.selection = this.selection.concat(key);
            }

            this.anchorKey = key;
        },
        /**
         * Selects every LOADED item.
         *
         * Deliberately not "everything in this folder": with paging that would arm the delete
         * action with rows the reader has never seen.
         */
        selectAll() {
            this.selection = this.items.map(keyOf);
        },
        clearSelection() {
            this.selection = [];
            this.anchorKey = null;
        },
        onKeydown(event) {
            // An open dialog or menu takes its own Escape; the selection it acts on stays.
            if (event.key !== 'Escape' || !this.selection.length || event.defaultPrevented
                || this.showMove || this.showCreate || this.showEdit) {
                return;
            }
            // Only the browser's: not that of another part of the page (a modal, another
            // island), nor that of a text field, which takes Escape itself.
            const target = event.target;
            if (target instanceof Element && target !== document.body
                && (!this.$el.contains(target) || target.closest('input:not([type="checkbox"]):not([type="radio"]), textarea, select, [contenteditable]'))) {
                return;
            }
            this.clearSelection();
        },
    },
};
