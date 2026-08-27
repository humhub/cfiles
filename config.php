<?php

use humhub\commands\IntegrityController;
use humhub\components\api\ApiRules;
use humhub\modules\cfiles\components\UrlRule;
use humhub\modules\content\components\ContentContainerActiveRecord;
use humhub\modules\content\models\ContentContainerModuleState;
use humhub\modules\file\controllers\FileController;
use humhub\modules\file\models\File;
use humhub\modules\space\widgets\Menu;
use humhub\modules\space\models\Space;
use humhub\modules\user\widgets\ProfileMenu;

return [
    'id' => 'cfiles',
    'class' => 'humhub\modules\cfiles\Module',
    'namespace' => 'humhub\modules\cfiles',
    'urlManagerRules' => array_merge(
        [
            // Pretty, cache-busting download URLs — see components\UrlRule.
            ['class' => UrlRule::class],
        ],
        // The module's only JSON surface (see docs/develop/concept-api.md in core). The Vue
        // file browser is built entirely on these seven endpoints.
        ApiRules::v2([
            ['pattern' => 'cfiles/folder/<id:\d+>', 'route' => 'cfiles/api/folder/view', 'verb' => ['GET', 'HEAD']],
            ['pattern' => 'cfiles/folder/<id:\d+>', 'route' => 'cfiles/api/folder/update', 'verb' => ['PATCH', 'PUT']],
            ['pattern' => 'cfiles/folder/<id:\d+>/folders', 'route' => 'cfiles/api/folder/create', 'verb' => 'POST'],
            ['pattern' => 'cfiles/folder/<id:\d+>/files', 'route' => 'cfiles/api/folder/upload', 'verb' => 'POST'],
            ['pattern' => 'cfiles/file/<id:\d+>', 'route' => 'cfiles/api/file/update', 'verb' => ['PATCH', 'PUT']],
            ['pattern' => 'cfiles/items/move', 'route' => 'cfiles/api/item/move', 'verb' => 'POST'],
            ['pattern' => 'cfiles/items/delete', 'route' => 'cfiles/api/item/delete', 'verb' => 'POST'],
        ]),
    ),
    'events' => [
        [Menu::class, Menu::EVENT_INIT, ['humhub\modules\cfiles\Events', 'onSpaceMenuInit']],
        [ProfileMenu::class, ProfileMenu::EVENT_INIT, ['humhub\modules\cfiles\Events', 'onProfileMenuInit']],
        [IntegrityController::class, IntegrityController::EVENT_ON_RUN, ['humhub\modules\cfiles\Events', 'onIntegrityCheck']],
        [FileController::class, FileController::EVENT_AFTER_ACTION, ['humhub\modules\cfiles\Events', 'onAfterFileAction']],
        [File::class, File::EVENT_AFTER_NEW_STORED_FILE, ['humhub\modules\cfiles\Events', 'onAfterNewStoredFile']],
        [ContentContainerActiveRecord::class, ContentContainerActiveRecord::EVENT_AFTER_INSERT, ['humhub\modules\cfiles\Events', 'onContentContainerActiveRecordInsert']],
        [ContentContainerModuleState::class, ContentContainerModuleState::EVENT_AFTER_INSERT, ['humhub\modules\cfiles\Events', 'onContentContainerModuleStateInsert']],
        [Space::class, Space::EVENT_AFTER_UPDATE, ['humhub\modules\cfiles\Events', 'onSpaceAfterUpdate']],
    ],
];
