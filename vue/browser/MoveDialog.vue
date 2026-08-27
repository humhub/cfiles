<template>
    <UiModal :show="show" :title="title" @update:show="$emit('close')">
        <p class="text-muted">{{ intro }}</p>

        <div class="hh-list cfiles-move-tree">
            <div
                v-for="node in flatTree"
                :key="node.id"
                :class="{ selected: node.id === selectedId }"
                :style="{ paddingLeft: (10 + node.depth * 18) + 'px' }"
                role="button"
                tabindex="0"
                @click="select(node)"
                @keydown.enter.prevent="select(node)"
                @keydown.space.prevent="select(node)"
            >
                <i
                    class="fa fa-fw"
                    :class="node.expanded ? 'fa-caret-down' : (node.hasChildren === false ? '' : 'fa-caret-right')"
                    aria-hidden="true"
                    @click.stop="toggle(node)"
                ></i>
                <i class="fa fa-folder text-muted" aria-hidden="true"></i>
                {{ node.isRoot ? rootLabel : node.title }}
            </div>
        </div>

        <p v-if="error" class="text-danger mt-2 mb-0">{{ error }}</p>

        <template #footer>
            <button type="button" class="btn btn-light" @click="$emit('close')">{{ cancelLabel }}</button>
            <button
                type="button"
                class="btn btn-primary"
                :disabled="selectedId === null || busy"
                @click="$emit('confirm', selectedId)"
            >{{ moveLabel }}</button>
        </template>
    </UiModal>
</template>

<script>
/**
 * Folder picker for moving items.
 *
 * Lazily expanded: each node loads its own subfolders from the same listing endpoint the
 * browser uses (filtered to folders), so no separate tree endpoint exists. Phase 2's
 * directory pane will reuse this tree.
 */
import { i18n, log } from '@humhub/vue';
import { loadFolder } from './api';

export default {
    props: {
        show: { type: Boolean, default: false },
        rootId: { type: Number, required: true },
        // Items being moved — they and their descendants are not valid targets.
        items: { type: Array, default: () => [] },
        busy: { type: Boolean, default: false },
        error: { type: String, default: null },
    },
    emits: ['close', 'confirm'],
    data() {
        return { nodes: [], selectedId: null };
    },
    watch: {
        show: {
            immediate: true,
            handler(open) {
                if (open) {
                    this.selectedId = null;
                    this.nodes = [{ id: this.rootId, title: '', isRoot: true, depth: 0, expanded: false, children: null }];
                    this.toggle(this.nodes[0]);
                }
            },
        },
    },
    computed: {
        title() {
            return i18n.t('CfilesModule.base', 'Move');
        },
        intro() {
            return i18n.t('CfilesModule.base', 'Choose the folder to move the selection into.');
        },
        rootLabel() {
            return i18n.t('CfilesModule.base', 'Files');
        },
        cancelLabel() {
            return i18n.t('base', 'Cancel');
        },
        moveLabel() {
            return i18n.t('CfilesModule.base', 'Move');
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
        select(node) {
            this.selectedId = node.id;
        },
        toggle(node) {
            if (node.expanded) {
                node.expanded = false;
                return;
            }

            node.expanded = true;

            if (node.children !== null) {
                return;
            }

            loadFolder(node.id, { pageSize: 200 }).then((payload) => {
                node.children = (payload.results || [])
                    .filter((row) => row.type === 'folder')
                    // A folder cannot be moved into itself or into its own subtree; the
                    // server refuses it too, but offering it as a target is just cruel.
                    .filter((row) => this.movedFolderIds.indexOf(row.id) === -1)
                    .map((row) => ({
                        id: row.id,
                        title: row.title,
                        isRoot: false,
                        depth: node.depth + 1,
                        expanded: false,
                        children: null,
                        hasChildren: row.itemCount > 0,
                    }));
            }).catch((e) => {
                node.children = [];
                log.error(e, true);
            });
        },
    },
};
</script>
