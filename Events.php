<?php

namespace humhub\modules\cfiles;

use humhub\modules\cfiles\models\File;
use Yii;
use yii\base\Object;

/**
 * cfiles Events
 *
 * @author luke
 */
class Events extends Object
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

}
