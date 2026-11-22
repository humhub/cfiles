<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2026 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\cfiles\components\dav;

use humhub\modules\cfiles\models\File;
use humhub\modules\cfiles\models\FileSystemItem;
use humhub\modules\cfiles\models\Folder;
use humhub\modules\cfiles\permissions\WriteAccess;
use humhub\modules\cfiles\services\FolderContentService;
use humhub\modules\cfiles\services\ItemCopyService;
use humhub\modules\cfiles\services\ItemMoveService;
use humhub\modules\content\components\ContentContainerActiveRecord;
use humhub\modules\dav\components\UploadedBody;
use humhub\modules\file\models\File as BaseFile;
use Sabre\DAV\Collection;
use Sabre\DAV\Exception\Conflict;
use Sabre\DAV\Exception\Forbidden;
use Sabre\DAV\Exception\NotFound;
use Sabre\DAV\ICopyTarget;
use Sabre\DAV\IMoveTarget;
use Sabre\DAV\INode;
use Sabre\DAV\Server;
use Throwable;
use Yii;

/**
 * One level of a container's file tree as a WebDAV collection: the top level of a space or
 * profile (`$folder` null), or a folder.
 *
 * It holds what the file browser shows at that level ({@see FolderContentService}), and
 * changes it by the rules of the browser:
 *
 * - adding (`PUT`, `MKCOL`) needs the write permission of the container, and a new item gets
 *   the visibility of the level ({@see FolderContentService::newItemVisibility()});
 * - a file and a folder never share a name — Sabre answers a `PUT` on a folder and a `MKCOL`
 *   on a file itself;
 * - moving and copying go through {@see ItemMoveService} and {@see ItemCopyService}, so an item
 *   keeps its history on a move; only an item of another container moves as copy and delete
 *   (Sabre's fallback), as the browser cannot move across containers either;
 * - deleting moves an item to the trash, as in the browser.
 *
 * @since 1.0
 */
class LevelNode extends Collection implements IMoveTarget, ICopyTarget
{
    /**
     * @param string $name the name of the level: the folder's title, or the segment of the container
     */
    public function __construct(
        public readonly ContentContainerActiveRecord $container,
        public readonly ?Folder $folder,
        private readonly string $name,
    ) {
    }

    /**
     * @inheritdoc
     */
    public function getName()
    {
        return $this->folder?->title ?? $this->name;
    }

    /**
     * @inheritdoc
     */
    public function getLastModified()
    {
        $updated = $this->folder?->content->updated_at;

        return $updated ? (strtotime($updated) ?: null) : null;
    }

    /**
     * @inheritdoc
     */
    public function getChildren()
    {
        $level = $this->level();
        $children = [];

        foreach ($level->subFolders() as $folder) {
            $children[] = new self($this->container, $folder, $folder->title);
        }
        foreach ($level->subFiles() as $file) {
            $children[] = new FileNode($file);
        }

        return $children;
    }

    /**
     * @inheritdoc
     */
    public function getChild($name)
    {
        $level = $this->level();

        $folder = $level->subFolderQuery()->andWhere(['cfiles_folder.title' => $name])->one();
        if ($folder instanceof Folder) {
            return new self($this->container, $folder, $folder->title);
        }

        $file = $level->subFileQuery()->andWhere(['file.file_name' => $name])->one();
        if ($file instanceof File) {
            return new FileNode($file);
        }

        throw new NotFound('No such file or folder: ' . $name);
    }

    /**
     * @inheritdoc
     */
    public function childExists($name)
    {
        try {
            $this->getChild($name);
            return true;
        } catch (NotFound) {
            return false;
        }
    }

    /**
     * Stores the body as a new file of this level, checked like an upload ({@see UploadedBody}).
     *
     * @inheritdoc
     */
    public function createFile($name, $data = null)
    {
        $this->assertCanWrite();
        $level = $this->level();
        if ($level->nameTaken($name)) {
            throw new Conflict(Yii::t('CfilesModule.base', 'A file with that name already exists in this folder.'));
        }

        $body = UploadedBody::fromData($data, $name);

        $file = new File($this->container, $level->newItemVisibility(), ['parent_folder_id' => $this->folder?->id]);
        $file->setFileContent(new BaseFile([
            'file_name' => $name,
            'mime_type' => $body->getMimeType(),
            'size' => $body->getSize(),
            'show_in_stream' => false,
        ]));

        // The records and the stored content belong together: no file without its content.
        $transaction = Yii::$app->db->beginTransaction();
        try {
            $saved = $file->save();
            if ($saved) {
                $file->baseFile->setStoredFileByPath($body->path, true);
            }
        } catch (Throwable $e) {
            $transaction->rollBack();
            throw $e;
        }

        if (!$saved) {
            $transaction->rollBack();
            throw new Forbidden(self::errors($file));
        }
        $transaction->commit();

        return (new FileNode($file))->getETag();
    }

