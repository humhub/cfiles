<template>
    <div class="mb-3 cfiles-item-form__topics" :class="{ 'has-error': hasError }">
        <label class="form-label" :for="inputId">{{ label }}</label>
        <TopicFilterControl :model-value="modelValue" :filter="filter" :input-id="inputId" @update:model-value="onInput" />
        <div v-if="hasError" :id="errorId" class="invalid-feedback d-block">
            <div v-for="message in errorMessages" :key="message">{{ message }}</div>
        </div>
    </div>
</template>

<script>
// Ids for the field's input, unique for the page's lifetime.
let uid = 0;

/**
 * The Topics field of the item form: the topic module's `TopicFilterControl` (a global
 * component — `CfilesVueAsset` depends on `TopicVueAsset`) over the topics of a container and
 * the global ones, as a field of the surrounding `HumHubForm`.
 *
 * The core form suite has no field for a custom control (its fields share an internal mixin),
 * so this one takes part the way its fields do, through the form context the `HumHubForm`
 * provides (`humhubForm`): it registers for its `attribute`, so a `422` under it is shown
 * here — below the control, `invalid-feedback d-block`, the input `aria-invalid` and described
 * by it — rather than at the form; a change clears it. Without a form around it, it is a plain
 * control.
 *
 * Props: `modelValue` (the topic ids as strings), `containerId` (the content container whose
 * topics it offers), `attribute`, `label`, `placeholder`. Emits `update:modelValue`.
 */
export default {
    inject: {
        humhubForm: { from: 'humhubForm', default: null },
    },
    props: {
        modelValue: { type: Array, default: () => [] },
        containerId: { type: Number, required: true },
        attribute: { type: String, default: 'topics' },
        label: { type: String, default: '' },
        placeholder: { type: String, default: '' },
    },
    emits: ['update:modelValue'],
    data() {
        const id = `cfiles-item-${this.attribute}-${++uid}`;
        return { inputId: id, errorId: `${id}-error` };
    },
    computed: {
        /** What the control takes for a Topic filter's definition (`TopicFilter`). */
        filter() {
            return {
                key: this.attribute,
                type: 'topic',
                label: this.label,
                placeholder: this.placeholder,
                multiple: true,
                props: { containerId: this.containerId },
            };
        },
        errorMessages() {
            const messages = this.humhubForm ? this.humhubForm.errors.value[this.attribute] : null;
            return Array.isArray(messages) ? messages : [];
        },
        hasError() {
            return this.errorMessages.length > 0;
        },
    },
    watch: {
        // The control renders its own input: its state is set on it.
        hasError() {
            this.$nextTick(this.markInput);
        },
    },
    mounted() {
        this.humhubForm?.registerField(this.attribute, this);
        this.markInput();
    },
    beforeUnmount() {
        this.humhubForm?.unregisterField(this.attribute, this);
    },
    methods: {
        onInput(value) {
            this.humhubForm?.clearError(this.attribute);
            this.$emit('update:modelValue', value);
        },
        markInput() {
            const input = this.$el.querySelector(`#${this.inputId}`);
            if (!input) {
                return;
            }
            if (this.hasError) {
                input.setAttribute('aria-invalid', 'true');
                input.setAttribute('aria-describedby', this.errorId);
            } else {
                input.removeAttribute('aria-invalid');
                input.removeAttribute('aria-describedby');
            }
        },
        /** What `HumHubForm.focusFirstError()` calls on the field of the first error. */
        focus() {
            this.$el.querySelector(`#${this.inputId}`)?.focus();
        },
    },
};
</script>
