import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { enableAutoUnmount, flushPromises, mount } from '@vue/test-utils';
import CfilesItemForm from '../../vue/CfilesItemForm.vue';
import { CONTAINER_ID, fileRow, folderRow } from './support/fixtures.mjs';

enableAutoUnmount(afterEach);

const budget = { id: 4, name: 'Budget', color: '#ff0000', container: { id: CONTAINER_ID, guid: 's-x', name: 'X' } };
const brand = { id: 6, name: 'Brand', color: null, container: null };

const form = (item, over = {}) => mount(CfilesItemForm, { props: { item, contentContainerId: CONTAINER_ID, ...over } });
const topics = (wrapper) => wrapper.findComponent({ name: 'TopicFilterControl' });
/** The attributes the last PATCH sent. */
const sent = () => globalThis.humhubStubs.client.ajax.mock.calls.at(-1)[1].data;

describe('CfilesItemForm topics', () => {
    beforeEach(() => {
        // The topic picker, resolving and searching.
        globalThis.humhubStubs.client.get = vi.fn(() => Promise.resolve({ results: [budget, brand], total: 2 }));
        globalThis.humhubStubs.client.ajax = vi.fn((url, cfg) => Promise.resolve({ ...fileRow(), ...cfg.data }));
    });

    it('shows the topics the item has, searching those of its container', async () => {
        const wrapper = form(fileRow({ topics: [{ id: 4, name: 'Budget', color: '#ff0000' }] }));
        await flushPromises();

        expect(topics(wrapper).props('modelValue')).toEqual(['4']);
        expect(topics(wrapper).props('filter').props.containerId).toBe(CONTAINER_ID);
        expect(wrapper.find('.c-picker__chip').text()).toContain('Budget');
    });

    it('sends the topics chosen with the other attributes', async () => {
        const wrapper = form(folderRow({ topics: [{ id: 4, name: 'Budget', color: '#ff0000' }] }));
        await flushPromises();

        topics(wrapper).vm.$emit('update:modelValue', ['4', '6']);
        await wrapper.find('form').trigger('submit');
        await flushPromises();

        expect(globalThis.humhubStubs.client.ajax.mock.calls.at(-1)[0]).toContain('cfiles/folder/11');
        expect(sent().topics).toEqual(['4', '6']);
        expect(sent().title).toBe('Entwürfe');
        expect(wrapper.emitted('saved')).toHaveLength(1);
    });

    // A form-encoded empty list is not sent at all: `''` is how the endpoint hears "none".
    it('sends none when the topics are cleared', async () => {
        const wrapper = form(fileRow({ topics: [{ id: 4, name: 'Budget', color: '#ff0000' }] }));
        await flushPromises();

        topics(wrapper).vm.$emit('update:modelValue', []);
        await wrapper.find('form').trigger('submit');
        await flushPromises();

        expect(sent().topics).toBe('');
    });

    it('shows a refused topic below the Topics field, not at the form', async () => {
        globalThis.humhubStubs.client.ajax = vi.fn(() => Promise.reject({ status: 422, errors: { topics: ['Topic not found.'] } }));
        const wrapper = form(fileRow());
        await flushPromises();

        await wrapper.find('form').trigger('submit');
        await flushPromises();

        const feedback = wrapper.find('.cfiles-item-form__topics .invalid-feedback.d-block');
        expect(feedback.text()).toBe('Topic not found.');
        expect(wrapper.text().split('Topic not found.')).toHaveLength(2);
        const input = wrapper.find('.cfiles-item-form__topics input[role="combobox"]');
        expect(input.attributes('aria-invalid')).toBe('true');
        expect(input.attributes('aria-describedby')).toBe(feedback.attributes('id'));

        // Changing the topics clears it.
        topics(wrapper).vm.$emit('update:modelValue', ['4']);
        await flushPromises();
        expect(wrapper.find('.cfiles-item-form__topics .invalid-feedback').exists()).toBe(false);
        expect(input.attributes('aria-invalid')).toBeUndefined();
    });

    it('has no topics when a folder is created', () => {
        const wrapper = form(null, { parentFolderId: null });

        expect(topics(wrapper).exists()).toBe(false);
    });
});
