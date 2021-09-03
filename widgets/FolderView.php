<?php

namespace humhub\modules\cfiles\widgets;

use humhub\modules\cfiles\models\Folder;
use humhub\modules\content\components\ContentContainerActiveRecord;
use humhub\widgets\JsWidget;

/**
 * Widget for rendering the file list bar.
 */
class FolderView extends JsWidget
{

    /**
     * @inheritdoc
     */
    public $jsWidget = 'cfiles.FolderView';

    /**
     * @inheritdoc
     */
    public $id = 'cfiles-folderView';

    /**
     * @var ContentContainerActiveRecord
     */
    public $contentContainer;

    /**
     * @var Folder
     */
    public $folder;

    /**
     * @inheritdoc
     */
    public $init = true;

    /**
     * @inheritdoc
     */
    public function getData()
    {
        return [
            'fid' => $this->folder->id,
            'upload-url' => $this->folder->createUrl('/cfiles/upload'),
            'reload-file-list-url' => $this->folder->createUrl('/cfiles/browse/file-list'),
            'delete-url' => $this->folder->createUrl('/cfiles/delete'),
            'zip-upload-url' => $this->folder->createUrl('/cfiles/zip/upload'),
            'download-archive-url' => $this->folder->createUrl('/cfiles/zip/download'),
            'move-url' => $this->folder->createUrl('/cfiles/move'),
            'drop-url' => $this->folder->createUrl('/cfiles/move/drop'),
            'import-url' => $this->folder->createUrl('/cfiles/upload/import'),
        ];
    }

    /**
     * @inheritdoc
     */
    public function run() {        
        return $this->render('folderView', [
            'folder' => $this->folder,
            'options' => $this->getOptions(),
            'contentContainer' => $this->contentContainer
        ]);
    }

}