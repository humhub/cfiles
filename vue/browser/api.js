/**
 * The module's whole server surface, in one place.
 *
 * Every call goes to `/api/v2/cfiles` (see the module's `config.php`); nothing in the file
 * browser talks to a web controller.
 */
import { apiUrl, client } from '@humhub/vue';

export const loadFolder = (folderId, { sort, order, page, pageSize } = {}) => {
    const params = {};
    if (sort) {
        params.sort = sort;
        params.order = order || 'asc';
    }
    if (page) {
        params.page = page;
    }
    if (pageSize) {
        params.pageSize = pageSize;
    }

    return client.get(apiUrl('cfiles/folder/' + folderId, params));
};

export const createFolder = (parentFolderId, attributes) =>
    client.post(apiUrl('cfiles/folder/' + parentFolderId + '/folders'), { data: attributes });

export const updateItem = (item, attributes) =>
    client.put(apiUrl('cfiles/' + item.type + '/' + item.id), { data: attributes });

export const moveItems = (items, targetFolderId) =>
    client.post(apiUrl('cfiles/items/move'), {
        data: { items: items.map(descriptor), targetFolderId },
    });

export const deleteItems = (items) =>
    client.post(apiUrl('cfiles/items/delete'), { data: { items: items.map(descriptor) } });

/**
 * Uploads through XMLHttpRequest rather than the client bridge, because the bridge has no
 * upload-progress signal and a file browser without a progress bar is a file browser people
 * think has hung.
 */
export const uploadFiles = (folderId, files, onProgress) => new Promise((resolve, reject) => {
    const form = new FormData();
    Array.prototype.forEach.call(files, (file) => form.append('files[]', file));

    const request = new XMLHttpRequest();
    request.open('POST', apiUrl('cfiles/folder/' + folderId + '/files'));
    request.setRequestHeader('X-CSRF-Token', csrfToken());
    request.setRequestHeader('Accept', 'application/json');

    request.upload.addEventListener('progress', (event) => {
        if (event.lengthComputable && typeof onProgress === 'function') {
            onProgress(Math.round((event.loaded / event.total) * 100));
        }
    });

    request.addEventListener('load', () => {
        let body = {};
        try {
            body = JSON.parse(request.responseText || '{}');
        } catch (e) {
            reject(new Error('Malformed upload response'));
            return;
        }
        // 422 with a `results` array is a partial success: some files landed. The caller
        // shows both halves, so it is not an error here.
        if (request.status >= 200 && request.status < 300) {
            resolve(body);
        } else if (request.status === 422 && Array.isArray(body.results)) {
            resolve(body);
        } else {
            reject(new Error(request.statusText || 'Upload failed'));
        }
    });
    request.addEventListener('error', () => reject(new Error('Upload failed')));

    request.send(form);
});

const descriptor = (item) => ({ type: item.type, id: item.id });

const csrfToken = () => {
    const meta = document.querySelector('meta[name="csrf-token"]');
    return meta ? meta.getAttribute('content') : '';
};

/** Stable identity of a row across reloads — a file and a folder can share a numeric id. */
export const keyOf = (item) => item.type + ':' + item.id;