    /**
     * @inheritdoc
     */
    public function createDirectory($name)
    {
        $this->assertCanWrite();

        $folder = $this->level()->newFolder($name);
        if (!$folder->save()) {
            throw new Forbidden(self::errors($folder));
        }
    }

    /**
     * Moves the folder to the trash.
     *
     * @inheritdoc
     */
    public function delete()
    {
        $folder = $this->ownFolder();
        if (!$folder->content->canEdit() || !$folder->delete()) {
            throw new Forbidden(Yii::t('CfilesModule.base', 'Could not delete the item!'));
        }
    }

    /**
     * @inheritdoc
     */
    public function setName($name)
    {
        $folder = $this->ownFolder();
        if (!$folder->content->canEdit()) {
            throw new Forbidden();
        }

        $folder->title = $name;
        if (!$folder->save()) {
            throw new Forbidden(self::errors($folder));
        }
    }

    /**
     * An item of this container moves here as the browser moves it, under the name the
     * client asked for — that name is free, Sabre has removed an item of it before. An item
     * of another container is left to Sabre (copy, then delete).
     *
     * @inheritdoc
     */
    public function moveInto($targetName, $sourcePath, INode $sourceNode)
    {
        $item = self::itemOf($sourceNode);
        if ($item === null || (int)$item->content->contentcontainer_id !== (int)$this->container->contentcontainer_id) {
            return false;
        }

        $this->assertCanWrite();
        self::rename($item, $targetName);

        if (!ItemMoveService::moveInto($this->container, $this->folder, $item)) {
            throw new Forbidden(self::errors($item));
        }

        return true;
    }

    /**
     * A copy as the browser makes it ({@see ItemCopyService}), then named as the client asked.
     * `Depth: 0` copies a folder without its contents.
     *
     * @inheritdoc
     */
    public function copyInto($targetName, $sourcePath, INode $sourceNode, int $depth = Server::DEPTH_INFINITY)
    {
        $item = self::itemOf($sourceNode);
        if ($item === null) {
            return false;
        }

        if ($item instanceof Folder && $depth === 0) {
            $this->createDirectory($targetName);
            return true;
        }

        $copy = ItemCopyService::copyInto($this->container, $this->folder, $item);
        if ($copy === null) {
            throw new Forbidden(self::errors($item));
        }

        if ($copy->getTitle() !== $targetName) {
            self::rename($copy, $targetName);
            if (!$copy->save()) {
                throw new Forbidden(self::errors($copy));
            }
        }

        return true;
    }

    private function level(): FolderContentService
    {
        return new FolderContentService($this->container, $this->folder);
    }

    /**
     * @throws Forbidden for the top level of a container, which cannot be renamed, moved or deleted
     */
    private function ownFolder(): Folder
    {
        if ($this->folder === null) {
            throw new Forbidden();
        }

        return $this->folder;
    }

    /**
     * @throws Forbidden
     */
    private function assertCanWrite(): void
    {
        if (!$this->container->permissionManager->can(WriteAccess::class)) {
            throw new Forbidden(Yii::t('CfilesModule.base', 'You cannot add files here.'));
        }
    }

    /**
     * The item of a node of this module; null for anything else.
     *
     * @throws Forbidden for the top level of a container
     */
    private static function itemOf(INode $node): ?FileSystemItem
    {
        if ($node instanceof FileNode) {
            return $node->file;
        }

        if ($node instanceof self) {
            return $node->ownFolder();
        }

        return null;
    }

    /**
     * Gives an item a new name, not saved yet.
     *
     * @throws Forbidden for a file name the platform does not take
     */
    private static function rename(FileSystemItem $item, string $name): void
    {
        if ($item instanceof File) {
            UploadedBody::assertValidName($name);
            $item->setTitle($name);
        } elseif ($item instanceof Folder) {
            $item->title = $name;
        }
    }

    private static function errors(FileSystemItem $item): string
    {
        $errors = $item->getFirstErrors();
        if ($item instanceof File && $item->baseFile !== null) {
            $errors = array_merge($errors, $item->baseFile->getFirstErrors());
        }

        return $errors === [] ? Yii::t('CfilesModule.base', 'The item could not be saved.') : implode(' ', $errors);
    }
}
