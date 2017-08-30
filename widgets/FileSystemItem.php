<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2015 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\cfiles\widgets;

use humhub\modules\cfiles\models\File;
use humhub\modules\cfiles\models\Folder;
use Yii;

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
    public $columns = ['select', 'visibility', 'title', 'size', 'timestamp', 'likesncomments', 'creator'];
    public $parentFolderId;
    public $type;
    public $id;
    public $downloadUrl;
    public $url;
    public $wallUrl;
    public $moveUrl;
    public $iconClass;
    public $title;
    public $size;
    public $creator;
    public $editor;
    public $updatedAt;
    public $baseFile;
    public $visibilityIcon;
    public $visibilityTitle;
    public $description;


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
        } else if(!$this->downloadUrl && $this->item instanceof Folder) {
            $this->downloadUrl = $this->item->getUrl();
        }

        if (!$this->url) {
            $this->url = $this->item->getUrl();
        }

        if (!$this->wallUrl) {
            $this->wallUrl = $this->item->content->container->createUrl(null, ['contentId' => $this->item->content->id]);
        }

        if (!$this->iconClass) {
            $this->iconClass = $this->item->getIcon();
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

        if(!$this->description) {
            $this->description = $this->item->description;
        }

        if(!($this->item instanceof Folder) || $this->item->type !== Folder::TYPE_FOLDER_POSTED) {
            $this->setVisibilityOptions();
        }

        if($this->item && $this->canWrite) {
            $this->moveUrl =  $this->item->content->container->createUrl('/cfiles/move', ['fid' => $this->parentFolderId]);
        }

        parent::init();
    }

    private function setVisibilityOptions()
    {
        if(!$this->visibilityIcon) {
            $this->visibilityIcon = $this->item->content->isPublic() ? 'fa-unlock-alt': 'fa-lock' ;
        }

        if(!$this->visibilityTitle) {
            if($this->contentObject && $this->contentObject instanceof Folder) {
                $this->visibilityTitle = $this->item->content->isPublic()
                    ? Yii::t('CfilesModule.base', 'This folder is public.')
                    : Yii::t('CfilesModule.base', 'This folder is private.') ;
            }

            if($this->contentObject && $this->contentObject instanceof File) {
                $this->visibilityTitle = $this->item->content->isPublic()
                    ?  Yii::t('CfilesModule.base', 'This file is public.')
                    : Yii::t('CfilesModule.base', 'This file is private.') ;
            }
        }
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
                    'moveUrl' => $this->moveUrl,
                    'iconClass' => $this->iconClass,
                    'title' => $this->title,
                    'size' => $this->size,
                    'creator' => $this->creator,
                    'editor' => $this->editor,
                    'description' => $this->description,
                    'updatedAt' => $this->updatedAt,
                    'contentObject' => $this->contentObject,
                    'visibilityIcon' => $this->visibilityIcon,
                    'visibilityTitle' => $this->visibilityTitle,
        ]);
    }

}
