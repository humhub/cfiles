<template>
    <div class="dropdown cfiles-add-menu">
        <button
            type="button"
            class="btn btn-accent c-icon-button"
            data-bs-toggle="dropdown"
            aria-haspopup="true"
            aria-expanded="false"
            :aria-label="addLabel"
            :title="addLabel"
        ><i class="ti ti-plus" aria-hidden="true"></i></button>
        <ul class="dropdown-menu dropdown-menu-end">
            <li>
                <a href="#" class="dropdown-item d-flex align-items-center gap-2 cfiles-add-menu__entry" @click.prevent="$emit('upload')">
                    <i class="ti ti-upload" aria-hidden="true"></i>{{ uploadLabel }}
                </a>
            </li>
            <li>
                <a href="#" class="dropdown-item d-flex align-items-center gap-2 cfiles-add-menu__entry" @click.prevent="$emit('create-folder')">
                    <i class="ti ti-folder-plus" aria-hidden="true"></i>{{ folderLabel }}
                </a>
            </li>
            <li v-if="handlersHtml"><hr class="dropdown-divider" /></li>
            <li v-if="handlersHtml"><ul class="list-unstyled m-0" v-additions v-html="handlersHtml"></ul></li>
        </ul>
    </div>
</template>

<script>
/**
 * The browser's "add" menu, the "+" in the page toolbar: upload files, create a folder and —
 * after a divider — whatever file handlers a module contributed ("New spreadsheet", "Import
 * from …").
 *
 * The toggle skips the `dropdown-toggle` class: Bootstrap's dropdown JS is wired purely off
 * `data-bs-toggle="dropdown"` (see `DropdownMenu`'s own doc comment), and the class itself
 * contributes nothing but the `::after` caret — so this plain icon button needs no override CSS.
 *
 * The handlers stay server-rendered `<li>` markup that has to land in the SAME menu, after the
 * divider — `DropdownMenu` can't do that, it wraps every `html` entry in an `<li>` of its own.
 * It gets a nested `<ul v-additions v-html>` instead, inside its own `<li>`: valid HTML, reactive
 * for free (unlike a one-off DOM splice), and Bootstrap's dropdown keyboard nav selects
 * `.dropdown-item` as a descendant of `.dropdown-menu` regardless of nesting depth. Same escape
 * hatch the old toolbar and the core's `UploadField` use. It isn't there for `data-action-click`
 * — humhub.action.js delegates that on `document`, so it already works unassisted — but for the
 * rest of the additions pass any server-rendered fragment gets (`data-ui-init` widgets, `.po`
 * popovers, nested Vue mount points, …).
 */
import { i18n } from '@humhub/vue';

export default {
    props: {
        /** Server-rendered `<li>` markup for the file handlers a module contributed. */
        handlersHtml: { type: String, default: '' },
    },
    emits: ['upload', 'create-folder'],
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
