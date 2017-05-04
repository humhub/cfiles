<?php

namespace humhub\modules\cfiles\widgets;

use Yii;

/**
 * Widget for rendering the file list context menu.
 */
class FileListContextMenu extends \yii\base\Widget
{
    /**
     * Current folder model instance.
     * @var \humhub\modules\cfiles\models\Folder
     */
    public $folder;
    
    /**
     * Determines if the user has write permissions.
     * @var boolean 
     */
    public $canWrite;
    

    /**
     * @inheritdoc
     */
    public function run()
    {
        return $this->render('fileListContextMenu', [
            'folder' => $this->folder,
            'canWrite' => $this->canWrite,
            'zipEnabled' => !Yii::$app->getModule('cfiles')->settings->get('disableZipSupport'),
        ]);
    }

}