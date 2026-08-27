/**
 * Payloads shaped exactly like the API's, so a test breaks when a serializer changes.
 *
 * @see \humhub\modules\cfiles\serializers\FolderSerializer
 * @see \humhub\modules\cfiles\serializers\FileSerializer
 */

export const CONTAINER_ID = 5;

export const creator = () => ({
    id: 1,
    guid: 'a1b2c3',
    displayName: 'Ada Lovelace',
    url: '/u/ada',
    imageUrl: '/uploads/profile_image/ada.jpg',
    contentContainerId: CONTAINER_ID,
});

export const folderRow = (over = {}) => ({
    type: 'folder',
    id: 11,
    contentId: 101,
    title: 'Entwürfe',
    description: '',
    visibility: 1,
    parentFolderId: null,
    itemCount: 4,
    createdAt: '2026-08-20T09:00:00+00:00',
    updatedAt: '2026-08-25T09:00:00+00:00',
    creator: creator(),
    url: '/s/x/cfiles/browse/index?fid=11',
    ...over,
});

export const fileRow = (over = {}) => ({
    type: 'file',
    id: 21,
    contentId: 201,
    guid: 'f-21',
    title: 'Angebot.pdf',
    description: '',
    visibility: 1,
    mimeType: 'application/pdf',
    mimeIcon: 'mime-pdf',
    size: 1258291,
    url: '/file/f-21',
    downloadUrl: '/s/x/cfiles/download/f-21',
    previewUrl: null,
    downloadCount: 0,
    parentFolderId: null,
    createdAt: '2026-08-20T09:00:00+00:00',
    updatedAt: '2026-08-25T09:00:00+00:00',
    creator: creator(),
    ...over,
});

/** A listing of the container's top level, which has no folder record of its own. */
export const topLevel = (results = [folderRow(), fileRow()], over = {}) => ({
    folder: null,
    path: [],
    sort: 'name',
    order: 'asc',
    results,
    total: results.length,
    page: 1,
    pageSize: 50,
    pages: 1,
    ...over,
});

/** A listing of one folder, two levels down. */
export const insideFolder = (results = [], over = {}) => ({
    folder: folderRow({ id: 9, title: 'sgadgasdg', parentFolderId: 7 }),
    path: [
        { id: 7, title: 'test123', url: '/s/x/cfiles/browse/index?fid=7' },
        { id: 9, title: 'sgadgasdg', url: '/s/x/cfiles/browse/index?fid=9' },
    ],
    sort: 'name',
    order: 'asc',
    results,
    total: results.length,
    page: 1,
    pageSize: 50,
    pages: 1,
    ...over,
});

export const browserProps = (listing, over = {}) => ({
    listing,
    canWrite: true,
    browseUrl: '/s/x/cfiles/browse/index',
    contentContainerId: CONTAINER_ID,
    ...over,
});
