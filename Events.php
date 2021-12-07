<?php

namespace humhub\modules\cfiles;

use humhub\modules\cfiles\models\File;
use humhub\modules\cfiles\models\Folder;
use humhub\modules\content\components\ContentContainerActiveRecord;
use humhub\modules\content\models\ContentContainer;
use humhub\modules\content\models\ContentContainerModuleState;
use humhub\modules\file\actions\DownloadAction;
use humhub\modules\file\models\File as BaseFile;
use humhub\modules\space\models\Space;
use humhub\modules\user\models\User;
use Yii;
use yii\base\Event;


/**
 * cfiles Events
 *
 * @author luke
 */
class Events
{

    public static function onSpaceMenuInit($event)
    {

        if ($event->sender->space !== null && $event->sender->space->moduleManager->isEnabled('cfiles')) {
            $event->sender->addItem([
                'label' => Yii::t('CfilesModule.base', 'Files'),
                'group' => 'modules',
                'url' => $event->sender->space->createUrl('/cfiles/browse'),
                'icon' => '<i class="fa fa-files-o"></i>',
                'isActive' => (Yii::$app->controller->module && Yii::$app->controller->module->id == 'cfiles')
            ]);
        }
    }

    /**
     * Callback to validate module database records.
     *
     * @param Event $event
     */
    public static function onIntegrityCheck($event)
    {
        $integrityController = $event->sender;
        $integrityController->showTestHeadline("CFile Module (" . File::find()->count() . " entries)");

        foreach (File::find()->all() as $file) {
            /* @var $file \humhub\modules\cfiles\models\File */

            // If parent_folder_id is 0 or null its an old root child which is not merged yet.
            if (!empty($file->parent_folder_id) && empty($file->parentFolder)) {
                if ($integrityController->showFix("Deleting cfile id " . $file->id . " without existing parent!")) {
                    $file->delete();
                }
            }
        }

        $integrityController->showTestHeadline("CFile Module (" . File::find()->count() . " entries)");

        foreach (Folder::find()->all() as $folder) {
            /* @var $file \humhub\modules\cfiles\models\File */

            // If parent_folder_id is 0 or null its either an old root child which is not merged yet or an root directory.
            if (!empty($folder->parent_folder_id) && empty($folder->parentFolder)) {
                if ($integrityController->showFix("Deleting cfile folder id " . $folder->id . " without existing parent!")) {
                    $folder->delete();
                }
            }
        }
    }

    public static function onProfileMenuInit($event)
    {
        if ($event->sender->user !== null && $event->sender->user->moduleManager->isEnabled('cfiles')) {
            $event->sender->addItem([
                'label' => Yii::t('CfilesModule.base', 'Files'),
                'url' => $event->sender->user->createUrl('/cfiles/browse'),
                'icon' => '<i class="fa fa-files-o"></i>',
                'isActive' => (Yii::$app->controller->module && Yii::$app->controller->module->id == 'cfiles')
            ]);
        }
    }

    /**
     * Callback on after file controller action
     *
     * @param Event $event
     */
    public static function onAfterFileAction(Event $event)
    {
        if (isset($event->action) &&
            $event->action instanceof DownloadAction &&
            ($downloadedFile = File::getFileByGuid(Yii::$app->request->get('guid')))
        ) {
            $downloadedFile->updateAttributes(['download_count' => $downloadedFile->download_count + 1]);
        }
    }

    /**
     * Callback when user or space is inserted
     *
     * @param Event $event
     */
    public static function onContentContainerActiveRecordInsert($event)
    {
        /**
         * @var ContentContainerActiveRecord|Space|User $container
         */
        $container = $event->sender;

        if ($container instanceof ContentContainerActiveRecord &&
            $container->moduleManager->isEnabled('cfiles')) {
            Folder::initRoot($container);
            Folder::initPostedFilesFolder($container);
        }
    }

    /**
     * Callback when module is enabled first time
     *
     * @param Event $event
     */
    public static function onContentContainerModuleStateInsert($event)
    {
        /**
         * @var ContentContainerModuleState $moduleState
         */
        $moduleState = $event->sender;

        if (!($moduleState instanceof ContentContainerModuleState &&
            $moduleState->module_id == 'cfiles' &&
            $moduleState->module_state)) {
            return;
        }

        if (($contentContainer = ContentContainer::findOne(['id' => $moduleState->contentcontainer_id])) &&
            ($container = $contentContainer->getPolymorphicRelation())) {
            Folder::initRoot($container);
            Folder::initPostedFilesFolder($container);
        }
    }

    public static function onRestApiAddRules()
    {
        /* @var \humhub\modules\rest\Module $restModule */
        $restModule = Yii::$app->getModule('rest');
        $restModule->addRules([

            //File
            ['pattern' => 'cfiles/files/container/<containerId:\d+>', 'route' => 'cfiles/rest/file/find-by-container', 'verb' => 'GET'],
            ['pattern' => 'cfiles/files/container/<containerId:\d+>', 'route' => 'cfiles/rest/file/upload', 'verb' => 'POST'],
            ['pattern' => 'cfiles/file/<id:\d+>', 'route' => 'cfiles/rest/file/view', 'verb' => ['GET', 'HEAD']],
            ['pattern' => 'cfiles/file/<id:\d+>', 'route' => 'cfiles/rest/file/delete', 'verb' => 'DELETE'],

            //Folder
            ['pattern' => 'cfiles/folders/container/<containerId:\d+>', 'route' => 'cfiles/rest/folder/find-by-container', 'verb' => 'GET'],
            ['pattern' => 'cfiles/folders/container/<containerId:\d+>', 'route' => 'cfiles/rest/folder/create', 'verb' => 'POST'],
            ['pattern' => 'cfiles/folder/<id:\d+>', 'route' => 'cfiles/rest/folder/view', 'verb' => ['GET', 'HEAD']],
            ['pattern' => 'cfiles/folder/<id:\d+>', 'route' => 'cfiles/rest/folder/update', 'verb' => 'PUT'],
            ['pattern' => 'cfiles/folder/<id:\d+>', 'route' => 'cfiles/rest/folder/delete', 'verb' => 'DELETE'],

            //Items management
            ['pattern' => 'cfiles/items/container/<containerId:\d+>/make-public', 'route' => 'cfiles/rest/manage/make-public', 'verb' => 'PATCH'],
            ['pattern' => 'cfiles/items/container/<containerId:\d+>/make-private', 'route' => 'cfiles/rest/manage/make-private', 'verb' => 'PATCH'],
            ['pattern' => 'cfiles/items/container/<containerId:\d+>/move', 'route' => 'cfiles/rest/manage/move', 'verb' => 'POST'],
            ['pattern' => 'cfiles/items/container/<containerId:\d+>/delete', 'route' => 'cfiles/rest/manage/delete', 'verb' => 'DELETE'],

        ], 'cfiles');
    }

    public static function onAfterNewStoredFile($event)
    {
        $baseFile = $event->sender;
        if (!($baseFile instanceof BaseFile)) {
            return;
        }

        $file = File::findOne($baseFile->object_id);
        if (!$file) {
            return;
        }

        $file->content->updateAttributes([
            'updated_at' => $baseFile->updated_at,
            'updated_by' => $baseFile->updated_by,
        ]);
    }

}
