<?php

namespace humhub\modules\cfiles\widgets;

use humhub\modules\cfiles\permissions\ManageFiles;
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
     * @inheritdoc
     */
    public function run()
    {
        $canWrite = $this->folder->content->container->can(ManageFiles::class);

        return $this->render('fileListContextMenu', [
            'folder' => $this->folder,
            'canWrite' => $canWrite,
            'zipEnabled' => !Yii::$app->getModule('cfiles')->settings->get('disableZipSupport'),
        ]);
    }

}