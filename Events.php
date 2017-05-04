<?php

namespace humhub\modules\cfiles;

use Yii;

/**
 * Description of WikiEvents
 *
 * @author luke
 */
class Events extends \yii\base\Object
{

    public static function onSpaceMenuInit($event)
    {
        if ($event->sender->space !== null && $event->sender->space->isModuleEnabled('cfiles') && $event->sender->space->isMember()) {
            $event->sender->addItem(array(
                'label' => Yii::t('CfilesModule.base', 'Files'),
                'group' => 'modules',
                'url' => $event->sender->space->createUrl('/cfiles/browse'),
                'icon' => '<i class="fa fa-files-o"></i>',
                'isActive' => (Yii::$app->controller->module && Yii::$app->controller->module->id == 'cfiles')
            ));
        }
    }

    public static function onProfileMenuInit($event)
    {
        if ($event->sender->user !== null && $event->sender->user->isModuleEnabled('cfiles')) {

            if ($event->sender->user->canAccessPrivateContent()) {
                $event->sender->addItem(array(
                    'label' => Yii::t('CfilesModule.base', 'Files'),
                    'url' => $event->sender->user->createUrl('/cfiles/browse'),
                    'isActive' => (Yii::$app->controller->module && Yii::$app->controller->module->id == 'cfiles')
                ));
            }
        }
    }

}
