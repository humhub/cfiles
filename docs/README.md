# Files

Enhance your network's efficiency with the Files Module, a complete solution for easy file management. Seamlessly integrated with HumHub, this module enables you to effortlessly manage and share important files throughout your network and team.

## Key Features

- **File Overview:** Get instant access to all files from your stream and profile.
- **Interact:** Comment on and like files for better collaboration and feedback.
- **Folder Structure:** Organize your files into unlimited folders and subfolders.
- **Two Displays:** Switch between a compact list and a tile grid with previews; your choice is remembered.
- **Migration:** Effortlessly move files and folders within your network.
- **File Info:** Always visible information about the creator, editor, and creation date.
- **Import Files:** Import files and folder structures directly from a .zip file.
- **Export Files:** Download folders and files in bulk as a .zip file.

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
