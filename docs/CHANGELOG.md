Changelog
=========

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
