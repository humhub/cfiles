<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2015 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\cfiles\widgets;

use humhub\modules\cfiles\models\File;

/**
 * @inheritdoc
 */
class FileSystemItem extends \yii\base\Widget
{

    /**
     * @var \humhub\modules\cfiles\models\FileSystemItem
     */
    public $item;
    public $canWrite;
    public $selectable = true;
    public $socialActionsAvailable = true;
    public $columns = ['select', 'title', 'size', 'timestamp', 'likesncomments', 'creator'];
    public $parentFolderId;
    public $type;
    public $id;
    public $downloadUrl;
    public $url;
    public $wallUrl;
    public $iconClass;
    public $title;
    public $size;
    public $creator;
    public $editor;
    public $updatedAt;
    public $baseFile;

    /** Content Object used for the Like/Comment widgets */
    public $contentObject;

    public function init()
    {
        if (!$this->item) {
            parent::init();
            return;
        }

        if (!$this->type) {
            $this->type = $this->item->getItemType();
        }

        if (!$this->id) {
            $this->id = $this->item->getItemId();
        }

        if (!$this->downloadUrl && $this->item instanceof File) {
            $this->downloadUrl = $this->item->getDownloadUrl();
        }

        if (!$this->url) {
            $this->url = $this->item->getUrl();
        }

        if (!$this->wallUrl) {
            $this->wallUrl = $this->item->getWallUrl();
        }

        if (!$this->iconClass) {
            $this->iconClass = $this->item->getIconClass();
        }

        if (!$this->title) {
            $this->title = $this->item->getTitle();
        }

        if ($this->size === null) {
            $this->size = $this->item->getSize();
        }

        if ($this->creator === null) {
            $this->creator = $this->item->getCreator();
        }

        if (!$this->editor === null) {
            $this->editor = $this->item->getEditor();
        }

        if (!$this->updatedAt) {
            $this->updatedAt = $this->item->content->updated_at;
        }

        parent::init();
    }

    /**
     * @inheritdoc
     */
    public function run()
    {
        return $this->render('fileSystemItem', [
                    'item' => $this->item,
                    'selectable' => $this->selectable,
                    'socialActionsAvailable' => $this->socialActionsAvailable,
                    'columns' => $this->columns,
                    'parentFolderId' => $this->parentFolderId,
                    'type' => $this->type,
                    'id' => $this->id,
                    'downloadUrl' => $this->downloadUrl,
                    'url' => $this->url,
                    'wallUrl' => $this->wallUrl,
                    'editUrl' => ($this->item && $this->canWrite) ? $this->item->getEditUrl() : null,
                    'moveUrl' => ($this->item && $this->canWrite) ? $this->item->getMoveUrl() : null,
                    'iconClass' => $this->iconClass,
                    'title' => $this->title,
                    'size' => $this->size,
                    'creator' => $this->creator,
                    'editor' => $this->editor,
                    'updatedAt' => $this->updatedAt,
                    'contentObject' => $this->contentObject
        ]);
    }

}
