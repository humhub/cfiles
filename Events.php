<?php

namespace humhub\modules\cfiles;

use humhub\modules\cfiles\models\File;
use humhub\modules\cfiles\models\Folder;
use humhub\modules\file\actions\DownloadAction;
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

        if ($event->sender->space !== null && $event->sender->space->isModuleEnabled('cfiles')) {
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
        if ($event->sender->user !== null && $event->sender->user->isModuleEnabled('cfiles')) {
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

}
