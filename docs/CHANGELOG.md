Changelog
=========

1.0.0 - Unreleased
------------------
- Enh: Rebuilt the file browser as a Vue.js island on the new core HTTP API (`/api/v2/cfiles`). The module no longer renders any of the browser server-side; folder navigation happens without a page load, and the URL (`?fid=`) is unchanged, so existing links keep working.
- Enh: The browser follows the design system v2 and is built on the core's item browser kit (humhub/humhub#8521): a page toolbar with the view switch, the settings link (for those who may change them) and one "add" menu, a filter bar with the sort order, and a card with the path bar and the items — tiles with a folder glyph, a document card per file type or a preview image, or a list. Uploads appear as tiles or rows with their progress.
- Enh: Added a tile display next to the list, remembered per user (`PATCH /api/v2/cfiles/preferences`). A tile grid asks the server for a bigger page than a row list.
- Enh: Selection works the same in tiles and list: Shift-click selects a range, Escape clears it, and a selection menu beside the path offers select all, move, delete and clear. Select all covers the items that are loaded, not everything in the folder — with paging, a checkbox that silently included rows nobody has seen would make the delete button far more dangerous than it looks.
- Enh: The whole row opens the item it shows, and a right-click anywhere on a row or a tile raises that item's context menu where the cursor is. Ctrl+right-click still gets the browser's own menu.
- Enh: The create and rename dialogs put the cursor in the title field as they open.
- Enh: Every row in the list view carries the platform's own like link (`LikeButton`), with the state for a whole page fetched in two grouped queries rather than one request per row. The module adds no like logic of its own.
- Enh: The "add" menu carries the file handlers a module contributed again ("new spreadsheet", "import from …"), after uploading files and creating a folder.
- Fix: Clicking a file reaches the handler a module contributed for it again — a viewer or an editor opens in the file dialog, and a file that only has the download handler stays a direct link. The list rendered a plain link to the file in every case.
- Fix: Uploading did not work at all. The request was built with a hand-rolled XMLHttpRequest reading a CSRF token from a meta tag that HumHub does not render, so every upload was rejected. It goes through the platform client now, which is what attaches the token.
- Fix: A tile's context menu was clipped by the tile's own overflow.
- Enh: Added drag & drop — files from the desktop upload into the open folder, or into a folder or path segment they are dropped on; items dragged onto a folder or a path segment move there, a selection as a whole.
- Enh: A folder listing is now paginated. Large folders no longer render every single row.
- Enh: The row context menu is the new core `ContentControls` island. Modules contributing entries through `WallEntryControls::EVENT_INIT` keep working unchanged; see the core migration guide.
- Enh: The models hold persistence and content integration only. Everything that was behaviour around a record — the folder tree and its root, creating items, moving them, name-collision rules, recursive visibility, the download counter, the integrity check — moved into `services/`. `models/` went from 3.440 to 1.050 lines; the module's server-side code from 9.508 to 4.998.
- Enh: Dropped the per-container root folder. Top level is `parent_folder_id IS NULL` again, which is what the schema always allowed — the root was a Content record nobody could see, whose creator being deleted took the container's whole tree with it. With it go the `cfiles_folder.type` column, three event handlers and a service. A migration detaches the children and removes the records.
- Enh: The API addresses a level of the tree as a container plus an optional `parent` folder (`GET /api/v2/cfiles/<containerId>/items?parent=<id>`), because the top level has no folder record a single id could name.
- Enh: The listing is built on the core list model (humhub/humhub#8523, `components\FolderList`): its sort is one key (`sort=name|nameDesc|newest|oldest|largest|smallest|default`), unknown parameters and a folder that is not readable in the container are answered with `422`, and modules can add filters on `FolderList::EVENT_INIT` or restrict it on `EVENT_BUILD`. The page's filter bar gets its definitions from the same list. File payloads carry the Tabler icon of their type as `icon`.
- Enh: The filter bar searches (`q`: folder titles, file names, descriptions), filters by author (`userId`, who created a folder or file — the core person filter type, humhub/humhub#8527, not offered to guests), by file type (`type`: images, documents, spreadsheets, presentations, audio & video) and by the last change (`modified`: 7 days, 30 days, 12 months, older). With a filter set the browser lists the hits in the open folder and all its subfolders (the whole space at the top level), each with a link to the folder it lies in (`resultsMode` and a `path` per item in the listing). Filters and sort are mirrored into the page URL, so a search can be shared, and the page renders the URL's filters without a further request. "Newest"/"Oldest" sort by the same last change the rows show and `modified` filters by.
- Fix: Desktop files dropped on a folder row were swallowed without uploading.
- Fix: The name-collision check for uploads and new folders looked at the top level of every space and profile, so an item was renamed although nothing in its own container had that name.
- Enh: The stream's Edit control links into the file browser instead of loading an edit form of its own. One edit form in the module rather than two render paths.
- Enh: Requires HumHub 1.20.
- Enh: Switched the icons from Font Awesome 4 to Tabler Icons (humhub/humhub#8504).
- Removed: "Files from the stream". Files attached to posts and comments are no longer listed in the files module; they are unaffected otherwise, and a migration deletes the marker folder.
- Removed: ZIP import and export, including the "Disable archive (ZIP) support" setting.
- Removed: The file version history UI. Versioning itself stays a core file-module feature.
- Removed: The `/api/v1` endpoints of this module, superseded by `/api/v2/cfiles`.
- Removed: The Custom Pages template elements (File, Files, Folder, Folders).
- Enh #288: Send a single notification to announce all files uploaded within a short period of time, rather than sending one notification per file (humhub/humhub#5334)
- Enh: Topics in the file browser: a Topic filter (the topics of the space and the global ones), and a Topics field in the edit dialog of files and folders (`topics` of `PATCH /api/v2/cfiles/file|folder/<id>`). Rows name their topics (humhub/humhub#8530).
- Fix: Saving a file (rename, visibility, move) no longer drops its topics.
- Enh: Optional global files page (`/files`, admin setting "Add entry to main navigation", off by default): one folder per member space with the module enabled, opening into that space's browser (Files › Space › Folder). Filtered at the top level, it lists the hits across all those spaces with their location "in Space › Folder" (`GET /api/v2/cfiles/items`, filters Search, Space, Author, Topic, File Type, Modified; the core filter types `space` and `topic`, humhub/humhub#8530).

0.18.3 - July 22, 2026
----------------------
- Fix #287: Fix PHP error from deprecated search event/constant removed in HumHub 1.19

0.18.2 - July 20, 2026
----------------------
- Fix #272: Fix Content linking with Comment

0.18.1 - July 8, 2026
---------------------
- Enh #286: Add aria-label attribute for icon-only buttons

0.18.0 - June 5, 2026
---------------------
- Enh #272: Update for HumHub 1.19

0.17.4 - May 5, 2026
--------------------
- Fix #278: Improved download count accuracy
- Fix #282: Fix virtual root folder ownership and recovery

0.17.3 - March 2, 2026
----------------------
- Fix #274: Fix asset bundle
- Fix #275: Labels encoding (see [migration guide](https://github.com/humhub/humhub/blob/master/MIGRATE-DEV.md#version-1181))

0.17.2 - November 24, 2025
---------------------------
- Enh #264: Update download and upload actions
- Fix #267: Include only accessible folders and files in a downloading zip archive
- Enh #266: Convert swagger docs to OpenAPI 3.0

0.17.1 - November 5, 2025
---------------------------
- Enh #255: Improved Module Test GitHub Actions
- Fix #256: Missing icon in Context Menu (File handler) and wrong styling in files context menu
- Fix #257: Dropdown styling
- Enh #259: Implemented module-coding-standards
- Fix #261: "Add Files" button hover issue

0.17.0 - August 29, 2025
------------------------
- Fix #220: Update module resources path
- Enh #242: Migration to Bootstrap 5 for HumHub 1.18

0.16.11 - November 24, 2025
---------------------------
- Enh #264: Update download and upload actions
- Fix #267: Include only accessible folders and files in a downloading zip archive

0.16.10 - August 1, 2025
-------------------------
Warning: This release contains two [security fixes](https://github.com/humhub/cfiles/security/advisories), and an update is strongly recommended.

- Enh #252: Refactor files sort ordering

0.16.9 - July 8, 2025
---------------------
- Enh #250: Use content ID in the "Custom Pages" extension

0.16.8 - June 30, 2025
-----------------------
- Enh #248: Extension for module "Custom Pages"

0.16.7 - June 10, 2025
----------------------
- Fix #216: Fix image space rending on wall entry
- Enh #224: Unifying positions of button on modals for consistency and better UX
- Enh #227: Use PHP CS Fixer
- Fix: Add autofocus on file or folder edit (for HumHub 1.17 - see https://github.com/humhub/humhub/issues/7136)
- Fix #230: Optimize sql query to get files from the stream
- Enh #232: Update Active Form for Bootstrap 5
- Enh #234: Increase File description max characters from 255 to 1000
- Enh #236: Reduce translation message categories
- Enh #239: Removing Topics from Edit File modal
- Fix #244: Fix invalid translation category
- Enh #246: Mobile View Decluttering

0.16.6 - March 14, 2024
-------------------------
- Fix #215: Space configuration checks permissions insufficiently

0.16.5 - March 5, 2024
-------------------------
- Fix #210: Fix enabling of module on Space with default private content
- Fix #213: Fix download url for browser caching

0.16.4 - February 8, 2024
-------------------------
- Fix #201: Fix replaced method `friendship\Module::isEnabled()`
- Fix #203: Refresh name of a downloading renamed file
- Fix #195: Allow to edit and delete own files

0.16.3 - November 16, 2023
---------------------------
- Enh #194: Tests for `next` version
- Fix #197: Fix visibility of the method `Controller::getAccessRules()`
- Fix #198: Fix memory usage on integrity check

0.16.2 - September 4, 2023
---------------------------
- Enh #178: Use new content state service
- Fix #180: Use icon `fa-unlock` for public files
- Fix #184: Display only published content files in the folder "Files from the stream"
- Fix #186: Rename conflicted not published folder/file on creating/uploading
- Fix #189: Initialize module content class
- Fix #190: Fix folder visibility in private space
- Fix #191: Avoid UnknownPropertyException in validation error response

0.16.1 - May 1, 2023
--------------------
- Fix #177: Hard delete records on disable module

0.16.0 - April 27, 2023
-----------------------
- Enh: Added support for hidden files in stream
- Enh #173: Soft deletion of nested content and restore parent folders on restore a child file/folder

0.15.1 - February 14, 2023
--------------------------
- Enh #168: Fix cropped folder/file names

0.15.0 - January 24, 2023
-------------------------
- Enh #157: Remove deprecated checkbox "regular" style
- Fix #162: Don't show last updating user in creator column

0.14.3 - Unreleased
--------------------
- Enh #152: Improve File listing layout

0.14.2 - December 15, 2021
--------------------------
- Fix #149: Fix error on context menu for files from stream

0.14.1 - December 7, 2021
-------------------------
- Fix #146: Update content last editor and date after save base File
- Enh #127: Improve context menu with items from wall stream entry

0.14.0 - November 26, 2021
--------------------------
- Enh #83: Enable paste/upload files from clipboard
- Enh #100: Add context menu on hover
- Enh #121: File versioning

0.13.2 - Unreleased
-----------------------
- Enh #84: Move files to different Space
- Enh #48: Use RichText for file description
- Enh #103: Allow to edit topics from the file edit form
- Enh #82: Move files and folders by drag & drop
- Enh #5274: Deprecate CompatModuleManager
- Enh #133: Factorize duplicated code
- Enh #140: Use widget ContentVisibiltySelect

0.13.1 - July 29, 2021
-----------------------
- Enh #114: Fix for PHP8 - Deprecate required parameters after optional parameters
- Fix #117: CLI error when no REST module is installed
- Fix: Race condition on newly created files (import vs. oo)
- Enh: Updated translations

0.13.0 - April 9, 2021
----------------------
- Enh #4751: Hide separator between content links
- Enh #4670: Enable default permissions
- Enh #45: Create root and "posted files" folders on insert container(Space/User) with enable module
- Enh #111: Support RESTful API module

0.12.1 - November 9, 2020
---------------------------
- Fix #97: Don’t affect an update date and user on download counter action

0.12.0 - November 4, 2020
--------------------------
- Enh #93: Wall Stream Layout Migration for HumHub 1.7+

0.11.20 - November 4, 2020
---------------------------
- Fix #87: ZIP Upload broken due legency ImageConverter usage
- Fix #74: Remove Temp Directory recursively in cleanup()
- Enh #94: Implement Download Counter

0.11.18 - December 4, 2019
---------------------------
- Fix: Social acitivites for virtual (Files from stream and root) folders

0.11.18 - December 4, 2019
---------------------------
- Fix: Social acitivites for virtual (Files from stream and root) folders

0.11.17 - June 27, 2019
---------------------------
- Enh: Updated translations
- Enh: Updated docs

0.11.16 - October 10, 2018
---------------------------
- Fix: Imported file visibility private

0.11.15 - October 2, 2018
---------------------------
- Fix: Imported file visibility private instead of public

0.11.14 - September 18, 2018
---------------------------
- Fix: getSearchAttributes() on items without editor or creator fails

0.11.13 - July 26, 2018
---------------------------
- Fix: Edit/Delete of own files without ManageFiles permission not working

0.11.12 - July 2, 2018
---------------------------
- Fix: PHP 7.2 compatibility issues

0.11.11 - April 27, 2018
---------------------------
- Fix: Profile files can't be managed

0.11.10 - April 25, 2018
---------------------------
- Fix: Yii 2.0.14 compatibility (https://github.com/yiisoft/yii2/issues/15875)

0.11.9 - December 20, 2017
---------------------------
- Enh: Updated translations

0.11.7 - December 12, 2017
---------------------------
- Enh: Added FolderView sort
- Enh: Default sorting configuration
- Enh: Remember user sort settings

0.11.6 - October 27, 2017
---------------------------
- Enh: Added upload behaviour settings (Index/Replace) in module config

0.11.5 - October 22, 2017
---------------------------
- Fix: Temporary files deletion on ZIP creation

0.11.4 - October 13, 2017
---------------------------
- Fix: Missing WallEntry layout for search results
- Enh: Updated translations

0.11.3 - September 22, 2017
---------------------------
- Fix: Fixed mixed permissions check

0.11.0 - September 4, 2017
---------------------------
- Enh: Editable folder/file visibility
- Enh: Guest support
- Enh: Added back button in sub folders
- Enh: Better move and upload logic (merge folders and file name index)
- Enh: Use of file name counter for already existing files instead of overwrite
- Enh: Context menu icons
- Enh: Rename files
- Fix: Zip support not working
- Enh: Posted files pagination
- Enh: Posted files/root folder translatable
- Enh: Added show URL context item
- Enh: Use of foreign keys
