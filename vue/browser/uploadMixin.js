/**
 * Uploading into the file browser: the file picker, the question about names that are taken,
 * and what the level shows while a batch is on its way.
 *
 * An upload into the open level shows a placeholder per file (`{ type: 'upload', id, title,
 * uploading: true, progress }`) after the items; one into a folder shown here marks that
 * folder as uploading instead (`displayItems`). Both go once the batch is in and the level is
 * up to date — refreshed in place, not reloaded: the selection, the further pages and the
 * details stay (`refresh()`).
 *
 * A mixin of the island, see its docblock. Uses the level shown (`items`, `folder`, `folderId`,
 * `container`, `writable`, the level on its way: `targetContainer()`, `targetFolderId()`,
 * `refresh()`), `rootLabel` and `reportErrors()`; the template holds the picker (`fileInput`)
 * and the `ConflictDialog`.
 */
import { i18n, log, status } from '@humhub/vue';
import { findConflicts, keyOf, uploadFiles } from './api';
import { sameContainer } from './globalLevel';

// Ids for upload placeholders, unique for the page's lifetime.
let uploadSeq = 0;

export default {
    data() {
        return {
            // The names an upload found taken, while the user is asked about them (ConflictDialog).
            conflicts: [],
            // Placeholders for the files being uploaded into the open level.
            uploads: [],
            // Folders shown here that files are being uploaded into: key => { batch => progress }.
            uploadTargets: {},
        };
    },
    computed: {
        /** The items with their grid key, the uploads in progress appended. */
        displayItems() {
            const items = this.items.map((item) => {
                const key = keyOf(item);
                const batches = this.uploadTargets[key];
                return batches
                    // The slowest batch: the folder is done once they all are.
                    ? { ...item, key, uploading: true, progress: Math.min(...Object.values(batches)) }
                    : { ...item, key };
            });
            const uploads = this.uploads.filter((upload) => upload.parent === this.folderId
                && upload.containerId === this.container?.contentContainerId);
            return items.concat(uploads.map((upload) => ({ ...upload, key: keyOf(upload) })));
        },
    },
    created() {
        // Settles the open conflict question (see `askConflicts()`). Not reactive, nothing
        // renders from it.
        this.conflictAnswer = null;
    },
    methods: {
        /** Opens the picker, for one type (`image/*`) when a handler asks for one. */
        pickFiles(accept = null) {
            const input = this.$refs.fileInput;
            if (accept) {
                input.setAttribute('accept', accept);
            } else {
                input.removeAttribute('accept');
            }
            input.click();
        },
        onFilesPicked(event) {
            this.upload(event.target.files);
            // Clear it, or picking the same file twice in a row is a no-op.
            event.target.value = '';
        },
        /**
         * Uploads a batch into `parentId` (the open level by default) — after asking about the
         * names that are taken there: the user decides whether those are replaced (a new
         * version), kept both (an indexed name) or skipped (see ConflictDialog).
         */
        upload(files, parentId = this.folderId, options = {}) {
            if (!files || !files.length || !this.writable) {
                return Promise.resolve();
            }

            const list = Array.from(files);
            // Where it goes, captured now: by the time the user answered (and the upload is in)
            // another level, on the global page another space, may be open.
            const container = this.container;
            const intoLevel = parentId === this.folderId;
            options = { ...options, intoLevel, title: options.title ?? (intoLevel ? this.folder?.title : null) };

            return findConflicts(container.contentContainerId, parentId, list.map((file) => file.name)).then((conflicts) => {
                if (!conflicts.length) {
                    return this.sendUpload(list, parentId, options, container);
                }

                return this.askConflicts(conflicts).then((choice) => {
                    if (choice === 'replace' || choice === 'keep') {
                        const replace = choice === 'replace'
                            ? conflicts.filter((conflict) => conflict.canReplace).map((conflict) => conflict.name)
                            : [];
                        return this.sendUpload(list, parentId, options, container, replace);
                    }

                    const taken = new Set(conflicts.map((conflict) => conflict.name));
                    const rest = choice === 'skip' ? list.filter((file) => !taken.has(file.name)) : [];
                    return rest.length ? this.sendUpload(rest, parentId, options, container) : undefined;
                });
            }).catch((response) => {
                log.error(response, true);
            });
        },
        /**
         * Asks the user about the names an upload found taken; settles with their choice —
         * `replace`, `keep`, `skip` or `cancel`. A question still open is given up (`cancel`).
         */
        askConflicts(conflicts) {
            this.settleConflicts('cancel');

            return new Promise((resolve) => {
                this.conflictAnswer = resolve;
                this.conflicts = conflicts;
            });
        },
        settleConflicts(choice) {
            const answer = this.conflictAnswer;
            this.conflictAnswer = null;
            this.conflicts = [];

            if (answer) {
                answer(choice);
            }
        },
        /**
         * Sends an upload `upload()` settled; where it goes was captured there.
         *
         * Into the open level each file gets a placeholder after the items (kept with that
         * level: `parent`); into a folder shown here (`targetKey`) that folder shows the
         * progress instead. A batch into a level not open says where it went (`title`) once
         * it is in. The level is brought up to date only when the upload shows on it — in place
         * (`refresh()`), the selection, further pages and details kept — and the placeholders
         * stay until the new rows are there.
         */
        sendUpload(files, parentId, { targetKey = null, title = null, intoLevel = false }, container, replace = []) {
            const batch = ++uploadSeq;
            const folderTitle = title;
            const placeholders = intoLevel
                ? Array.from(files).map((file) => ({
                    type: 'upload', id: ++uploadSeq, parent: parentId, containerId: container.contentContainerId, title: file.name, uploading: true, progress: 0, icon: 'file',
                }))
                : [];
            const ids = placeholders.map((upload) => upload.id);
            const mine = (upload) => ids.includes(upload.id);

            const progress = (percent) => {
                if (intoLevel) {
                    this.uploads = this.uploads.map((upload) => (mine(upload) ? { ...upload, progress: percent } : upload));
                } else if (targetKey) {
                    this.uploadTargets = {
                        ...this.uploadTargets,
                        [targetKey]: { ...this.uploadTargets[targetKey], [batch]: percent },
                    };
                }
            };
            const done = () => {
                this.uploads = this.uploads.filter((upload) => !mine(upload));
                if (targetKey && this.uploadTargets[targetKey]) {
                    const { [batch]: gone, ...others } = this.uploadTargets[targetKey];
                    const { [targetKey]: all, ...rest } = this.uploadTargets;
                    this.uploadTargets = Object.keys(others).length ? { ...rest, [targetKey]: others } : rest;
                }
            };

            this.uploads = this.uploads.concat(placeholders);
            progress(0);

            return uploadFiles(container.contentContainerId, parentId, files, progress, replace).then((response) => {
                this.reportErrors(response.errors);

                const here = sameContainer(container, this.targetContainer());
                const open = here && parentId === this.targetFolderId();
                const shown = open
                    || (here && targetKey !== null && this.items.some((item) => keyOf(item) === targetKey));
                const count = (response.results || []).length;

                if (!open && count) {
                    status('success', i18n.t('CfilesModule.base', '{count, plural, one{# file} other{# files}} uploaded to {folder}', {
                        count,
                        folder: folderTitle || container.space?.name || this.rootLabel,
                    }));
                }

                if (shown) {
                    this.refresh().then(done);
                } else {
                    done();
                }
            }).catch((response) => {
                // Not one file the endpoint says something about: a request that failed as a
                // whole. Files it could not add are in the answer (`errors`), see above.
                done();
                log.error(response, true);
            });
        },
    },
};
