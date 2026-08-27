<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\cfiles\services;

use humhub\modules\cfiles\models\File;
use humhub\modules\cfiles\models\FileSystemItem;
use humhub\modules\cfiles\models\Folder;
use humhub\modules\content\models\Content;

/**
 * Applies a requested visibility to an item.
 *
 * Visibility in a file tree is not a per-record flag: making a folder private has to take
 * everything inside it private too, or the folder would hide a public file that the stream
 * still shows. That recursion is why this is a service — the models only hold the REQUEST
 * (`$item->visibility`) and hand it here from their save hooks.
 *
 * @since 1.0
 */
class ItemVisibilityService
{
    /**
     * @param int|null $visibility null means "unchanged", which is what a write that does not
     *        mention visibility sends.
     */
    public static function apply(FileSystemItem $item, ?int $visibility): void
    {
        if ($visibility === null) {
            return;
        }

        if ($item instanceof Folder) {
            self::applyToFolder($item, $visibility);
            return;
        }

        if ($item instanceof File) {
            self::applyToFile($item, $visibility);
        }
    }

    private static function applyToFolder(Folder $folder, int $visibility): void
    {
        $folder->content->visibility = $visibility;

        $content = new FolderContentService($folder);

        foreach ($content->subFiles() as $file) {
            $file->content->visibility = $visibility;
            $file->content->save();
        }

        foreach ($content->subFolders() as $subFolder) {
            self::applyToFolder($subFolder, $visibility);
            $subFolder->content->save();
        }
    }

    private static function applyToFile(File $file, int $visibility): void
    {
        // A file cannot be more visible than the folder holding it. Going private is always
        // allowed; going public only inside a public folder.
        if ($file->parentFolder === null
            || !$file->parentFolder->content->isPrivate()
            || $visibility === Content::VISIBILITY_PRIVATE) {
            $file->content->visibility = $visibility;
        }
    }
}
