/**
 * The module's whole server surface, in one place.
 *
 * Every call goes to `/api/v2/cfiles` (see the module's `config.php`); nothing in the file
 * browser talks to a web controller.
 */
import { apiUrl, client } from '@humhub/vue';

/**
 * The request parameters of a listing: the filters with a value (an array, a filter with
 * several values, comma-separated), the sort, the page.
 */
const listParams = ({ sort, page, pageSize, filters = {} }, params = {}) => {
    Object.entries(filters).forEach(([key, value]) => {
        if (Array.isArray(value)) {
            if (value.length) {
                params[key] = value.join(',');
            }
        } else if (value !== '' && value !== null && value !== undefined) {
            params[key] = value;
        }
    });
    if (sort) {
        params.sort = sort;
    }
    if (page) {
        params.page = page;
    }
    if (pageSize) {
        params.pageSize = pageSize;
    }

    return params;
};

/**
 * One level of a container's tree. `parent` is a folder id, or null for the top level, which
 * has no folder record of its own — that is why the container is what the URL addresses.
 *
 * `sort` is a key of the list's sort select (`default` = the module's order, forgetting the
 * one the user chose last; left out = the one the user chose last), `pageSize` the page the
 * caller's view shows (see `FolderList` and `FolderListingService::VIEWS`). `filters` are the
 * values of the list's filters by key (`q`, `userId`, `topicId`, `type`, `modified`); an empty one is not sent —
 * with any of them set, the list searches the folder and all its subfolders. The endpoint
 * answers `422` for a parameter it does not know: send only its own filters.
 */
export const loadItems = (containerId, parent, options = {}) =>
    client.get(apiUrl('cfiles/' + containerId + '/items', listParams(options, parent ? { parent } : {})));

/**
 * The top level of the global files page (`GET cfiles/items`, `GlobalFolderList`): a tile per
 * space of the user, or — with a result filter — the hits across them. Takes what `loadItems()`
 * takes but the container and the parent; its filters add `spaceId`.
 */
export const loadGlobalItems = (options = {}) => client.get(apiUrl('cfiles/items', listParams(options)));

/**
 * Which of the topic ids a container takes — its own topics and the global ones: the topics
 * `GET topic/picker` names of them for that container (the rule of its Topic filter). One
 * request, at most the 20 ids the picker resolves.
 */
export const resolveTopics = (containerId, ids) =>
    client.get(apiUrl('topic/picker', { ids: ids.join(','), containerId, pageSize: ids.length }))
        .then((response) => (response.results || []).map((topic) => String(topic.id)));

/** Remembers the caller's view (`tiles` or `list`); answers the stored preferences. */
export const savePreferences = ({ view }) =>
    client.patch(apiUrl('cfiles/preferences'), { data: { view } });

export const createFolder = (containerId, parent, attributes) =>
    client.post(apiUrl('cfiles/' + containerId + '/folders'), {
        data: { ...attributes, parent },
    });

export const updateItem = (item, attributes) =>
    client.patch(apiUrl('cfiles/' + item.type + '/' + item.id), { data: attributes });

export const moveItems = (containerId, items, targetFolderId) =>
    client.post(apiUrl('cfiles/items/move'), {
        data: { containerId, items: items.map(descriptor), targetFolderId },
    });

export const deleteItems = (items) =>
    client.post(apiUrl('cfiles/items/delete'), { data: { items: items.map(descriptor) } });

/**
 * Uploads a batch of files to one level of the tree.
 *
 * Goes through the platform client rather than a hand-rolled XMLHttpRequest, which is what
 * makes it work at all: Yii's ajax prefilter is what attaches the CSRF token, and a
 * session-authenticated POST without one is rejected by `SessionAuth`. There is no
 * `csrf-token` meta tag on a HumHub page to read it from — only the installer layout renders
 * one. Modelled on the core's own `vue/upload/uploadClient.js`.
 *
 * The custom `xhr` factory exists for one reason: upload progress is an XHR-level event
 * jQuery does not surface. Everything else — CSRF, error handling, Response wrapping — stays
 * with the platform.
 */
export const uploadFiles = (containerId, parent, files, onProgress) => {
    const form = new FormData();

    Array.prototype.forEach.call(files, (file) => form.append('files[]', file));

    if (parent) {
        form.append('parent', parent);
    }

    return client.post(apiUrl('cfiles/' + containerId + '/files'), {
        data: form,
        // Hand the FormData to the browser untouched: jQuery must neither serialize it nor
        // set a Content-Type, or the multipart boundary is lost.
        processData: false,
        contentType: false,
        dataType: 'json',
        xhr: () => {
            const xhr = jQuery.ajaxSettings.xhr();

            if (onProgress && xhr.upload) {
                xhr.upload.addEventListener('progress', (event) => {
                    if (event.lengthComputable && event.total > 0) {
                        onProgress(Math.round((event.loaded / event.total) * 100));
                    }
                });
            }

            return xhr;
        },
    });
};

const descriptor = (item) => ({ type: item.type, id: item.id });


/** Stable identity of a row across reloads — a file and a folder can share a numeric id. */
export const keyOf = (item) => item.type + ':' + item.id;
