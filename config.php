<?php

use humhub\modules\space\widgets\Menu;
use humhub\modules\user\widgets\ProfileMenu;

return [
    'id' => 'cfiles',
    'class' => 'humhub\modules\cfiles\Module',
    'namespace' => 'humhub\modules\cfiles',
    'events' => array(
        array(
            'class' => Menu::className(),
            'event' => Menu::EVENT_INIT,
            'callback' => array(
                'humhub\modules\cfiles\Events',
                'onSpaceMenuInit'
            )
        ),
        array('class' => ProfileMenu::className(), 'event' => ProfileMenu::EVENT_INIT, 'callback' => array('humhub\modules\cfiles\Events', 'onProfileMenuInit')),
    )
];
?>