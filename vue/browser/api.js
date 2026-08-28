/**
 * The module's whole server surface, in one place.
 *
 * Every call goes to `/api/v2/cfiles` (see the module's `config.php`); nothing in the file
 * browser talks to a web controller.
 */
import { apiUrl, client } from '@humhub/vue';

/**
 * One level of a container's tree. `parent` is a folder id, or null for the top level, which
 * has no folder record of its own — that is why the container is what the URL addresses.
 */
export const loadItems = (containerId, parent, { sort, order, view, page, pageSize } = {}) => {
    const params = {};
    if (parent) {
        params.parent = parent;
    }
    if (sort) {
        params.sort = sort;
        params.order = order || 'asc';
    }
    if (view) {
        params.view = view;
    }
    if (page) {
        params.page = page;
    }
    if (pageSize) {
        params.pageSize = pageSize;
    }

    return client.get(apiUrl('cfiles/' + containerId + '/items', params));
};

export const createFolder = (containerId, parent, attributes) =>
    client.post(apiUrl('cfiles/' + containerId + '/folders'), {
        data: { ...attributes, parent },
    });

export const updateItem = (item, attributes) =>
    client.put(apiUrl('cfiles/' + item.type + '/' + item.id), { data: attributes });

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
