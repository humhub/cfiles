<template>
    <UiModal :show="show" :title="title" @update:show="$emit('close')">
        <p :id="introId" class="text-muted">{{ intro }}</p>

        <div class="list-group cfiles-move-tree" role="tree" :aria-labelledby="introId">
            <div
                v-for="node in flatTree"
                :key="keyOf(node)"
                :ref="(el) => remember(node, el)"
                class="list-group-item list-group-item-action"
                :class="{ active: isSelected(node) }"
                :style="{ '--hh-cfiles-depth': node.depth }"
                role="treeitem"
                :aria-level="node.depth + 1"
                :aria-selected="String(isSelected(node))"
                :aria-expanded="node.hasChildren === false ? undefined : String(node.expanded)"
                :tabindex="keyOf(node) === focusedKey ? 0 : -1"
                @click="select(node)"
                @focus="focusedKey = keyOf(node)"
                @keydown="onKeydown($event, node)"
            >
                <i
                    class="ti icon-fw"
                    :class="node.expanded ? 'ti-caret-down-filled' : (node.hasChildren === false ? '' : 'ti-caret-right-filled')"
                    aria-hidden="true"
                    @click.stop="toggle(node)"
                ></i>
                <i class="ti ti-folder-filled text-muted" aria-hidden="true"></i>
                {{ node.isTop ? rootLabel : node.title }}
            </div>
        </div>

        <p v-if="error" class="text-danger mt-2 mb-0">{{ error }}</p>

        <template #footer>
            <button type="button" class="btn btn-light" @click="$emit('close')">{{ cancelLabel }}</button>
            <button
                type="button"
                class="btn btn-primary"
                :disabled="selectedId === undefined || busy"
                @click="$emit('confirm', selectedId)"
            >{{ confirmLabel }}</button>
        </template>
    </UiModal>
</template>

<script>
/**
 * Folder picker for moving or copying items (`mode`).
 *
 * Lazily expanded: each node loads its own subfolders from the same listing endpoint the
 * browser uses (filtered to folders, every page of them), so no separate tree endpoint exists.
 * It opens on the folder the items are in (`currentPath`), with the way to it expanded.
 *
 * A tree in the ARIA sense, and one tab stop: the arrow keys walk its nodes (Right expands
 * and enters a folder, Left closes it or goes up), Enter or Space chooses one.
 */
import { i18n, log } from '@humhub/vue';
import { loadItems } from './api';

/** The page the subfolders are loaded in: the largest the listing serves (`MAX_PAGE_SIZE`). */
const PAGE_SIZE = 200;

let dialogSeq = 0;

