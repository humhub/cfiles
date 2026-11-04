import { describe, expect, it } from 'vitest';
import { flushPromises, mount } from '@vue/test-utils';
import DetailsPanel from '../../vue/browser/DetailsPanel.vue';
import { fileRow, folderRow } from './support/fixtures.mjs';

// The tabs' islands are the core's own (comment, activity and file module); stood in for here, as
// what they are given is what this panel is responsible for.
const stubs = {
    CommentSection: { props: ['contentId'], template: '<div class="comment-section-stub"></div>' },
    ContentActivity: { props: ['contentId'], template: '<div class="content-activity-stub"></div>' },
    FileVersions: { props: ['fileId'], template: '<div class="file-versions-stub"></div>' },
    ExtensionSlot: true,
};

const panel = (item = fileRow(), over = {}) => mount(DetailsPanel, {
    props: { item, ...over },
    global: { stubs },
});

const openTab = async (wrapper, id) => {
    await wrapper.find(`.card-tabs [data-tab="${id}"]`).trigger('click');
    await flushPromises();
};

describe('DetailsPanel', () => {
    it('opens on Info, with the three tabs as secondary pills', () => {
        const wrapper = panel();

        expect(wrapper.findAll('.nav-pills-secondary .nav-link').map((tab) => tab.text())).toEqual(['Info', 'Activity', 'Discussion']);
        expect(wrapper.find('.nav-link.active').text()).toBe('Info');
        expect(wrapper.find('.cfiles-details-facts').exists()).toBe(true);
    });

    it('opens on the tab it is asked to, and reports a change', async () => {
        const wrapper = panel(fileRow(), { initialTab: 'activity' });
        expect(wrapper.find('.nav-link.active').text()).toBe('Activity');

        await openTab(wrapper, 'info');
        expect(wrapper.emitted('tab').at(-1)).toEqual(['info']);
    });

    it('shows a file\'s versions on Activity, then the rest of its history', async () => {
        const wrapper = panel();
        await openTab(wrapper, 'activity');

        expect(wrapper.findComponent(stubs.FileVersions).props('fileId')).toBe(501);
        expect(wrapper.findComponent(stubs.ContentActivity).props('contentId')).toBe(201);
        expect(wrapper.findAll('h3').map((h) => h.text())).toEqual(['Versions', 'Activity']);
        expect(wrapper.find('.cfiles-details-facts').exists()).toBe(false);
    });

    it('has no versions for a folder', async () => {
        const wrapper = panel(folderRow());
        await openTab(wrapper, 'activity');

        expect(wrapper.findComponent(stubs.FileVersions).exists()).toBe(false);
        expect(wrapper.findComponent(stubs.ContentActivity).exists()).toBe(true);
    });

    it('offers Download on Info only', async () => {
        const wrapper = panel();
        expect(wrapper.find('.card-footer a.btn-primary').exists()).toBe(true);

        for (const tab of ['activity', 'discussion']) {
            await openTab(wrapper, tab);
            expect(wrapper.find('.card-footer').exists()).toBe(false);
        }
    });

    it('shows the item\'s thread on Discussion, below its description', async () => {
        const wrapper = panel(fileRow({ description: 'Line one\nLine two' }));
        expect(wrapper.findComponent(stubs.CommentSection).exists()).toBe(false);

        await openTab(wrapper, 'discussion');

        expect(wrapper.find('.cfiles-details-description').text()).toBe('Line one\nLine two');
        expect(wrapper.findComponent(stubs.CommentSection).props('contentId')).toBe(201);
    });

    it('offers the like link on Discussion where there is a like state', async () => {
        const liked = panel(fileRow(), { likeState: { total: 2, liked: true, canLike: true } });
        await openTab(liked, 'discussion');
        expect(liked.find('.cfiles-details-social').exists()).toBe(true);

        const none = panel();
        await openTab(none, 'discussion');
        expect(none.find('.cfiles-details-social').exists()).toBe(false);
    });

    it('offers Download for a file, not for a folder', () => {
        expect(panel().find('.card-footer a.btn-primary').attributes('href')).toBe('/s/x/cfiles/download/f-21');
        expect(panel(folderRow()).find('.card-footer').exists()).toBe(false);
    });
});
