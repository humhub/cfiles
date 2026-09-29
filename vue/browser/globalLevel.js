/**
 * The levels of the file browser, as the global files page (`/files`) adds them: which
 * container a level is of, its URL, what a URL names and which filters a level's endpoint
 * knows. Pure functions, so `CfilesFileBrowser` keeps only thin methods around them.
 *
 * A container state is `{ contentContainerId, space }` (`space` on the global page only,
 * `{ id, contentContainerId, guid, name, color, imageUrl }`); `null` is the global page's top
 * level.
 */

/**
 * The id of the space's crumb in the global page's path bar — the space's top level. A string,
 * so no folder id can be it.
 */
export const SPACE_CRUMB = 'space';

/** Whether a filter value is set: `''`, `null` and `[]` (a filter with several values) are not. */
export const isSet = (value) => (Array.isArray(value) ? value.length > 0 : value !== '' && value !== null && value !== undefined);

/** A value as it goes into a URL: several values comma-separated. */
export const urlValue = (value) => (Array.isArray(value) ? value.join(',') : String(value));

/**
 * The filter values with a filter of several values (`multiple`, the Space filter) as an array —
 * how the `FilterBar` holds them — also where the page or a URL gave them comma-separated.
 */
export const normalizeQuery = (values, definitions) => Object.fromEntries(Object.entries(values).map(([key, value]) => {
    const multiple = definitions.some((filter) => filter.key === key && filter.multiple === true);
    return [key, multiple && !Array.isArray(value) ? String(value ?? '').split(',').filter(Boolean) : value];
}));

/**
 * The container state of a space: from a tile (`name`), a path entry (`title`) or the page's
 * `space` prop.
 */
export const containerOf = (space) => ({
    contentContainerId: space.contentContainerId,
    space: {
        id: space.id,
        contentContainerId: space.contentContainerId,
        guid: space.guid ?? null,
        name: space.name ?? space.title ?? '',
        color: space.color ?? null,
        imageUrl: space.imageUrl ?? null,
    },
});

/** Whether two container states are the same container (`null` = the global top level). */
export const sameContainer = (a, b) => (a?.contentContainerId ?? null) === (b?.contentContainerId ?? null);

/**
 * What the tile grid keys a level by, to slide the next one in: the folder id — on the global
 * page with the container, since folder ids of two spaces are not the same level.
 */
export const levelKey = (global, container, folder) => {
    const folderId = folder ? folder.id : 0;
    return global ? (container ? container.contentContainerId + ':' : '') + folderId : folderId;
};

/** Adds the filter values that are set to URL parameters. */
export const appendFilters = (params, values) => {
    Object.entries(values).forEach(([key, value]) => {
        if (isSet(value)) {
            params.set(key, urlValue(value));
        }
    });
    return params;
};

/**
 * The global page's URL of a level: `?space=<space id>&fid=<folder id>` — neither at its top
 * level, no `fid` at a space's — and the filter values that are set.
 */
export const levelUrl = (globalUrl, space, folderId, values) => {
    const params = new URLSearchParams();
    if (space) {
        params.set('space', String(space.id));
        if (folderId) {
            params.set('fid', String(folderId));
        }
    }
    const query = appendFilters(params, values).toString();
    return globalUrl + (query ? (globalUrl.indexOf('?') === -1 ? '?' : '&') + query : '');
};

/** The space a URL's query names (`space`), null for none. */
export const spaceIdFromUrl = (search) => parseInt(new URLSearchParams(search).get('space'), 10) || null;

/**
 * The container state of a space a URL names, from what the island knows of it: the containers
 * it has (`known`: shown, opening), the history entry (`state`), the items shown — a tile, or
 * the first entry of a hit's path. `null` when it knows nothing of it.
 */
export const resolveSpace = (spaceId, { known = [], state = null, items = [] } = {}) => {
    const container = known.find((candidate) => candidate?.space?.id === spaceId);
    if (container) {
        return container;
    }
    const stored = state?.cfiles?.space;
    if (stored && stored.id === spaceId) {
        return containerOf(stored);
    }
    for (const item of items) {
        if (item.type === 'space' && item.id === spaceId) {
            return containerOf(item);
        }
        const entry = item.path?.[0];
        if (entry && entry.type === 'space' && entry.id === spaceId) {
            return containerOf(entry);
        }
    }
    return null;
};

/** The topic filter's key, whose values a space narrows to its own (`scopeTopics()`). */
export const TOPIC_KEY = 'topicId';

/** The ids of a filter value of several ids, as strings. */
export const idsOf = (value) => (Array.isArray(value) ? value : String(value ?? '').split(',')).map(String).filter(Boolean);

/**
 * The ids of `value` a container's topic scope (`{ [id]: true|false }`, what the picker said of
 * each for that container) takes — its own topics and the global ones. An id it has not said
 * anything of yet is not taken.
 */
export const scopedIds = (value, scope) => idsOf(value).filter((id) => scope?.[id] === true);

/**
 * The value `chosen` inside a container makes of the whole value `all`: the chosen ids, then
 * those of `all` the container does not take (other spaces' topics), kept for the way back.
 */
export const mergeScoped = (chosen, all, scope) => {
    const ids = idsOf(chosen);
    const merged = [...ids, ...idsOf(all).filter((id) => scope?.[id] !== true && !ids.includes(id))];
    const before = idsOf(all);
    // The same ids: the value as it was, so nothing reads as a change.
    return merged.length === before.length && merged.every((id) => before.includes(id)) ? before : merged;
};

/**
 * The filter values a level's endpoint knows: those of its definitions (the endpoints answer
 * `422` for a parameter they do not know) — all of them without definitions.
 */
export const levelFilters = (query, definitions) => {
    if (!definitions) {
        return query;
    }
    const keys = definitions.map((filter) => filter.key);
    return Object.fromEntries(Object.entries(query).filter(([key]) => keys.includes(key)));
};

/**
 * Whether a location is the page of a URL: the same path, and every parameter the URL carries
 * (`r`, `cguid` without pretty URLs) the same. A history entry of another page is none of the
 * island's business.
 */
export const isPageUrl = (url, location) => {
    const page = new URL(url, location.origin);
    if (page.pathname !== location.pathname) {
        return false;
    }
    const current = new URLSearchParams(location.search);
    return Array.from(page.searchParams.entries()).every(([key, value]) => current.get(key) === value);
};

/** The parameters that say which page a URL is without pretty URLs: the route and the container. */
const PAGE_PARAMS = ['r', 'cguid'];

/**
 * The page a location is, as `isPageLocation()` compares it: its path, and the route and
 * container parameters it carries. The island keeps the one it was opened at — a menu links the
 * page by a URL of its own (`/s/x/cfiles/browse`, `r=cfiles/browse`, `/cfiles/global`), not by
 * the one the island pushes.
 */
export const pageOf = (location) => {
    const params = new URLSearchParams(location.search);
    return {
        pathname: location.pathname,
        params: Object.fromEntries(PAGE_PARAMS.filter((key) => params.has(key)).map((key) => [key, params.get(key)])),
    };
};

/** Whether a location is that page (`pageOf()`), whatever else its query says. */
export const isPageLocation = (page, location) => {
    if (!page || page.pathname !== location.pathname) {
        return false;
    }
    const current = new URLSearchParams(location.search);
    return Object.entries(page.params).every(([key, value]) => current.get(key) === value);
};
