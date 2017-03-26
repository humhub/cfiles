<?php

namespace humhub\modules\cfiles;

use Yii;
use humhub\modules\space\models\Space;
use humhub\modules\user\models\User;
use humhub\modules\content\components\ContentContainerModule;
use humhub\modules\content\components\ContentContainerActiveRecord;
use humhub\modules\cfiles\models\Folder;
use humhub\modules\cfiles\models\File;
use yii\helpers\Url;

class Module extends ContentContainerModule
{

    public $resourcesPath = 'resources';

    const ALL_POSTED_FILES_TITLE = 'Files from the stream';
    const ALL_POSTED_FILES_DESCRIPTION = 'You can find all files that have been posted to this stream here.';
    const ROOT_TITLE = 'Root';
    const ROOT_DESCRIPTION = 'The root folder is the entry point that contains all available files.';

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
                new permissions\WriteAccess(),
                new permissions\ManageFiles(),
            ];
        }

        return [];
    }

    /**
     * @inheritdoc
     */
    public function disable()
    {
        foreach (Folder::find()->all() as $key => $folder) {
            $folder->delete();
        }
        foreach (File::find()->all() as $key => $file) {
            $file->delete();
        }
        parent::disable();
    }

    /**
     * @inheritdoc
     */
    public function disableContentContainer(ContentContainerActiveRecord $container)
    {

        foreach (Folder::find()->contentContainer($container)->all() as $folder) {
            $folder->delete();
        }

        foreach (File::find()->contentContainer($container)->all() as $file) {
            $file->delete();
        }
        parent::disableContentContainer($container);
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

    /**
     * Loads user by given ID (Helper)
     * 
     * @param int $id the user id
     * @return User|null the user
     */
    public static function getUserById($id)
    {
        return User::findOne(['id' => $id]);
    }

    /**
     * Determines ZIP Support is enabled or not
     * 
     * @return boolean is ZIP support enabled
     */
    public function isZipSupportEnabled()
    {
        $zipEnabled = !$this->settings->get('disableZipSupport');

        return $zipEnabled;
    }

}
