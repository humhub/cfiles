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
use humhub\modules\content\components\ContentContainerActiveRecord;
use Yii;

/**
 * Moving files and folders around the tree.
 *
 * The rules are all about what happens when two things collide, which is why this is a
 * service and not a model method: a file that lands on an existing name is indexed
 * (`report(1).pdf`), a folder that lands on an existing name is MERGED into it, item by item
 * and recursively, and either can fail halfway. None of that is knowledge a folder record
 * carries about itself.
 *
 * @since 1.0
 */
class ItemMoveService
{
    /**
     * Moves an item into a folder, or to the container's top level when `$target` is null.
     *
     * Failures are reported on the ITEM (`$item->getErrors()`), keyed by its title, because a
     * caller moving a whole selection needs to know which one refused.
     */
    public static function moveInto(ContentContainerActiveRecord $container, ?Folder $target, FileSystemItem $item): bool
    {
        if (!$item->canManage()) {
            return self::refuse($item, $item instanceof File
                ? Yii::t('CfilesModule.base', 'You cannot move the file "{name}"!', ['name' => $item->getTitle()])
                : Yii::t('CfilesModule.base', 'You cannot move the folder "{name}"!', ['name' => $item->getTitle()]));
        }

        if ($target !== null && $item instanceof Folder && (int)$item->id === (int)$target->id) {
            return self::refuse($item, Yii::t('CfilesModule.base', 'Folder {name} can\'t be moved to itself!', ['name' => $item->getTitle()]));
        }

        // Already there — a no-op, not a failure.
        if ((int)$item->parent_folder_id === (int)$target?->id) {
            return true;
        }

        // Set the requested visibility rather than the content's directly, so a folder runs
        // its recursive visibility change on save (see ItemVisibilityService).
        $item->visibility = $target === null
            ? $container->getDefaultContentVisibility()
            : (int)$target->content->visibility;
        $item->parent_folder_id = $target?->id;

        $resolved = self::resolveCollision($container, $target, $item);

        if ($resolved === null) {
            // A subitem failed on its way into an existing folder of the same name; the errors
            // are already on $item.
            return false;
        }

        // Either nothing collided, or a file was renamed out of the way — in both cases
        // resolveCollision() handed back the item itself, and that is what gets saved. A
        // folder merged into an existing one has nothing left to save; its children moved and
        // it was deleted.
        return $resolved === $item ? $item->save() : true;
    }

    /**
     * Renames an item out of the way of a name it is blocking.
     */
    public static function renameConflicted(FileSystemItem $item): bool
    {
        if ($item->isNewRecord) {
            return false;
        }

        if ($item instanceof File) {
            $item->baseFile->file_name = 'conflict' . $item->baseFile->id . '-' . $item->baseFile->file_name;

            return $item->baseFile->save();
        }

        if (!$item instanceof Folder) {
            return false;
        }

        $item->title = 'conflict' . $item->id . '-' . $item->title;

        return $item->save();
    }

    /**
     * Follows a folder into another content container, taking its whole subtree with it.
     *
     * Content is moved between containers one record at a time by the platform, and a folder
     * that arrived somewhere new while its children stayed behind would be a tree split
     * across two spaces.
     */
    public static function moveSubItemsToContainer(Folder $folder, ?ContentContainerActiveRecord $container = null): void
    {
        $container = $container ?? $folder->content->getContainer();

        /** @var FileSystemItem[] $subFolders */
        $subFolders = Folder::find()->andWhere(['parent_folder_id' => $folder->id])->all();

        /** @var FileSystemItem[] $subFiles */
        $subFiles = File::find()
            ->joinWith('baseFile')
            ->andWhere(['cfiles_file.parent_folder_id' => $folder->id])
            ->all();

        foreach ($subFolders as $subFolder) {
            $subFolder->move($container);
        }

        foreach ($subFiles as $subFile) {
            $subFile->move($container);
        }
    }

    /**
     * Resolves a name collision at the target level.
     *
     * @return FileSystemItem|null the item that should be saved, or null when merging a
     *         folder into an existing one failed partway
     */
    private static function resolveCollision(ContentContainerActiveRecord $container, ?Folder $target, FileSystemItem $item): ?FileSystemItem
    {
        $content = new FolderContentService($container, $target);

        if ($item instanceof File) {
            // A duplicate file name is indexed rather than refused.
            $item->setTitle($content->uniqueFileName($item->getTitle()));

            return $item;
        }

        if (!$item instanceof Folder) {
            return $item;
        }

        $existing = $content->findFolder($item->title);

        if ($existing === null || (int)$existing->id === (int)$item->id) {
            return $item;
        }

        // Same name as an existing folder: merge into it rather than creating a second one.
        $failed = false;

        foreach ((new FolderContentService($container, $item))->children() as $child) {
            if (self::moveInto($container, $existing, $child)) {
                continue;
            }

            $failed = true;

            foreach ($child->getErrors() as $errors) {
                $item->addErrors([$child->getTitle() => $errors]);
            }
        }

        if ($failed) {
            return null;
        }

        $item->delete();

        return $existing;
    }

    private static function refuse(FileSystemItem $item, string $message): bool
    {
        $item->addError($item->getTitle(), $message);

        return false;
    }
}
