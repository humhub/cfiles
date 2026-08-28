import { beforeEach, describe, expect, it, vi } from 'vitest';
import { flushPromises, mount } from '@vue/test-utils';
import ItemRow from '../../vue/browser/ItemRow.vue';
import ContentControls from '@core/modules/content/vue/ContentControls.vue';
import { fileRow, folderRow } from './support/fixtures.mjs';

const row = (item, over = {}) => mount(ItemRow, {
    props: {
        item,
        folderUrl: (id) => '/b?fid=' + (id || 0),
        entries: [],
        ...over,
    },
});

/**
 * A real right-click. `wrapper.trigger()` cannot be used here: the assertions are about the
 * pointer coordinates and about whether the native menu was suppressed, and both live on the
 * event object itself.
 */
const rightClick = (wrapper, init = {}) => {
    const event = new MouseEvent('contextmenu', {
        bubbles: true, cancelable: true, clientX: 40, clientY: 90, ...init,
    });
    wrapper.element.dispatchEvent(event);

    return event;
};

/** Watches the row's context menu without letting it reach Bootstrap, which is not loaded here. */
const watchMenu = (wrapper) => vi.spyOn(wrapper.findComponent(ContentControls).vm, 'open')
    .mockImplementation(() => {});

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

        // A file is not always just a download: a module may have contributed a viewer or an
        // editor, and the server says which of the two link shapes applies.
        describe('what the name links to', () => {
            it('carries the download hooks when only the download handler applies', () => {
                const link = row(fileRow()).find('h4 a');

                expect(link.attributes('href')).toBe('/file/f-21');
                expect(link.attributes('target')).toBe('_blank');
                expect(link.attributes('data-file-name')).toBe('Angebot.pdf');
            });

            it('opens the file dialog when a module contributed a handler', () => {
                const link = row(fileRow({
                    link: { url: '/file/view?guid=f-21', attributes: { 'data-bs-target': '#globalModal' } },
                })).find('h4 a');

                expect(link.attributes('href')).toBe('/file/view?guid=f-21');
                expect(link.attributes('data-bs-target')).toBe('#globalModal');
                expect(link.attributes('target')).toBeUndefined();
            });

            it('falls back to the plain file url when the payload carries no decision', () => {
                const link = row(fileRow({ link: undefined })).find('h4 a');

                expect(link.attributes('href')).toBe('/file/f-21');
            });

            it('never applies any of it to a folder', () => {
                const link = row(folderRow()).find('h4 a');

                expect(link.attributes('href')).toBe('/b?fid=11');
                expect(link.attributes('data-bs-target')).toBeUndefined();
            });
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

        it('raises the menu at the cursor on a right-click anywhere on the row', () => {
            const wrapper = row(fileRow());
            const open = watchMenu(wrapper);

            const event = rightClick(wrapper);

            expect(open).toHaveBeenCalledTimes(1);
            expect(open.mock.calls[0][0].clientX).toBe(40);
            expect(open.mock.calls[0][0].clientY).toBe(90);
            expect(event.defaultPrevented).toBe(true);
        });

        it('leaves ctrl+right-click to the browser, as the platform always has', () => {
            const wrapper = row(fileRow());
            const open = watchMenu(wrapper);

            const event = rightClick(wrapper, { ctrlKey: true });

            expect(open).not.toHaveBeenCalled();
            expect(event.defaultPrevented).toBe(false);
        });

        it('leaves a right-click inside the open menu to the menu', () => {
            const wrapper = row(fileRow());
            const open = watchMenu(wrapper);

            wrapper.find('.dropdown-menu').element.dispatchEvent(
                new MouseEvent('contextmenu', { bubbles: true, cancelable: true }),
            );

            expect(open).not.toHaveBeenCalled();
        });
    });

    /**
     * The row itself is the click target for the item it shows — a browser where only the
     * name is clickable makes every open a precision exercise.
     */
    describe('opening by clicking the row', () => {
        it('opens a folder', async () => {
            const wrapper = row(folderRow());

            await wrapper.trigger('click');

            expect(wrapper.emitted('open')[0][0].id).toBe(11);
        });

        it('follows a file through its own link, whatever the server made of it', async () => {
            const wrapper = row(fileRow({
                link: { url: '/file/view?guid=f-21', attributes: { 'data-bs-target': '#globalModal' } },
            }));
            const link = wrapper.find('h4 a').element;
            const clicks = vi.fn((event) => event.preventDefault());
            link.addEventListener('click', clicks);

            await wrapper.trigger('click');

            // Through the anchor, not around it: its attributes are what the platform's own
            // delegated handlers read to decide what opening this file means.
            expect(clicks).toHaveBeenCalledTimes(1);
            expect(wrapper.emitted('open')).toBeFalsy();
        });

        it('leaves the checkbox, the menu and the creator link their own clicks', async () => {
            const wrapper = row(folderRow(), { selectable: true });

            await wrapper.find('input[type="checkbox"]').trigger('click');
            await wrapper.find('a[data-bs-toggle="dropdown"]').trigger('click');

            expect(wrapper.emitted('open')).toBeFalsy();
        });

        it('leaves a modifier click to the browser', async () => {
            const wrapper = row(folderRow());

            await wrapper.trigger('click', { metaKey: true });
            await wrapper.trigger('click', { ctrlKey: true });
            await wrapper.trigger('click', { shiftKey: true });

            expect(wrapper.emitted('open')).toBeFalsy();
        });

        it('does not open when the click only ended a text selection', async () => {
            const wrapper = row(folderRow());
            vi.spyOn(window, 'getSelection').mockReturnValue({
                isCollapsed: false,
                anchorNode: wrapper.find('h5').element,
            });

            await wrapper.trigger('click');

            expect(wrapper.emitted('open')).toBeFalsy();
            window.getSelection.mockRestore();
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
