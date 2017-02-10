<?php

use humhub\modules\space\widgets\Menu;
use humhub\modules\user\widgets\ProfileMenu;

return [
    'id' => 'cfiles',
    'class' => 'humhub\modules\cfiles\Module',
    'namespace' => 'humhub\modules\cfiles',
    'events' => [
        [Menu::className(), Menu::EVENT_INIT, ['humhub\modules\cfiles\Events', 'onSpaceMenuInit']],
        [ProfileMenu::className(), ProfileMenu::EVENT_INIT, ['humhub\modules\cfiles\Events', 'onProfileMenuInit']]
    ]
];
?>