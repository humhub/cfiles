<?php

use humhub\commands\IntegrityController;
use humhub\components\console\Application;
use humhub\modules\content\components\ContentContainerActiveRecord;
use humhub\modules\content\models\ContentContainerModuleState;
use humhub\modules\file\controllers\FileController;
use humhub\modules\space\widgets\Menu;
use humhub\modules\user\widgets\ProfileMenu;

return [
    'id' => 'cfiles',
    'class' => 'humhub\modules\cfiles\Module',
    'namespace' => 'humhub\modules\cfiles',
    'events' => [
        [Menu::class, Menu::EVENT_INIT, ['humhub\modules\cfiles\Events', 'onSpaceMenuInit']],
        [ProfileMenu::class, ProfileMenu::EVENT_INIT, ['humhub\modules\cfiles\Events', 'onProfileMenuInit']],
        [IntegrityController::class, IntegrityController::EVENT_ON_RUN, ['humhub\modules\cfiles\Events', 'onIntegrityCheck']],
        [FileController::class, FileController::EVENT_AFTER_ACTION, ['humhub\modules\cfiles\Events', 'onAfterFileAction']],
        [ContentContainerActiveRecord::class, ContentContainerActiveRecord::EVENT_AFTER_INSERT, ['humhub\modules\cfiles\Events', 'onContentContainerActiveRecordInsert']],
        [ContentContainerModuleState::class, ContentContainerModuleState::EVENT_AFTER_INSERT, ['humhub\modules\cfiles\Events', 'onContentContainerModuleStateInsert']],
        ['humhub\modules\rest\Module', 'restApiAddRules', ['humhub\modules\cfiles\Events', 'onRestApiAddRules']],
        [Application::class, Application::EVENT_BEFORE_ACTION, ['humhub\modules\cfiles\Events', 'onBeforeConsoleAction']],
    ]
];
?>