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
    recordId: 1101,
    title: 'Entwürfe',
    description: '',
    visibility: 1,
    parentFolderId: null,
    itemCount: 4,
    createdAt: '2026-08-20T09:00:00+00:00',
    updatedAt: '2026-08-25T09:00:00+00:00',
    creator: creator(),
    // `[{ id, name, color }]` (ItemTopicSerializer).
    topics: [],
    url: '/s/x/cfiles/browse/index?fid=11',
    // Where it lies relative to the open folder (FolderListingService): empty = directly in it.
    path: [],
    ...over,
});

export const fileRow = (over = {}) => ({
    type: 'file',
    id: 21,
    contentId: 201,
    recordId: 1201,
    guid: 'f-21',
    title: 'Angebot.pdf',
    description: '',
    visibility: 1,
    mimeType: 'application/pdf',
    mimeIcon: 'mime-pdf',
    icon: 'file-type-pdf',
    size: 1258291,
    url: '/file/f-21',
    downloadUrl: '/s/x/cfiles/download/f-21',
    // Only the download handler applies: a plain link with the platform's download hooks.
    link: {
        url: '/file/f-21',
        attributes: {
            target: '_blank',
            'data-file-download': true,
            'data-file-url': '/file/f-21?download=1',
            'data-file-name': 'Angebot.pdf',
            'data-file-mime': 'application/pdf',
        },
    },
    previewUrl: null,
    downloadCount: 0,
    parentFolderId: null,
    createdAt: '2026-08-20T09:00:00+00:00',
    updatedAt: '2026-08-25T09:00:00+00:00',
    creator: creator(),
    topics: [],
    path: [],
    ...over,
});

/**
 * A like state per row, the way the listing carries them: keyed by record id, NOT by the
 * content id the rest of the payload uses.
 */
export const likeStates = (results) => {
    const states = {};

    results.forEach((item) => {
        states[item.recordId] = { total: 0, liked: false, canLike: true };
    });

    return states;
};

/** A listing of the container's top level, which has no folder record of its own. */
export const topLevel = (results = [folderRow(), fileRow()], over = {}) => ({
    folder: null,
    path: [],
    // The sort key the list was built with; `default` = none chosen (see FolderList).
    sort: 'default',
    view: 'list',
    results,
    // The hits of the filters in the folder and its subfolders rather than the level.
    resultsMode: false,
    // The payload's per-caller section (see FolderListingService::payload()).
    likeStates: likeStates(results),
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
    // The sort key the list was built with; `default` = none chosen (see FolderList).
    sort: 'default',
    view: 'list',
    results,
    // The hits of the filters in the folder and its subfolders rather than the level.
    resultsMode: false,
    // The payload's per-caller section (see FolderListingService::payload()).
    likeStates: likeStates(results),
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
    // What FolderList::definitions() renders (FilterDefinition::toArray()).
    filters: [
        { key: 'q', type: 'text', label: 'Search', placeholder: 'Search', placement: 'primary' },
        { key: 'userId', type: 'user', label: 'Author', placement: 'primary' },
        // The container's topics and the global ones (FolderList::topicFilter()).
        { key: 'topicId', type: 'topic', label: 'Topic', multiple: true, props: { containerId: CONTAINER_ID }, placement: 'primary' },
        {
            key: 'type',
            type: 'select',
            label: 'File Type',
            placeholder: 'File Type',
            options: [
                { value: 'image', label: 'Images' },
                { value: 'document', label: 'Documents' },
                { value: 'spreadsheet', label: 'Spreadsheets' },
                { value: 'presentation', label: 'Presentations' },
                { value: 'media', label: 'Audio & Video' },
            ],
            placement: 'primary',
        },
        {
            key: 'modified',
            type: 'select',
            label: 'Modified',
            placeholder: 'Modified',
            options: [
                { value: '7d', label: 'Last 7 days' },
                { value: '30d', label: 'Last 30 days' },
                { value: '12m', label: 'Last 12 months' },
                { value: 'older', label: 'Older' },
            ],
            placement: 'primary',
        },
        {
            key: 'sort',
            type: 'select',
            label: 'Sort by',
            options: [
                { value: 'name', label: 'Name (A–Z)' },
                { value: 'nameDesc', label: 'Name (Z–A)' },
                { value: 'newest', label: 'Newest first' },
                { value: 'oldest', label: 'Oldest first' },
                { value: 'largest', label: 'Largest first' },
                { value: 'smallest', label: 'Smallest first' },
            ],
            placement: 'primary',
        },
    ],
    // The filter values the page was built with (BrowseController::firstListing()).
    initialFilters: { q: '', userId: '', topicId: '', type: '', modified: '' },
    settingsUrl: null,
    ...over,
});

