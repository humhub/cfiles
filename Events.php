<?php

namespace humhub\modules\cfiles;

use humhub\helpers\ControllerHelper;
use humhub\modules\cfiles\models\File;
use humhub\modules\cfiles\services\DownloadCounterService;
use humhub\modules\cfiles\services\IntegrityService;
use humhub\modules\file\models\File as BaseFile;
use humhub\modules\space\models\Space;
use humhub\modules\space\widgets\Menu;
use humhub\modules\ui\menu\MenuLink;
use humhub\modules\user\models\User;
use humhub\modules\user\widgets\ProfileMenu;
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
        /* @var Menu $menu */
        $menu = $event->sender;

        if ($menu->space !== null && $menu->space->moduleManager->isEnabled('cfiles')) {
            $menu->addEntry(new MenuLink([
                'label' => Yii::t('CfilesModule.base', 'Files'),
                'url' => $event->sender->space->createUrl('/cfiles/browse'),
                'icon' => 'files-o',
                'isActive' => ControllerHelper::isActivePath('cfiles'),
            ]));
        }
    }

    /**
     * Validates the module's records — see {@see IntegrityService}.
     */
    public static function onIntegrityCheck($event)
    {
        IntegrityService::check($event->sender);
    }

    /**
     * Counts a download the core file module just served — see {@see DownloadCounterService}.
     */
    public static function onAfterFileAction(Event $event)
    {
        DownloadCounterService::track($event->action ?? null);
    }

    public static function onProfileMenuInit($event)
    {
        /* @var ProfileMenu $menu */
        $menu = $event->sender;
        if ($menu->user !== null && $menu->user->moduleManager->isEnabled('cfiles')) {
            $menu->addEntry(new MenuLink([
                'label' => Yii::t('CfilesModule.base', 'Files'),
                'url' => $event->sender->user->createUrl('/cfiles/browse'),
                'icon' => 'files-o',
                'isActive' => ControllerHelper::isActivePath('cfiles'),
            ]));
        }
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
