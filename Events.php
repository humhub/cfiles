<?php

namespace humhub\modules\cfiles;

use humhub\helpers\ControllerHelper;
use humhub\modules\cfiles\components\dav\FilesCollection;
use humhub\modules\cfiles\models\File;
use humhub\modules\cfiles\services\DownloadCounterService;
use humhub\modules\cfiles\services\IntegrityService;
use humhub\modules\dav\components\Mount;
use humhub\modules\dav\events\MountsEvent;
use humhub\modules\file\models\File as BaseFile;
use humhub\modules\oauth\events\ScopesEvent;
use humhub\modules\oauth\models\Scope;
use humhub\modules\space\models\Space;
use humhub\modules\space\widgets\Menu;
use humhub\modules\user\models\User;
use humhub\modules\user\widgets\ProfileMenu;
use humhub\widgets\menu\MenuLink;
use humhub\widgets\TopMenu;
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
                'icon' => 'files',
                'isActive' => ControllerHelper::isActivePath('cfiles'),
            ]));
        }
    }

    /**
     * The main navigation entry of the global files page, while the admin enabled it.
     */
    public static function onTopMenuInit($event)
    {
        try {
            if (Yii::$app->user->isGuest) {
                return;
            }

            /** @var Module $module */
            $module = Yii::$app->getModule('cfiles');
            if (!$module->getShowGlobalMenuItem()) {
                return;
            }

            /** @var TopMenu $menu */
            $menu = $event->sender;
            $menu->addEntry(new MenuLink([
                'id' => 'cfiles-global',
                'label' => Yii::t('CfilesModule.base', 'Files'),
                'url' => ['/cfiles/global/index'],
                'icon' => 'folders',
                'sortOrder' => 500,
                'isActive' => ControllerHelper::isActivePath('cfiles', 'global'),
            ]));
        } catch (\Throwable $e) {
            // The main navigation must never break because of this entry.
            Yii::error($e);
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
                'icon' => 'files',
                'isActive' => ControllerHelper::isActivePath('cfiles'),
            ]));
        }
    }

    public static function onAfterNewStoredFile($event)
    {
        $baseFile = $event->sender;
        // Only a platform file held by a cfiles file: `object_id` alone is the id of any record.
        if (!($baseFile instanceof BaseFile) || $baseFile->object_model !== File::class) {
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

    /**
     * The scopes a token may hold for the module's API, see the `$scopes` of its controllers.
     */
    public static function onCollectScopes(ScopesEvent $event): void
    {
        $event->scopes[] = new Scope(Module::SCOPE_READ, Yii::t('CfilesModule.base', 'Read files and folders'));
        $event->scopes[] = new Scope(Module::SCOPE_WRITE, Yii::t('CfilesModule.base', 'Add, change, move and delete files and folders'));
    }

    /**
     * The files of the user's spaces and profile under `/dav/files/`, with the module's scopes
     * (see {@see FilesCollection}).
     */
    public static function onDavCollectMounts(MountsEvent $event): void
    {
        /** @var Module $module */
        $module = Yii::$app->getModule('cfiles');
        if (!$module->getEnableWebDav()) {
            return;
        }

        $user = $event->user;
        $event->mounts[] = new Mount(
            FilesCollection::NAME,
            Module::SCOPE_READ,
            Module::SCOPE_WRITE,
            static fn() => new FilesCollection($user),
        );
    }
}
