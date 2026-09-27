/**
 * Module-side test setup, loaded after the core's own (see vitest.config.mjs).
 *
 * The core's setup builds the platform stubs (`globalThis.humhub`, jQuery, the module
 * registry); this one boots the island runtime on top of them and makes the core components
 * this module nests resolvable, so a test can mount `CfilesFileBrowser` without repeating the
 * wiring. In production those come from the Vue component registry, which
 * `CfilesVueAsset`'s dependencies guarantee is populated first.
 */
import { config } from '@vue/test-utils';
import { IntlMessageFormat } from 'intl-messageformat';
import ContentControls from '@core/modules/content/vue/ContentControls.vue';
import LikeButton from '@core/modules/like/vue/LikeButton.vue';
import UserFilterControl from '@core/modules/user/vue/UserFilterControl.vue';
import UserImage from '@core/modules/user/vue/UserImage.vue';
import UserList from '@core/modules/user/vue/UserList.vue';
import CheckboxField from '@core/vue/CheckboxField.vue';
import DropdownMenu from '@core/vue/DropdownMenu.vue';
import DropZone from '@core/vue/DropZone.vue';
import FilterBar from '@core/vue/FilterBar.vue';
import HumHubForm from '@core/vue/HumHubForm.vue';
import PageToolbar from '@core/vue/PageToolbar.vue';
import PathBar from '@core/vue/PathBar.vue';
import ProgressFrame from '@core/vue/ProgressFrame.vue';
import SelectionMenu from '@core/vue/SelectionMenu.vue';
import SelectField from '@core/vue/SelectField.vue';
import SubmitButton from '@core/vue/SubmitButton.vue';
import TextField from '@core/vue/TextField.vue';
import TextareaField from '@core/vue/TextareaField.vue';
import TileGrid from '@core/vue/TileGrid.vue';
import UiModal from '@core/vue/UiModal.vue';
import ViewSwitch from '@core/vue/ViewSwitch.vue';
import CfilesItemForm from '../../../vue/CfilesItemForm.vue';

await import('@core/resources/js/humhub/humhub.url.js');
await import('@core/resources/js/humhub/humhub.vue.js');

// The `user` filter type of the FilterBar (the Author filter), registered as the user module's
// Vue entry does in production (`modules/user/vue/index.js`, loaded through UserVueAsset).
globalThis.humhub.modules.vue.register('UserFilterControl', UserFilterControl);
globalThis.humhub.modules.vue.registerFilterType('user', 'UserFilterControl');

/**
 * Formats messages the way the platform does, rather than the way the core's test stub does.
 *
 * `humhub.i18n.t()` runs EVERY message through IntlMessageFormat — including the untranslated
 * source text, which is the normal case in tests — and the platform ships that library as an
 * asset (`IntlMessageFormatAsset`, a dependency of `CoreApiAsset`). The core's stub only
 * substitutes `{placeholder}`, so an ICU plural would render as its own source text here and a
 * broken one would pass unnoticed. This module uses plurals, so the harness has to be faithful.
 */
const formatters = new Map();

globalThis.humhubStubs.i18n.t = (category, message, params) => {
    const locale = globalThis.humhub.config.module('i18n').language || 'en';
    const key = locale + '\u0000' + message;

    if (!formatters.has(key)) {
        formatters.set(key, new IntlMessageFormat(String(message), locale, undefined, { ignoreTag: true }));
    }

    return formatters.get(key).format(params || {});
};

config.global.components = {
    CheckboxField,
    // The platform's own island this module nests: a like link per row. Registered in
    // production by CfilesVueAsset's dependency on LikeVueAsset.
    LikeButton,
    ContentControls,
    DropdownMenu,
    HumHubForm,
    SelectField,
    SubmitButton,
    TextField,
    TextareaField,
    UiModal,
    UserFilterControl,
    UserImage,
    // Nested by LikeButton's "who liked this" modal.
    UserList,
    // The core's item-browser kit the file browser is built on. Registered in production by
    // the core's Vue component registry, like the components above.
    DropZone,
    FilterBar,
    PageToolbar,
    PathBar,
    ProgressFrame,
    SelectionMenu,
    TileGrid,
    ViewSwitch,
    // Referenced by tag from CfilesFileBrowser's modals; auto-registered in production
    // because it is a top-level file in vue/.
    CfilesItemForm,
};

// `v-additions` is registered by the island runtime on the real app. Stands in for that here
// so markup handed to the legacy enhancer pipeline takes the same path.
config.global.directives = {
    additions: {
        mounted(el) {
            globalThis.humhubStubs.additions.applyTo(jQuery(el));
        },
        updated(el) {
            globalThis.humhubStubs.additions.applyTo(jQuery(el));
        },
    },
};
