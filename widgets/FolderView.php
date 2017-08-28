<?php

namespace humhub\modules\cfiles\widgets;
use humhub\modules\cfiles\models\Folder;
use humhub\modules\content\components\ContentContainerActiveRecord;

/**
 * Widget for rendering the file list bar.
 */
class FolderView extends \humhub\widgets\JsWidget
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
     * @var boolean
     */
    public $canWrite;

    /**
     * @inheritdoc
     */
    public function getData()
    {
        return [
            'fid' => $this->folder->id,
            'upload-url' => $this->contentContainer->createUrl('/cfiles/upload', ['fid' => $this->folder->id]),
            'reload-file-list-url' => $this->contentContainer->createUrl('/cfiles/browse/file-list', ['fid' => $this->folder->id]),
            'delete-url' => $this->contentContainer->createUrl('/cfiles/delete', ['fid' => $this->folder->id]),
            'zip-upload-url' => $this->contentContainer->createUrl('/cfiles/zip/upload', ['fid' => $this->folder->id]),
            'download-archive-url' => $this->contentContainer->createUrl('/cfiles/zip/download'),
            'move-url' => $this->contentContainer->createUrl('/cfiles/move', ['init' => 1]),  
            'import-url' => $this->contentContainer->createUrl('/cfiles/upload/import', ['fid' => $this->folder->id]),  
        ];
    }

    /**
     * @inheritdoc
     */
    public function run() {        
        return $this->render('folderView', [
            'folder' => $this->folder,
            'options' => $this->getOptions(),
            'canWrite' => $this->canWrite,
            'contentContainer' => $this->contentContainer
        ]);
    }

}