/** A result list: the hits of the filters in the folder and its subfolders. */
export const results = (listing, over = {}) => ({ ...listing, resultsMode: true, ...over });

// --- the global files page ---------------------------------------------------------------

/** A tile of the global files page's top level (GlobalListingService::tiles()). */
export const spaceTile = (over = {}) => ({
    type: 'space',
    id: 3,
    guid: 's-3',
    name: 'Marketing',
    url: '/s/marketing/',
    color: '#6fdbe8',
    imageUrl: null,
    contentContainerId: 7,
    browseUrl: '/s/marketing/cfiles/browse/index',
    itemCount: 4,
    ...over,
});

export const salesTile = () => spaceTile({
    id: 4, guid: 's-4', name: 'Sales', url: '/s/sales/', imageUrl: '/uploads/sales.jpg', contentContainerId: 8, itemCount: 1,
});

/** The first entry of a path over several spaces (FolderListBuilder::$prefixContainer). */
export const spaceEntry = (over = {}) => ({ type: 'space', id: 3, contentContainerId: 7, guid: 's-3', title: 'Marketing', ...over });

/** The space a page inside a space hands the island (`space` prop). */
export const openSpace = (over = {}) => ({
    id: 3, contentContainerId: 7, guid: 's-3', name: 'Marketing', color: '#6fdbe8', imageUrl: null, ...over,
});

/** The global top level: one tile per space (GlobalListingService::payload()). */
export const globalTop = (results = [spaceTile(), salesTile()], over = {}) => ({
    global: true,
    space: null,
    canWrite: false,
    ...topLevel(results, { likeStates: {}, ...over }),
});

/** The hits across all spaces, each path starting with its space. */
export const globalResults = (results, over = {}) => globalTop(results, { resultsMode: true, ...over });

/** A level of a space, from the container endpoint (FolderListingService::payload()). */
export const spaceLevel = (results = [folderRow(), fileRow()], over = {}) => topLevel(results, { canWrite: true, ...over });

/** What GlobalFolderList::definitions() renders: the container's filters plus Space and Topic. */
export const globalFilters = () => {
    const [q, userId, , type, modified, sort] = browserProps(null).filters;

    return [
        q,
        { key: 'spaceId', type: 'space', label: 'Space', placeholder: 'Space', multiple: true, props: { scope: 'member' }, placement: 'primary' },
        userId,
        { key: 'topicId', type: 'topic', label: 'Topic', placeholder: 'Topic', multiple: true, placement: 'primary' },
        type,
        modified,
        sort,
    ];
};

/**
 * What GlobalController hands the island as a space's definitions: the container's filters for
 * no space in particular — its Topic without a container, which the island sets.
 */
export const containerFilters = () => browserProps(null).filters.map((filter) => {
    if (filter.type !== 'topic') {
        return filter;
    }
    const { props, ...unscoped } = filter;
    return unscoped;
});

export const globalProps = (listing, over = {}) => ({
    listing,
    global: true,
    globalUrl: '/files',
    space: null,
    contentContainerId: null,
    filters: globalFilters(),
    containerFilters: containerFilters(),
    initialFilters: { q: '', spaceId: '', userId: '', topicId: '', type: '', modified: '' },
    settingsUrl: null,
    ...over,
});
