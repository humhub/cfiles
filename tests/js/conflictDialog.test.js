import { afterEach, describe, expect, it } from 'vitest';
import { flushPromises, mount } from '@vue/test-utils';
import ConflictDialog from '../../vue/browser/ConflictDialog.vue';

const mine = { name: 'Plan.pdf', type: 'file', id: 21, canReplace: true };
const theirs = { name: 'Budget.xlsx', type: 'file', id: 22, canReplace: false };
const folder = { name: 'Reports', type: 'folder', id: 11, canReplace: false };

/** UiModal teleports its dialog onto document.body: looked up there. */
const dialog = async (conflicts) => {
    const wrapper = mount(ConflictDialog, { props: { show: true, conflicts }, attachTo: document.body });
    await flushPromises();
    return wrapper;
};
const buttons = () => Array.from(document.body.querySelectorAll('.modal-footer button'));
const button = (label) => buttons().find((b) => b.textContent.trim() === label);

describe('ConflictDialog', () => {
    afterEach(() => {
        document.body.replaceChildren();
    });

    it('names every item whose name is taken', async () => {
        await dialog([mine, folder]);

        const names = Array.from(document.body.querySelectorAll('.cfiles-conflicts li')).map((li) => li.textContent);
        expect(names[0]).toContain('Plan.pdf');
        expect(names[1]).toContain('Reports');
    });

    it('answers which way to go from its buttons', async () => {
        const wrapper = await dialog([mine]);

        button('Replace').click();
        button('Keep both').click();
        button('Skip').click();

        expect(wrapper.emitted('resolve')).toEqual([['replace'], ['keep'], ['skip']]);
    });

    it('says which items cannot be replaced', async () => {
        await dialog([mine, theirs]);

        const items = Array.from(document.body.querySelectorAll('.cfiles-conflicts li'));
        expect(items[0].querySelector('.cfiles-conflict-note')).toBeNull();
        expect(items[1].querySelector('.cfiles-conflict-note')).not.toBeNull();
    });

    it('offers no Replace when none of the items can be replaced', async () => {
        await dialog([theirs, folder]);

        expect(button('Replace')).toBeUndefined();
        expect(button('Keep both').classList.contains('btn-primary')).toBe(true);
    });

    it('is dismissed as a cancel', async () => {
        const wrapper = await dialog([mine]);

        wrapper.findComponent({ name: 'UiModal' }).vm.$emit('update:show', false);

        expect(wrapper.emitted('cancel')).toHaveLength(1);
    });
});
