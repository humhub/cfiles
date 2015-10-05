<?php
namespace humhub\modules\cfiles;

use Yii;
use humhub\modules\space\models\Space;
use humhub\modules\content\components\ContentContainerModule;
use humhub\modules\cfiles\models\Folder;
use humhub\modules\cfiles\models\File;
use humhub\modules\content\models\Content;

class Module extends ContentContainerModule
{

    /**
     * @inheritdoc
     */
    public function getContentContainerTypes()
    {
        return [
            Space::className()
        ];
    }

    public function getItemById($itemId)
    {
        list ($type, $id) = explode('-', $itemId);
        
        if ($type == 'file') {
            return models\File::findOne([
                'id' => $id
            ]);
        } elseif ($type == 'folder') {
            return models\Folder::findOne([
                'id' => $id
            ]);
        }
        return null;
    }

    public function disable()
    {
        foreach (Folder::find()->all() as $key => $folder) {
            $folder->delete();
        }
        foreach (File::find()->all() as $key => $file) {
            $file->delete();
        }
    }

    public function disableContentContainer($container)
    {
        $folders = Content::findAll([
            'object_model' => Folder::className(),
            'space_id' => $container->id
        ]);
        foreach ($folders as $key => $folderContent) {
            $folder = Folder::findOne([
                'id' => $folderContent->object_id
            ]);
            $folder->delete();
        }
        $files = Content::findAll([
            'object_model' => File::className(),
            'space_id' => $container->id
        ]);
        foreach ($files as $key => $fileContent) {
            $file = File::findOne([
                'id' => $fileContent->object_id
            ]);
            $file->delete();
        }
    }
}
