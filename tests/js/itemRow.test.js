import { beforeEach, describe, expect, it, vi } from 'vitest';
import { flushPromises, mount } from '@vue/test-utils';
import ItemRow from '../../vue/browser/ItemRow.vue';
import { fileRow, folderRow } from './support/fixtures.mjs';

const row = (item, over = {}) => mount(ItemRow, {
    props: {
        item,
        folderUrl: (id) => '/b?fid=' + (id || 0),
        entries: [],
        ...over,
    },
});

describe('ItemRow', () => {
    beforeEach(() => {
        globalThis.humhub.config.module('i18n').language = 'de-DE';
        globalThis.humhubStubs.client.get = vi.fn(() => Promise.resolve({
            entries: [],
            capabilities: { canEdit: true, canDelete: true, canMove: true },
        }));
    });

    describe('presentation', () => {
        it('links a folder to its own page and a file to the file itself', () => {
            expect(row(folderRow()).find('h4 a').attributes('href')).toBe('/b?fid=11');
            expect(row(fileRow()).find('h4 a').attributes('href')).toBe('/file/f-21');
        });

        it('shows a folder icon for a folder and a mime icon for a file', () => {
            expect(row(folderRow()).find('.cfiles-icon-folder').exists()).toBe(true);
            expect(row(fileRow()).find('.fa-file-pdf-o').exists()).toBe(true);
        });

        it('prefers a thumbnail over an icon when there is one', () => {
            const wrapper = row(fileRow({ previewUrl: '/preview/21.jpg' }));

            expect(wrapper.find('img.cfiles-thumb').attributes('src')).toBe('/preview/21.jpg');
            expect(wrapper.find('.cfiles-icon-file').exists()).toBe(false);
        });

        it('marks a private item and leaves a public one unmarked', () => {
            expect(row(fileRow({ visibility: 0 })).find('.fa-lock').exists()).toBe(true);
            expect(row(fileRow({ visibility: 1 })).find('.fa-lock').exists()).toBe(false);
        });

        it('counts a folder\'s items and sizes a file', () => {
            expect(row(folderRow({ itemCount: 4 })).find('.cfiles-row-meta').text()).toContain('4 items');
            expect(row(folderRow({ itemCount: 1 })).find('.cfiles-row-meta').text()).toContain('1 item');
            expect(row(folderRow({ itemCount: 0 })).find('.cfiles-row-meta').text()).toContain('empty');
            expect(row(fileRow()).find('.cfiles-row-meta').text()).toContain('1.2 MB');
        });

        it('appends the description when there is one', () => {
            const meta = row(fileRow({ description: 'Erste Fassung' })).find('.cfiles-row-meta').text();

            expect(meta).toContain('Erste Fassung');
        });

        // A recent change reads as "3 days ago", an older one as a date - the same split the
        // platform's TimeAgo widget makes - and both in the HumHub language, not the browser's.
        describe('timestamps', () => {
            it('gives a recent change a relative time', () => {
                const twoHoursAgo = new Date(Date.now() - 2 * 60 * 60 * 1000).toISOString();
                const meta = row(fileRow({ updatedAt: twoHoursAgo })).find('.cfiles-row-meta').text();

                expect(meta).toMatch(/Stunden|hours/);
            });

            it('gives an older change a date in the HumHub language, not the browser\'s', () => {
                const german = row(fileRow({ updatedAt: '2026-01-15T09:00:00+00:00' }))
                    .find('.cfiles-row-meta').text();

                expect(german).toContain('15.01.2026');

                globalThis.humhub.config.module('i18n').language = 'en-US';
                const english = row(fileRow({ updatedAt: '2026-01-15T09:00:00+00:00' }))
                    .find('.cfiles-row-meta').text();

                expect(english).toContain('Jan 15, 2026');
            });
        });
    });

    describe('selection', () => {
        it('offers a checkbox only where selection is possible', () => {
            expect(row(fileRow(), { selectable: true }).find('input[type="checkbox"]').exists()).toBe(true);
            expect(row(fileRow()).find('input[type="checkbox"]').exists()).toBe(false);
        });

        it('emits the item when its checkbox is toggled', async () => {
            const wrapper = row(fileRow(), { selectable: true });
            await wrapper.find('input[type="checkbox"]').setValue(true);

            expect(wrapper.emitted('toggle-select')[0][0].id).toBe(21);
        });
    });

    describe('context menu', () => {
        // Without this the row menu shows the whole stream-entry stack (pin, archive,
        // permalink) next to the file actions.
        it('tells the server not to send the core entries the row renders itself', async () => {
            const wrapper = row(fileRow());

            wrapper.find('a[data-bs-toggle="dropdown"]').element
                .dispatchEvent(new Event('show.bs.dropdown'));
            await flushPromises();

            expect(globalThis.humhubStubs.client.get.mock.calls[0][0])
                .toContain('suppress=edit%2Cdelete%2Cpermalink%2Cpin%2Cmove%2Carchive');
        });

        it('asks for the browser view context, not the stream one', async () => {
            const wrapper = row(fileRow());

            wrapper.find('a[data-bs-toggle="dropdown"]').element
                .dispatchEvent(new Event('show.bs.dropdown'));
            await flushPromises();

            expect(globalThis.humhubStubs.client.get.mock.calls[0][0]).toContain('viewContext=browser');
        });
    });

    describe('drag and drop', () => {
        it('offers a folder as a drop target and a file not', async () => {
            const folder = row(folderRow(), { draggable: true });
            const file = row(fileRow(), { draggable: true });
            const event = () => ({ preventDefault: vi.fn(), stopPropagation: vi.fn(), dataTransfer: {} });

            const folderEvent = event();
            folder.vm.onDrop(folderEvent);
            expect(folder.emitted('drop-on')).toBeTruthy();

            file.vm.onDrop(event());
            expect(file.emitted('drop-on')).toBeFalsy();
        });
    });
});
