<?php

use humhub\modules\cfiles\assets\CfilesVueAsset;
use humhub\widgets\VueComponent;

/* @var $this humhub\components\View */
/* @var $contentContainer humhub\modules\content\components\ContentContainerActiveRecord */
/* @var $folder humhub\modules\cfiles\models\Folder */
/* @var $listing array the first page, embedded so the island paints without a request */
/* @var $canWrite bool */

?>
<?= VueComponent::widget([
    'name' => 'CfilesFileBrowser',
    'assetBundle' => CfilesVueAsset::class,
    'options' => [
        'id' => 'cfiles-container',
        // A custom element is inline by default, and this one is a panel.
        'class' => 'panel panel-default cfiles-content d-block',
    ],
    'props' => [
        'listing' => $listing,
        'canWrite' => $canWrite,
        'browseUrl' => $contentContainer->createUrl('/cfiles/browse/index'),
    ],
]) ?>
