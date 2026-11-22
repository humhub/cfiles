# Files

Enhance your network's efficiency with the Files Module, a complete solution for easy file management. Seamlessly integrated with HumHub, this module enables you to effortlessly manage and share important files throughout your network and team.

## Key Features

- **Folders:** Organize your files into folders and subfolders, as deep as you need.
- **Two Displays:** Switch between a compact list and a tile grid with previews; your choice is remembered.
- **Drag & Drop Upload:** Drop files from your desktop into the open folder or onto any folder. When a name is taken, you choose: replace the file as a new version, keep both, or skip it.
- **Move and Copy:** Move or copy files and folders through a folder dialog, or drag them where they belong.
- **Search and Filters:** Find files by name and description, and narrow them down by author, file type and topic.
- **Details:** See who created and changed a file and when, its versions and its history, and discuss it with comments and likes.
- **Topics and Visibility:** Tag files and folders with topics, and keep them private or share them publicly — a folder passes its visibility on to its content.
- **All Files at a Glance:** An optional page in the main navigation brings together the files of all your spaces.
- **Works with Other Modules:** Create documents with the file handlers of other modules, and open files in their viewers and editors.
- **WebDAV:** Connect the files of your spaces and your profile as a network drive in Finder, Windows Explorer or any WebDAV client (`/dav/files/`), signed in with a personal access token. Administrators can switch it off.
- **API:** Read and manage files through the HumHub API (`/api/v2/cfiles`), with the scopes `cfiles:read` and `cfiles:write`.

## Development

The file browser is a Vue.js island on the core's HTTP API v2. **Installing or running the
module needs nothing extra** — the compiled artifact in `resources/js/` is committed, and the
asset pipeline treats it as a plain JS file. Only editing `vue/` requires tooling.

### Building the island

From a HumHub core checkout:

```
node vue.build.mjs --module <path-to-this-module>
```

Commit the rebuilt `resources/js/humhub.cfiles.vue.js` and its source map along with the
source change; CI fails if they drift apart.

### Running the JS tests

The components are tested against the core they run in — `@humhub/vue`, the platform stubs and
the core components the island nests all come from a core checkout rather than a copy.

```
npm install
HUMHUB_CORE_PATH=<path-to-humhub-core> npm test
```

`HUMHUB_CORE_PATH` can be omitted when the module sits inside an installation
(`protected/modules/cfiles` or `modules/cfiles`), which is what CI does.

### Structure

| | |
|---|---|
| `vue/` | island sources; every top-level `.vue` is auto-registered under its filename |
| `controllers/api/`, `serializers/` | the module's only JSON surface, `/api/v2/cfiles` |
| `services/` | the domain behaviour — the models hold persistence and content integration only |
| `tests/js/` | vitest suite for the island |
