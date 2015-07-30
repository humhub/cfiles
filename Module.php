<?php

namespace humhub\modules\cfiles;

use Yii;
use humhub\modules\space\models\Space;
use humhub\modules\content\components\ContentContainerModule;

class Module extends ContentContainerModule
{

    /**
     * @inheritdoc
     */
    public function getContentContainerTypes()
    {
        return [
            Space::className(),
        ];
    }

    public function getItemById($itemId)
    {

        list($type, $id) = explode('-', $itemId);

        if ($type == 'file') {
            return models\File::findOne(['id' => $id]);
        } elseif ($type == 'folder') {
            return models\Folder::findOne(['id' => $id]);
        }
        return null;
    }

}
