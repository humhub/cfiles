<?php

use humhub\commands\IntegrityController;
use humhub\components\api\ApiRules;
use humhub\modules\cfiles\components\UrlRule;
use humhub\modules\file\controllers\FileController;
use humhub\modules\file\models\File;
use humhub\modules\space\widgets\Menu;
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
            // Reading and adding are addressed as a container plus an optional `parent`
            // folder: the top level has no folder record, so a folder id alone cannot name
            // every level of a tree.
            ['pattern' => 'cfiles/<containerId:\d+>/items', 'route' => 'cfiles/api/folder/items', 'verb' => ['GET', 'HEAD']],
            ['pattern' => 'cfiles/<containerId:\d+>/folders', 'route' => 'cfiles/api/folder/create', 'verb' => 'POST'],
            ['pattern' => 'cfiles/<containerId:\d+>/files', 'route' => 'cfiles/api/folder/upload', 'verb' => 'POST'],
            // Changing something that exists addresses it by its own id.
            ['pattern' => 'cfiles/folder/<id:\d+>', 'route' => 'cfiles/api/folder/update', 'verb' => ['PATCH', 'PUT']],
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
    ],
];
