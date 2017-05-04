<?php

namespace humhub\modules\cfiles\widgets;

/**
 * Widget for rendering the file list bar.
 */
class FolderView extends \humhub\widgets\JsWidget
{
    
    public $jsWidget = 'cfiles.FolderView';
    
    public $id = 'cfiles-folderView';
    
    public $contentContainer;
    
    public $folder;
    
    public $canWrite;
    
    public function getData()
    {
        return [
            'fid' => $this->folder->id,
            'upload-url' => $this->contentContainer->createUrl('/cfiles/upload', ['fid' => $this->folder->id]),
            'reload-file-list-url' => $this->contentContainer->createUrl('/cfiles/browse/file-list', ['fid' => $this->folder->id]),
            'delete-url' => $this->contentContainer->createUrl('/cfiles/delete', ['fid' => $this->folder->id]),
            'zip-upload-url' => $this->contentContainer->createUrl('/cfiles/zip/upload-archive', ['fid' => $this->folder->id]),
            'download-archive-url' => $this->contentContainer->createUrl('/cfiles/zip/download-archive'),
            'move-url' => $this->contentContainer->createUrl('/cfiles/move', ['init' => 1]),  
            'import-url' => $this->contentContainer->createUrl('/cfiles/upload/import', ['fid' => $this->folder->id]),  
        ];
    }
    
    public function run() {        
        return $this->render('folderView', [
            'folder' => $this->folder,
            'options' => $this->getOptions(),
            'canWrite' => $this->canWrite,
            'contentContainer' => $this->contentContainer
        ]);
    }

}