<?php

use humhub\modules\cfiles\assets\CfilesVueAsset;
use humhub\widgets\VueComponent;

/* @var $this humhub\components\View */
/* @var $contentContainer humhub\modules\content\components\ContentContainerActiveRecord */
/* @var $folder humhub\modules\cfiles\models\Folder */
/* @var $listing array the first page, embedded so the island paints without a request */
/* @var $canWrite bool */
/* @var $editItem string|null */
/* @var $createHandlersHtml string */
/* @var $filters array the `FilterBar` definitions ({@see \humhub\modules\cfiles\components\FolderList::definitions()}) */
/* @var $initialFilters array<string, string> the filter values the first page was built with, from the page URL */
/* @var $settingsUrl string|null the container's cfiles settings page, or null for those who may not change them */

?>
<?= VueComponent::widget([
    'name' => 'CfilesFileBrowser',
    'assetBundle' => CfilesVueAsset::class,
    'options' => [
        'id' => 'cfiles-container',
        // A custom element is inline by default.
        'class' => 'cfiles-content d-block',
    ],
    'props' => [
        'listing' => $listing,
        'canWrite' => $canWrite,
        'browseUrl' => $contentContainer->createUrl('/cfiles/browse/index'),
        'contentContainerId' => $contentContainer->contentcontainer_id,
        'editKey' => $editItem,
        'createHandlersHtml' => $createHandlersHtml,
        'filters' => $filters,
        'initialFilters' => $initialFilters,
        'settingsUrl' => $settingsUrl,
    ],
]) ?>
