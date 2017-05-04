<?php

namespace humhub\modules\cfiles\widgets;

/**
 * Widget for rendering the file list bar.
 */
class FileListBar extends \yii\base\Widget
{
    
    /**
     * @var \humhub\modules\cfiles\models\Folder current folder
     */
    public $folder;
    
    /**
     * Current content container.
     * @var \humhub\modules\content\components\ContentContainerActiveRecord 
     */
    public $contentContainer;

    /**
     * @inheritdoc
     */
    public function run()
    {
        return $this->render('fileListBar', [
            'folder' => $this->folder,
            'contentContainer' => $this->contentContainer
        ]);
    }

}