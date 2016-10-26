<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2015 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */
namespace humhub\modules\cfiles\widgets;

/**
 * @inheritdoc
 */
class FileSystemItem extends \yii\base\Widget
{
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
    
    /** Content Object used for the Like/Comment widgets */
    public $contentObject;

    /**
     * @inheritdoc
     */
    public function run()
    {
        return $this->render('fileSystemItem', array(
            'selectable' => $this->selectable,
            'socialActionsAvailable' => $this->socialActionsAvailable,
            'columns' => $this->columns,
            'parentFolderId' => $this->parentFolderId,
            'type' => $this->type,
            'id' => $this->id,
            'downloadUrl' => $this->downloadUrl,
            'url' => $this->url,
            'wallUrl' => $this->wallUrl,
            'iconClass' => $this->iconClass,
            'title' => $this->title,
            'size' => $this->size,
            'creator' => $this->creator,
            'editor' => $this->editor,
            'updatedAt' => $this->updatedAt,
            'contentObject' => $this->contentObject
        ));
    }
}
