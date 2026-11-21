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
        // Nothing is more visible than the folder holding it: going private is always allowed,
        // going public only inside a public folder (or at the top level). Checked here, for
        // the item asked about — inside the cascade the parent is the folder being changed.
        if ($visibility === null
            || ($visibility !== Content::VISIBILITY_PRIVATE && $item->parentFolder?->content->isPrivate())) {
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

        foreach (self::reached($folder, $visibility) as $item) {
            if ($item instanceof Folder) {
                self::applyToFolder($item, $visibility);
            } else {
                $item->content->visibility = $visibility;
            }
            $item->content->save();
        }
    }

    /**
     * What a change of the folder reaches inside it. Going private reaches everything, whatever
     * its state and whoever may see it: nothing may stay more visible than the folder holding
     * it, also not the draft of someone else, published later, or an item in the trash,
     * restored later. Going public reaches what the user making the change can see — what they
     * cannot see, they cannot decide to publish.
     *
     * @return FileSystemItem[]
     */
    private static function reached(Folder $folder, int $visibility): array
    {
        if ($visibility === Content::VISIBILITY_PRIVATE) {
            return ItemMoveService::subItems($folder);
        }

        return (new FolderContentService($folder->content->container, $folder))->children();
    }

    private static function applyToFile(File $file, int $visibility): void
    {
        $file->content->visibility = $visibility;
    }
}
