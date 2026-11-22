<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2026 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\cfiles\components\dav;

use humhub\modules\cfiles\models\File;
use humhub\modules\cfiles\services\FolderContentService;
use humhub\modules\dav\components\UploadedBody;
use Sabre\DAV\Exception\Conflict;
use Sabre\DAV\Exception\Forbidden;
use Yii;

/**
 * A file as WebDAV serves it: its stored content, streamed, with the SHA-1 of the content as
 * its ETag (see getETag()).
 *
 * Writing it (`PUT`) stores a new version, the previous content kept in its history, as
 * replacing it in the browser does — only for those who may change it. An empty file has no
 * version worth keeping: clients create one with `LOCK` before they write the content.
 *
 * @since 1.0
 */
class FileNode extends \Sabre\DAV\File
{
    public function __construct(public readonly File $file)
    {
    }

    /**
     * @inheritdoc
     */
    public function getName()
    {
        return $this->file->getTitle();
    }

    /**
     * @inheritdoc
     */
    public function get()
    {
        return $this->file->baseFile->store->getContentStream();
    }

    /**
     * @inheritdoc
     */
    public function getSize()
    {
        return (int)$this->file->baseFile->size;
    }

    /**
     * The SHA-1 of the content; for a file stored without it (an import, say), one of what
     * changes with every version.
     *
     * @inheritdoc
     */
    public function getETag()
    {
        $baseFile = $this->file->baseFile;
        $hash = $baseFile->hash_sha1 ?: sha1($baseFile->guid . '|' . $baseFile->size . '|' . $baseFile->updated_at);

        return '"' . $hash . '"';
    }

    /**
     * @inheritdoc
     */
    public function getContentType()
    {
        return $this->file->baseFile->mime_type ?: null;
    }

    /**
     * @inheritdoc
     */
    public function getLastModified()
    {
        $updated = $this->file->baseFile->updated_at ?: $this->file->content->updated_at;

        return $updated ? (strtotime($updated) ?: null) : null;
    }

    /**
     * @inheritdoc
     */
    public function put($data)
    {
        if (!$this->file->content->canEdit()) {
            throw new Forbidden(Yii::t('CfilesModule.base', 'You cannot change the file "{name}".', ['name' => $this->getName()]));
        }

        $body = UploadedBody::fromData($data, $this->getName());
        $baseFile = $this->file->baseFile;
        $baseFile->setStoredFileByPath($body->path, (int)$baseFile->size === 0);

        return $this->getETag();
    }

    /**
     * Moves the file to the trash.
     *
     * @inheritdoc
     */
    public function delete()
    {
        if (!$this->file->content->canEdit() || !$this->file->delete()) {
            throw new Forbidden(Yii::t('CfilesModule.base', 'Could not delete the item!'));
        }
    }

    /**
     * @inheritdoc
     */
    public function setName($name)
    {
        if (!$this->file->content->canEdit()) {
            throw new Forbidden();
        }

        UploadedBody::assertValidName($name);
        $level = new FolderContentService($this->file->content->container, $this->file->parentFolder);
        if ($level->nameTaken($name, $this->file)) {
            throw new Conflict(Yii::t('CfilesModule.base', 'A file with that name already exists in this folder.'));
        }

        $this->file->setTitle($name);
        if (!$this->file->save()) {
            throw new Forbidden(implode(' ', array_merge($this->file->getFirstErrors(), $this->file->baseFile->getFirstErrors())));
        }
    }
}
