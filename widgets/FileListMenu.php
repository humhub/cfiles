<?php

namespace humhub\modules\cfiles\widgets;

use Yii;
use humhub\modules\file\handler\FileHandlerCollection;

/**
 * Widget for rendering the file list menu.
 */
class FileListMenu extends \yii\base\Widget
{

    /**
     * Current folder model instance.
     * @var \humhub\modules\cfiles\models\Folder
     */
    public $folder;

    /**
     * @var \humhub\modules\content\components\ContentContainerActiveRecord Current content container.
     */
    public $contentContainer;

    /**
     * @var boolean Determines if the user has write permissions.
     */
    public $canWrite;

    /**
     * @var integer FileList item count.
     */
    public $itemCount;

    /**
     * @inheritdoc
     */
    public function run()
    {
        $fileHandlerImport = FileHandlerCollection::getByType(FileHandlerCollection::TYPE_IMPORT);
        $fileHandlerCreate = FileHandlerCollection::getByType(FileHandlerCollection::TYPE_CREATE);

        return $this->render('fileListMenu', [
                    'folder' => $this->folder,
                    'contentContainer' => $this->contentContainer,
                    'canWrite' => $this->canWrite,
                    'zipEnabled' => Yii::$app->getModule('cfiles')->isZipSupportEnabled(),
                    'hasItems' => true,
                    'fileHandlers' => array_merge($fileHandlerCreate, $fileHandlerImport),
        ]);
    }

}
