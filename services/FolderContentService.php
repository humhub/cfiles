<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\cfiles\services;

use humhub\modules\file\libs\ImageHelper;
use humhub\modules\cfiles\models\File;
use humhub\modules\cfiles\models\FileSystemItem;
use humhub\modules\cfiles\models\Folder;
use humhub\modules\file\libs\FileHelper;
use humhub\modules\file\models\FileContent;
use yii\db\ActiveQuery;
use yii\imagine\Image;
use yii\web\UploadedFile;

/**
 * Everything that happens INSIDE one folder: creating items in it, listing them, and the
 * name-collision rules that govern both.
 *
 * Kept off the model deliberately. A `Folder` record is a title, a description and a parent
 * — "what happens when you drop a second `report.pdf` in here" is behaviour around that
 * record, not part of it, and it is the part that grows.
 *
 * @since 1.0
 */
class FolderContentService
{
    public function __construct(private Folder $folder)
    {
    }

    // --- listing ---------------------------------------------------------------------

    /**
     * The readable subfolders of this folder, as a query — the listing paginates it, the
     * visibility cascade walks it, so neither builds its own.
     */
    public function subFolderQuery(array $order = ['title' => SORT_ASC]): ActiveQuery
    {
        return FolderTreeService::subFolderQuery($this->folder, $order);
    }

    /**
     * The readable files of this folder, as a query.
     */
    public function subFileQuery(array $order = ['file.file_name' => SORT_ASC]): ActiveQuery
    {
        return File::find()
            ->joinWith('baseFile')
            ->contentContainer($this->folder->content->container)
            ->readable()
            ->andWhere(['cfiles_file.parent_folder_id' => $this->folder->id])
            ->orderBy($order);
    }

    /**
     * @return Folder[]
     */
    public function subFolders(array $order = ['title' => SORT_ASC]): array
    {
        return $this->subFolderQuery($order)->all();
    }

    /**
     * @return File[]
     */
    public function subFiles(array $order = ['file.file_name' => SORT_ASC]): array
    {
        return $this->subFileQuery($order)->all();
    }

    /**
     * @return FileSystemItem[]
     */
    public function children(): array
    {
        return array_merge($this->subFolders(), $this->subFiles());
    }

    // --- creating --------------------------------------------------------------------

    /**
     * A new, unsaved folder inside this one.
     */
    public function newFolder(?string $title = null, ?string $description = null): Folder
    {
        return new Folder($this->folder->content->container, $this->newItemVisibility(), [
            'parent_folder_id' => $this->folder->id,
            'title' => $title,
            'description' => $description,
        ]);
    }

    /**
     * Stores an uploaded file in this folder.
     *
     * Uploading over an existing name is an UPDATE, not a duplicate: the platform's file
     * versioning keeps the previous content. The exception is an unpublished record with that
     * name (a draft, an aborted import), which is renamed out of the way instead.
     *
     * The caller checks `$file->hasErrors()` and `$file->baseFile->hasErrors()`.
     */
    public function addUploadedFile(UploadedFile $uploadedFile): File
    {
        $file = $this->fileInstance($uploadedFile);

        if ($file->setUploadedFile($uploadedFile)) {
            $file->save();
        }

        return $file;
    }

    /**
     * Stores a file that already exists on disk (imports, tests, other modules).
     */
    public function addFileFromPath(string $fileName, string $path): File
    {
        $file = new File($this->folder->content->container, $this->newItemVisibility(), [
            'parent_folder_id' => $this->folder->id,
        ]);

        $fileContent = new FileContent([
            'mime_type' => FileHelper::getMimeType($path),
            'size' => filesize($path),
            'show_in_stream' => 0,
            'file_name' => $this->uniqueFileName($fileName),
        ]);

        if ($fileContent->mime_type === 'image/jpeg') {
            ImageHelper::fixJpegOrientation(Image::getImagine()->open($path), $path);
        }

        $fileContent->newFileContent = stream_get_contents(fopen($path, 'r'));

        $file->setFileContent($fileContent);
        $file->save();

        return $file;
    }

    /**
     * What a new item in this folder is visible to.
     *
     * Everything inherits the folder it lands in, so a private folder cannot hold public
     * files. The root has no folder above it and falls back to the container's own default.
     */
    public function newItemVisibility(): int
    {
        return $this->folder->isRoot()
            ? $this->folder->content->container->getDefaultContentVisibility()
            : (int)$this->folder->content->visibility;
    }

    // --- names -----------------------------------------------------------------------

    public function findFile(string $name): ?File
    {
        return File::find()
            ->contentContainer($this->folder->content->container)
            ->joinWith('baseFile')
            ->andWhere(['file_name' => $name])
            ->andWhere(['cfiles_file.parent_folder_id' => $this->folder->id])
            ->one();
    }

    public function findFolder(string $name): ?Folder
    {
        return Folder::find()
            ->contentContainer($this->folder->content->container)
            ->andWhere(['title' => $name, 'parent_folder_id' => $this->folder->id])
            ->one();
    }

    public function fileExists(string $name): bool
    {
        return File::find()
            ->joinWith('baseFile')
            ->where(['file_name' => $name, 'cfiles_file.parent_folder_id' => $this->folder->id])
            ->exists();
    }

    public function folderExists(string $name): bool
    {
        return Folder::find()
            ->where(['title' => $name, 'parent_folder_id' => $this->folder->id])
            ->exists();
    }

    /**
     * The given name, or the first indexed variant of it that is free — `report.pdf`,
     * `report(1).pdf`, `report(2).pdf`.
     */
    public function uniqueFileName(string $fileName): string
    {
        $parts = preg_split('~\.(?=[^\.]*$)~', $fileName);
        $stem = $parts[0];
        $extension = count($parts) === 2 ? '.' . $parts[1] : '';

        for ($counter = 0; $this->fileExists($fileName); $fileName = $stem . '(' . ++$counter . ')' . $extension) {
        }

        return $fileName;
    }

    /**
     * The record an upload of this name should write to.
     */
    private function fileInstance(UploadedFile $uploadedFile): File
    {
        $existing = $this->findFile($uploadedFile->name);

        if ($existing !== null) {
            if ($existing->content->getStateService()->isPublished()) {
                return $existing;
            }

            ItemMoveService::renameConflicted($existing);
        }

        return new File($this->folder->content->container, $this->newItemVisibility(), [
            'parent_folder_id' => $this->folder->id,
        ]);
    }
}
