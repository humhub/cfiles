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
    
    /*
     * public static function onProfileMenuInit($event) { $user = $event->sender->user; if ($user->isModuleEnabled('wiki')) { $event->sender->addItem(array( 'label' => Yii::t('WikiModule.base', 'Wiki'), 'group' => 'modules', 'url' => $user->createUrl('//wiki/page'), 'isActive' => (Yii::$app->controller->module && Yii::$app->controller->module->id == 'wiki'), )); } }
     */
}
