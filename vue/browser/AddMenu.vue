<template>
    <div class="cfiles-add-menu">
        <button
            type="button"
            class="btn btn-light btn-icon-only cfiles-add-folder"
            :aria-label="folderLabel"
            :title="folderLabel"
            @click="$emit('create-folder')"
        ><i class="ti ti-folder-plus" aria-hidden="true"></i></button>
        <button
            v-if="!handlers.length"
            type="button"
            class="btn btn-accent btn-icon-only cfiles-add-upload"
            :aria-label="uploadLabel"
            :title="uploadLabel"
            @click="$emit('upload')"
        ><i class="ti ti-upload" aria-hidden="true"></i></button>
        <div v-else class="dropdown">
            <button
                type="button"
                class="btn btn-accent btn-icon-only"
                data-bs-toggle="dropdown"
                aria-haspopup="true"
                aria-expanded="false"
                :aria-label="addLabel"
                :title="addLabel"
            ><i class="ti ti-plus" aria-hidden="true"></i></button>
            <ul class="dropdown-menu dropdown-menu-end">
                <li>
                    <a href="#" class="dropdown-item cfiles-add-menu-entry" @click.prevent="$emit('upload')">
                        <i class="ti ti-upload dropdown-item-icon" aria-hidden="true"></i>{{ uploadLabel }}
                    </a>
                </li>
                <li><hr class="dropdown-divider" /></li>
                <li v-for="(handler, index) in handlers" :key="index">
                    <a
                        class="dropdown-item"
                        href="#"
                        v-bind="handler.attributes"
                        data-action-process="file-handler"
                        @click="onHandler(handler, $event)"
                    ><i v-if="handler.icon" :class="'dropdown-item-icon ti ti-' + handler.icon" aria-hidden="true"></i>{{ handler.label }}</a>
                </li>
            </ul>
        </div>
    </div>
</template>

<script>
/**
 * The browser's "add" buttons in the page header: a new folder, and upload files — a plain
 * button that opens the file picker. Where a module contributed file handlers ("New
 * spreadsheet", "Import from …") the upload button becomes a "+" menu instead: upload files,
 * then — after a divider — the handlers.
 *
 * The toggle skips the `dropdown-toggle` class: Bootstrap's dropdown JS is wired purely off
 * `data-bs-toggle="dropdown"` (see `DropdownMenu`'s own doc comment), and the class itself
 * contributes nothing but the `::after` caret — so this plain icon button needs no override CSS.
 *
 * The handlers are the page's (the core JS config's `file.handlers`: `{label, icon,
 * attributes}`), rendered as links carrying their attributes, so a module's action
 * (`data-action-click`) runs through humhub.action.js's document-level delegate. The
 * platform's own "upload this type" entries (`file.uploadByType`) are taken over: they ask
 * for the browser's own picker with that type (`upload(accept)`) - the legacy action looks for
 * an upload button this page does not have.
 *
 * A click on a handler is reported (`handler`): what a handler creates is announced on the
 * global `humhub:file:created` event without saying where it was asked for, so the browser has
 * to remember which level the menu was opened on.
 */
import { i18n } from '@humhub/vue';

/** The action of the platform's "upload this type" handlers (`humhub.file.js`). */
const UPLOAD_BY_TYPE_ACTION = 'file.uploadByType';

export default {
    props: {
        /** The file handlers (`{label, icon, attributes}`, see the class docblock). */
        handlers: { type: Array, default: () => [] },
    },
    emits: ['upload', 'create-folder', 'handler'],
    methods: {
        onHandler(handler, event) {
            const attributes = handler.attributes || {};

            if (attributes['data-action-click'] !== UPLOAD_BY_TYPE_ACTION) {
                this.$emit('handler');
                return;
            }

            // Not the legacy action as well (see the class docblock)
            event.preventDefault();
            event.stopPropagation();

            let accept = null;
            try {
                accept = JSON.parse(attributes['data-action-params'] || '{}').type || null;
            } catch (error) {
                accept = null;
            }
            this.$emit('upload', accept);
        },
    },
    computed: {
        addLabel() {
            return i18n.t('CfilesModule.base', 'Add');
        },
        uploadLabel() {
            return i18n.t('CfilesModule.base', 'Upload files');
        },
        folderLabel() {
            return i18n.t('CfilesModule.base', 'New folder');
        },
    },
};
</script>