export default {
    props: {
        show: { type: Boolean, default: false },
        contentContainerId: { type: Number, required: true },
        // Items being moved — they and their descendants are not valid targets.
        items: { type: Array, default: () => [] },
        /** `move` or `copy` — what the chosen folder is for. */
        mode: { type: String, default: 'move' },
        /**
         * The folders from the top down to the one the items are in (`[{id, title}]`, the
         * listing's `path`), empty at the top level: what the dialog opens on.
         */
        currentPath: { type: Array, default: () => [] },
        busy: { type: Boolean, default: false },
        error: { type: String, default: null },
    },
    emits: ['close', 'confirm'],
    data() {
        return { nodes: [], selectedId: undefined, focusedKey: 'top', introId: 'cfiles-move-intro-' + ++dialogSeq };
    },
    created() {
        // The rendered node of each key, for moving the focus; not reactive state.
        this.elements = new Map();
    },
    watch: {
        show: {
            immediate: true,
            handler(open) {
                if (open) {
                    this.open();
                }
            },
        },
    },
    computed: {
        isCopy() {
            return this.mode === 'copy';
        },
        title() {
            return this.confirmLabel;
        },
        intro() {
            return this.isCopy
                ? i18n.t('CfilesModule.base', 'Choose the folder to copy the selection into.')
                : i18n.t('CfilesModule.base', 'Choose the folder to move the selection into.');
        },
        rootLabel() {
            return i18n.t('CfilesModule.base', 'Files');
        },
        cancelLabel() {
            return i18n.t('base', 'Cancel');
        },
        confirmLabel() {
            return this.isCopy
                ? i18n.t('CfilesModule.base', 'Copy')
                : i18n.t('CfilesModule.base', 'Move');
        },
        movedFolderIds() {
            return this.items.filter((item) => item.type === 'folder').map((item) => item.id);
        },
        flatTree() {
            const flatten = (nodes) => nodes.reduce((all, node) => {
                all.push(node);
                if (node.expanded && node.children) {
                    all.push(...flatten(node.children));
                }
                return all;
            }, []);

            return flatten(this.nodes);
        },
    },
    methods: {
        /**
         * Starts over at the top level, then expands the way down to the folder the items are
         * in and chooses it. The top level is a node with no id: `null` is what the move
         * endpoint takes for "no parent".
         */
        open() {
            this.nodes = [{ id: null, title: '', isTop: true, depth: 0, expanded: false, children: null, parent: null }];
            // The reactive node, not the object it was made of: what changes it has to show.
            const top = this.nodes[0];
            this.selectedId = undefined;
            this.focusedKey = 'top';

            const walk = (node, path) => this.expand(node).then(() => {
                const next = path.length ? (node.children || []).find((child) => child.id === path[0].id) : null;
                if (next) {
                    return walk(next, path.slice(1));
                }
                // The deepest folder of the path that is offered — the one the items are in.
                this.select(node);
                this.focusedKey = this.keyOf(node);
                return undefined;
            });

            walk(top, this.currentPath);
        },
        keyOf(node) {
            return node.isTop ? 'top' : String(node.id);
        },
        isSelected(node) {
            return this.selectedId !== undefined && node.id === this.selectedId;
        },
        remember(node, element) {
            if (element) {
                this.elements.set(this.keyOf(node), element);
            } else {
                this.elements.delete(this.keyOf(node));
            }
        },
        select(node) {
            this.selectedId = node.id;
        },
        toggle(node) {
            if (node.expanded) {
                node.expanded = false;
                return;
            }
            this.expand(node);
        },
        /** Expands the node, loading its subfolders the first time. */
        expand(node) {
            node.expanded = true;

            if (node.children !== null) {
                return Promise.resolve();
            }

            return this.subfolders(node).then((children) => {
                node.children = children;
            }).catch((e) => {
                node.children = [];
                log.error(e, true);
            });
        },
        /**
         * The subfolders of a node, every page of them: a listing puts the folders ahead of the
         * files, so a page that ends with a file holds the last of them.
         */
        subfolders(node) {
            const children = [];

            const load = (page) => loadItems(this.contentContainerId, node.id, { pageSize: PAGE_SIZE, page }).then((payload) => {
                const rows = payload.results || [];

                children.push(...rows
                    .filter((row) => row.type === 'folder')
                    // A folder cannot be moved or copied into itself or into its own subtree;
                    // the server refuses it too, but offering it as a target is just cruel.
                    .filter((row) => this.movedFolderIds.indexOf(row.id) === -1)
                    .map((row) => ({
                        id: row.id,
                        title: row.title,
                        isTop: false,
                        depth: node.depth + 1,
                        expanded: false,
                        children: null,
                        hasChildren: row.itemCount > 0,
                        parent: node,
                    })));

                const more = (payload.page || 1) < (payload.pages || 1) && rows.length > 0 && rows[rows.length - 1].type === 'folder';

                return more ? load(page + 1) : children;
            });

            return load(1);
        },
        focus(node) {
            if (!node) {
                return;
            }
            this.focusedKey = this.keyOf(node);
            this.elements.get(this.focusedKey)?.focus();
        },
        onKeydown(event, node) {
            const flat = this.flatTree;
            const index = flat.indexOf(node);
            const expandable = node.hasChildren !== false;

            const actions = {
                ArrowDown: () => this.focus(flat[index + 1]),
                ArrowUp: () => this.focus(flat[index - 1]),
                Home: () => this.focus(flat[0]),
                End: () => this.focus(flat[flat.length - 1]),
                ArrowRight: () => {
                    if (!expandable) {
                        return;
                    }
                    if (!node.expanded) {
                        this.expand(node);
                    } else if (node.children?.length) {
                        this.focus(node.children[0]);
                    }
                },
                ArrowLeft: () => {
                    if (node.expanded && expandable) {
                        node.expanded = false;
                    } else {
                        this.focus(node.parent);
                    }
                },
                Enter: () => this.select(node),
                ' ': () => this.select(node),
            };

            if (actions[event.key]) {
                event.preventDefault();
                actions[event.key]();
            }
        },
    },
};
</script>
