<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2026 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\cfiles\services;

use humhub\modules\cfiles\models\File;
use humhub\modules\cfiles\models\FileSystemItem;
use humhub\modules\cfiles\models\Folder;
use humhub\modules\cfiles\permissions\WriteAccess;
use humhub\modules\content\components\ContentContainerActiveRecord;
use humhub\modules\file\components\StorageManager;
use humhub\modules\file\models\File as BaseFile;
use humhub\modules\topic\models\Topic;
use League\Flysystem\Visibility;
use Throwable;
use Yii;

/**
 * Copying files and folders, within a container or into another one.
 *
 * A copy is new content of the caller, as if they had uploaded it: their authorship, the
 * visibility and the stream setting a new item at the target gets, and a free name there —
 * a copy next to its original is `plan(1).txt`, never a merge. What it takes along is what
 * describes the item: its name, its description, its topics (those that exist at the target)
 * and its stored content, copied in the storage rather than read into memory. Its history
 * stays behind: likes, comments, earlier versions.
 *
 * A folder is copied with everything inside it the caller may read — what they see of it in
 * the browser. A subitem that cannot be copied is skipped and reported on the folder, the
 * rest is copied: like a file manager, a copy of a tree is not all or nothing.
 *
 * @since 1.0
 */
class ItemCopyService
{
    /**
     * Copies an item into a folder of a container, or to its top level when `$target` is null.
     *
     * The caller needs to be able to read the item — that is up to whoever found it — and to
     * add files to the container. Failures are reported on the ITEM (`$item->getErrors()`),
     * keyed by its title, as for a move ({@see ItemMoveService::moveInto()}).
     *
     * @return FileSystemItem|null the copy; null when the item itself could not be copied
     */
    public static function copyInto(ContentContainerActiveRecord $container, ?Folder $target, FileSystemItem $item): ?FileSystemItem
    {
        if (!$container->permissionManager->can(WriteAccess::class)) {
            $item->addError($item->getTitle(), Yii::t('CfilesModule.base', 'You cannot add files here.'));

            return null;
        }

        if ($item instanceof Folder && self::isWithin($target, $item)) {
            $item->addError($item->getTitle(), Yii::t('CfilesModule.base', 'Folder {name} can\'t be copied into itself!', ['name' => $item->getTitle()]));

            return null;
        }

        if ($item instanceof File) {
            return self::copyFile($container, $target, $item);
        }

        if ($item instanceof Folder) {
            return self::copyFolder($container, $target, $item);
        }

        return null;
    }

    private static function copyFile(ContentContainerActiveRecord $container, ?Folder $target, File $file): ?File
    {
        $source = $file->baseFile;

        if ($source === null || !$source->store->has()) {
            $file->addError($file->getTitle(), Yii::t('CfilesModule.base', 'The file "{name}" could not be copied.', ['name' => $file->getTitle()]));

            return null;
        }

        $level = new FolderContentService($container, $target);
        $copy = new File($container, $level->newItemVisibility(), [
            'parent_folder_id' => $target?->id,
            'description' => $file->description,
        ]);
        $copy->setFileContent(new BaseFile([
            'file_name' => $level->uniqueFileName($file->getTitle()),
            'title' => $source->title,
            'mime_type' => $source->mime_type,
            'size' => $source->size,
            'hash_sha1' => $source->hash_sha1,
            'show_in_stream' => false,
        ]));

        // The records and the stored content belong together: no copy without its content.
        $transaction = Yii::$app->db->beginTransaction();

        try {
            if (!$copy->save()) {
                $transaction->rollBack();
                self::report($file, $copy);

                return null;
            }

            self::copyStoredContent($source, $copy->baseFile);
            $transaction->commit();
        } catch (Throwable $e) {
            $transaction->rollBack();
            Yii::error('Could not copy the file ' . $file->id . ': ' . $e->getMessage(), 'cfiles');
            $file->addError($file->getTitle(), Yii::t('CfilesModule.base', 'The file "{name}" could not be copied.', ['name' => $file->getTitle()]));

            return null;
        }

        self::copyTopics($file, $copy, $container);

        return $copy;
    }

    private static function copyFolder(ContentContainerActiveRecord $container, ?Folder $target, Folder $folder): ?Folder
    {
        $level = new FolderContentService($container, $target);
        $copy = $level->newFolder($level->uniqueFolderName($folder->title), $folder->description);

        if (!$copy->save()) {
            self::report($folder, $copy);

            return null;
        }

        self::copyTopics($folder, $copy, $container);

        foreach ((new FolderContentService($folder->content->container, $folder))->children() as $child) {
            self::copyInto($container, $copy, $child);

            // Also those of a subfolder that was copied, but not with everything inside it.
            foreach ($child->getErrors() as $errors) {
                $folder->addErrors([$child->getTitle() => $errors]);
            }
        }

        return $copy;
    }

    /**
     * Whether `$target` is `$folder` or lies somewhere inside it.
     */
    private static function isWithin(?Folder $target, Folder $folder): bool
    {
        $seen = [];

        for ($current = $target; $current !== null && !isset($seen[$current->id]); $current = $current->parentFolder) {
            if ((int)$current->id === (int)$folder->id) {
                return true;
            }
            $seen[$current->id] = true;
        }

        return false;
    }

    /**
     * Copies the stored content in the storage itself, so even a large file is never read
     * into memory — through the data mount both stores write to, as the core's own storage
     * manager does; any other storage manager gets the content handed over.
     */
    private static function copyStoredContent(BaseFile $from, BaseFile $to): void
    {
        $source = $from->store;
        $destination = $to->store;

        if ($source instanceof StorageManager && $destination instanceof StorageManager) {
            $destination->fs->copy($source->get(), $destination->get(), [
                'visibility' => Visibility::PRIVATE,
                'directory_visibility' => Visibility::PRIVATE,
            ]);

            return;
        }

        $destination->setContent($source->getContent());
    }

    /**
     * The item's topics that exist at the target: the global ones, and those of its container.
     */
    private static function copyTopics(FileSystemItem $item, FileSystemItem $copy, ContentContainerActiveRecord $container): void
    {
        $topics = array_filter(
            Topic::findByContent($item->content)->all(),
            static fn(Topic $topic) => $topic->contentcontainer_id === null
                || (int)$topic->contentcontainer_id === (int)$container->contentcontainer_id,
        );

        if ($topics !== []) {
            Topic::attach($copy->content, array_values($topics));
        }
    }

    private static function report(FileSystemItem $item, FileSystemItem $copy): void
    {
        $errors = $copy->getFirstErrors();
        if ($copy instanceof File && $copy->baseFile !== null) {
            $errors = array_merge($errors, $copy->baseFile->getFirstErrors());
        }

        $item->addError($item->getTitle(), $errors === []
            ? Yii::t('CfilesModule.base', 'The item "{name}" could not be copied.', ['name' => $item->getTitle()])
            : implode(' ', $errors));
    }
}
