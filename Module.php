<?php

namespace humhub\modules\cfiles;

use Yii;
use humhub\modules\space\models\Space;
use humhub\modules\user\models\User;
use humhub\modules\content\components\ContentContainerModule;
use humhub\modules\content\components\ContentContainerActiveRecord;
use humhub\modules\cfiles\models\Folder;
use humhub\modules\cfiles\models\File;
use humhub\modules\content\models\Content;
use yii\helpers\Url;

class Module extends ContentContainerModule
{

    /**
     * @inheritdoc
     */
    public function getContentContainerTypes()
    {
        return [
            Space::className(),
            User::className()
        ];
    }

    /**
     * @inheritdoc
     */
    public function getPermissions($contentContainer = null)
    {
        if ($contentContainer instanceof Space) {
            return [
                new permissions\WriteAccess()
            ];
        }

        return [];
    }

    public function getItemById($itemId)
    {
        list ($type, $id) = explode('_', $itemId);

        if ($type == 'file') {
            return models\File::findOne([
                        'id' => $id
            ]);
        } elseif ($type == 'folder') {
            return models\Folder::findOne([
                        'id' => $id
            ]);
        } elseif ($type == 'baseFile') {
            return \humhub\modules\file\models\File::findOne([
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

    public function disableContentContainer(\humhub\modules\content\components\ContentContainerActiveRecord $container)
    {

        foreach (Folder::find()->contentContainer($container)->all() as $folder) {
            $folder->delete();
        }

        foreach (File::find()->contentContainer($container)->all() as $file) {
            $file->delete();
        }
    }

    /**
     * @inheritdoc
     */
    public function getContentContainerName(ContentContainerActiveRecord $container)
    {
        return Yii::t('CfilesModule.base', 'Files');
    }

    /**
     * @inheritdoc
     */
    public function getContentContainerDescription(ContentContainerActiveRecord $container)
    {
        if ($container instanceof Space) {
            return Yii::t('CfilesModule.base', 'Adds files module to this space.');
        } elseif ($container instanceof User) {
            return Yii::t('CfilesModule.base', 'Adds files module to your profile.');
        }
    }

    /**
     * @inheritdoc
     */
    public function getConfigUrl()
    {
        return Url::to([
                    '/cfiles/config'
        ]);
    }

}
