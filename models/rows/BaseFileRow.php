<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2017 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 *
 */

namespace humhub\modules\cfiles\models\rows;

use humhub\modules\cfiles\libs\FileUtils;
use humhub\modules\cfiles\models\File;
use humhub\modules\file\libs\FileHelper;

/**
 * Created by PhpStorm.
 * User: buddha
 * Date: 31.08.2017
 * Time: 00:20
 */

class BaseFileRow extends AbstractFileSystemItemRow
{
    const DEFAULT_ORDER = 'file.file_name ASC';

    const ORDER_MAPPING = [
        self::ORDER_TYPE_NAME => 'file.file_name',
        self::ORDER_TYPE_UPDATED_AT => 'file.updated_at',
        self::ORDER_TYPE_SIZE => 'cast(file.size as unsigned)',
    ];

    /**
     * @var \humhub\modules\cfiles\models\Folder
     */
    public $parentFolder;

    /**
     * @var \humhub\modules\file\models\File
     */
    public $baseFile;


    /**
     * @return boolean
     */
    public function isSelectable()
    {
        return false;
    }

    /**
     * @return boolean
     */
    public function isSocialActionsAvailable()
    {
        return true;
    }

    /**
     * @return array
     */
    public function getColumns()
    {
        //self::COLUMN_VISIBILITY
        return [self::COLUMN_TITLE, self::COLUMN_VISIBILITY, self::COLUMN_SIZE, self::COLUMN_TIMESTAMP, self::COLUMN_DOWNLOAD_COUNT, self::COLUMN_CREATOR];
    }

    /**
     * @return integer
     */
    public function getParentFolderId()
    {
        return $this->parentFolder->id;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return FileUtils::getItemTypeByExt(FileHelper::getExtension($this->baseFile));
    }

    /**
     * @return string
     */
    public function getItemId()
    {
        return 'baseFile_'.$this->baseFile->id;
    }

    /**
     * @inheritdoc
     */
    public function getContentId()
    {
        return null;
    }

    /**
     * @return string
     */
    public function getUrl()
    {
        return  $this->baseFile->getUrl() . '&' . http_build_query(['download' => true]);
    }

    /**
     * @return string
     */
    public function getLinkUrl()
    {
        return $this->baseFile->getUrl();
    }

    /**
     * @return string
     */
    public function getDisplayUrl()
    {
        return $this->getUrl();
    }

    /**
     * @return string
     */
    public function getWallUrl()
    {
        return File::getBasePost($this->baseFile)->getUrl();
    }

    /**
     * @return string
     */
    public function getEditUrl()
    {
        return null;
    }

    /**
     * @return string
     */
    public function getMoveUrl()
    {
        //TODO: copy file when moving?
        return null;
    }

    /**
     * @return string
     */
    public function getVersionsUrl()
    {
        return null;
    }

    /**
     * @return string
     */
    public function getIconClass()
    {
        return FileUtils::getIconClassByExt($this->getType());
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->baseFile->file_name;
    }

    /**
     * @return string
     */
    public function getSize()
    {
        return $this->baseFile->size;
    }

    /**
     * @return \humhub\modules\user\models\User
     */
    public function getCreator()
    {
        return $this->baseFile->createdBy;
    }

    /**
     * @return \humhub\modules\user\models\User
     */
    public function getEditor()
    {
        return $this->baseFile->createdBy;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return '';
    }

    /**
     * @return integer
     */
    public function getDownloadCount()
    {
        return 0;
    }

    /**
     * @return string
     */
    public function getUpdatedAt()
    {
        return $this->baseFile->updated_at;
    }

    /**
     * @return \humhub\modules\content\components\ContentActiveRecord
     */
    public function getModel()
    {
        return $this->baseFile->getPolymorphicRelation();
    }

    /**
     * @return string
     */
    public function getVisibilityIcon()
    {
        return $this->getModel()->content->isPublic() ? 'fa-unlock-alt': 'fa-lock';
    }

    /**
     * @return string
     */
    public function getVisibilityTitle()
    {
        $file = new File();
        $file->populateRelation('content', $this->getModel()->content);
        return $file->getVisibilityTitle();
    }

    /**
     * @inheritdoc
     */
    public function getBaseFile()
    {
        return $this->baseFile;
    }

    /**
     * @return boolean
     */
    public function canEdit()
    {
        // We do not allow base files to be deleted in the cfiles module
        return false;
    }
}