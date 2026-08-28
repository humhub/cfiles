<template>
    <HumHubForm ref="form" :busy="busy" @submit="submit">
        <TextField attribute="title" v-model="values.title" :label="titleLabel" :required="true" />
        <TextareaField attribute="description" v-model="values.description" :label="descriptionLabel" :rows="3" />
        <SelectField
            attribute="visibility"
            v-model="values.visibility"
            :label="visibilityLabel"
            :options="visibilityOptions"
            :hint="isFolder ? visibilityHint : null"
        />

        <div class="d-flex justify-content-end gap-2">
            <button type="button" class="btn btn-light" @click="$emit('cancel')">{{ cancelLabel }}</button>
            <SubmitButton class="btn btn-primary">{{ saveLabel }}</SubmitButton>
        </div>
    </HumHubForm>
</template>

<script>
/**
 * Create/rename dialog for a file or a folder — one form for both kinds, and for both places
 * it is opened from: inline in the file browser, and inside the modal the stream's wall entry
 * controls load (`cfiles/edit/file`, see `controllers/EditController`). There is one edit
 * form in this module, not two.
 *
 * `standalone` is what tells the two callers apart. Inside the browser the form emits `saved`
 * and the list updates in place; opened from the stream there is no list to update and no
 * parent island to emit into, so it reloads the page the platform's modal is sitting on.
 *
 * Field names are bare (`title`, not `Folder[title]`): `HumHubForm` gets no `modelName`, so
 * the inputs and the API's 422 keys line up without a mapping step.
 */
import { i18n, log } from '@humhub/vue';
import { createFolder, updateItem } from './browser/api';

export default {
    i18nCategories: ['CfilesModule.base', 'base'],
    props: {
        /** The serialized item being edited; omit (with `parentFolderId` set) to create. */
        item: { type: Object, default: null },
        /** The container to create in — required together with `parentFolderId`. */
        contentContainerId: { type: Number, default: null },
        /** Set when creating a folder; null creates it at the container's top level. */
        parentFolderId: { type: Number, default: null },
        standalone: { type: Boolean, default: false },
    },
    emits: ['saved', 'cancel'],
    data() {
        return {
            busy: false,
            values: {
                title: this.item ? this.item.title : '',
                description: this.item ? this.item.description : '',
                visibility: this.item ? String(this.item.visibility) : '1',
            },
        };
    },
    computed: {
        isFolder() {
            return this.item ? this.item.type === 'folder' : true;
        },
        isCreate() {
            return this.item === null;
        },
        titleLabel() {
            return this.isFolder
                ? i18n.t('CfilesModule.base', 'Title')
                : i18n.t('CfilesModule.base', 'File name');
        },
        descriptionLabel() {
            return i18n.t('CfilesModule.base', 'Description');
        },
        visibilityLabel() {
            return i18n.t('CfilesModule.base', 'Visibility');
        },
        visibilityHint() {
            return i18n.t('CfilesModule.base', 'Note: Changes of the folders visibility, will be inherited by all contained files and folders.');
        },
        visibilityOptions() {
            return [
                { value: '1', label: i18n.t('CfilesModule.base', 'Public') },
                { value: '0', label: i18n.t('CfilesModule.base', 'Private') },
            ];
        },
        saveLabel() {
            return i18n.t('base', 'Save');
        },
        cancelLabel() {
            return i18n.t('base', 'Cancel');
        },
    },
    methods: {
        /**
         * Focuses the title field — what the dialog this form sits in calls once it is open,
         * so creating a folder is type-and-enter instead of click-then-type.
         */
        focus() {
            this.$refs.form.focusFirstField();
        },
        submit() {
            if (this.busy) {
                return;
            }

            this.busy = true;
            this.$refs.form.clearErrors();

            const attributes = {
                title: this.values.title,
                description: this.values.description,
                visibility: Number(this.values.visibility),
            };

            const request = this.isCreate
                ? createFolder(this.contentContainerId, this.parentFolderId, attributes)
                : updateItem(this.item, attributes);

            request.then((saved) => {
                this.busy = false;

                if (this.standalone) {
                    // Nothing here owns the surrounding page — hand it back to the platform.
                    window.location.reload();
                    return;
                }

                this.$emit('saved', saved);
            }).catch((response) => {
                this.busy = false;

                // 422 {"errors": {attribute: [messages]}}, flattened onto the rejected
                // response by the client — same contract the comment form consumes.
                if (response && response.status === 422 && response.errors) {
                    this.$refs.form.setErrors({ errors: response.errors });
                    return;
                }

                log.error(response, true);
            });
        },
    },
};
</script>
