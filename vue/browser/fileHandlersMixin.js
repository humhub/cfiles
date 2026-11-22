/**
 * The file handlers of the file browser's add menu ("New spreadsheet", "Import from …"):
 * which there are, and filing what one of them created into the level it was chosen on.
 *
 * A handler announces the platform file(s) it created on the global `humhub:file:created`
 * event without saying where it was asked for, and leaves it to whoever asked to attach them.
 * So the browser remembers the level the menu was used on (`handlerLevel`), and forgets it
 * once a handler of another field on the page is chosen — what that one creates is the other
 * field's.
 *
 * A mixin of the island, see its docblock. Uses the level shown (`container`, `folderId`, the
 * level on its way: `targetContainer()`, `targetFolderId()`, `reload()`) and `reportErrors()`.
 */
import { events, getConfig, log } from '@humhub/vue';
import { attachFiles } from './api';
import { sameContainer } from './globalLevel';

export default {
    data() {
        return {
            // The level a file handler of the add menu was chosen on: what it creates is filed
            // there (see onFileCreated()).
            handlerLevel: null,
        };
    },
    computed: {
        /**
         * The file handlers modules contribute ("New spreadsheet", "Import from …") and the
         * platform's "upload this type" entries, for the `AddMenu` - the page's, from the core
         * JS config (`file.handlers`).
         */
        fileHandlers() {
            return (getConfig('file') || {}).handlers || [];
        },
    },
    mounted() {
        document.addEventListener('click', this.onAnyClick, true);
        events.on('humhub:file:created', this.onFileCreated);
        events.on('humhub:file:modified', this.onFileModified);
    },
    beforeUnmount() {
        document.removeEventListener('click', this.onAnyClick, true);
        events.off('humhub:file:created', this.onFileCreated);
        events.off('humhub:file:modified', this.onFileModified);
    },
    methods: {
        onHandlerChosen() {
            this.handlerLevel = { container: this.container, parentId: this.folderId };
        },
        /**
         * Any file handler entry chosen on the page - the add menu's, or another field's (the
         * comment form of the details panel) - ends an earlier choice of the add menu: what
         * that handler creates is the other field's. Listened to in the capture phase, so the
         * add menu's own entry claims the choice again afterwards (`onHandlerChosen`). The core's
         * `UploadField` keeps its choice the same way.
         */
        onAnyClick(event) {
            if (event.target instanceof Element && event.target.closest('a[data-action-process="file-handler"]')) {
                this.handlerLevel = null;
            }
        },
        /**
         * Files what a file handler of the add menu created ("New spreadsheet") into the level
         * it was chosen on. The handler announces the new platform file(s) on the global
         * `humhub:file:created` event and leaves it to whoever asked to attach them - the
         * browser did, so it does. An event no handler of this menu caused is not ours.
         */
        onFileCreated(event, ...files) {
            const level = this.handlerLevel;
            const guids = files.flat().map((file) => file && file.guid).filter(Boolean);

            if (!level || !guids.length) {
                return;
            }

            this.handlerLevel = null;

            attachFiles(level.container.contentContainerId, level.parentId, guids).then((response) => {
                this.reportErrors(response.errors);

                if (sameContainer(level.container, this.targetContainer()) && level.parentId === this.targetFolderId()) {
                    this.reload();
                }
            }).catch((response) => {
                log.error(response, true);
            });
        },
        /** A file edited through a handler (e.g. saved in an office editor) may show changed. */
        onFileModified() {
            this.reload();
        },
    },
};
