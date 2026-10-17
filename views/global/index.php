<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2026 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

use humhub\components\View;
use humhub\modules\cfiles\assets\CfilesVueAsset;
use humhub\widgets\VueComponent;

/* @var $this View */
/* @var $props array the island's props ({@see \humhub\modules\cfiles\controllers\GlobalController::pageProps()}) */
?>
<?= VueComponent::widget([
    'name' => 'CfilesFileBrowser',
    'assetBundle' => CfilesVueAsset::class,
    'options' => [
        'id' => 'cfiles-global',
        // A custom element is inline by default.
        'class' => 'cfiles-content d-block',
    ],
    'props' => $props,
]) ?>
