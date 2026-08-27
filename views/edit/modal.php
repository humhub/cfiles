<?php

use humhub\modules\cfiles\assets\CfilesVueAsset;
use humhub\widgets\modal\Modal;
use humhub\widgets\VueComponent;

/* @var $this humhub\components\View */
/* @var $item array the serialized file or folder */

?>

<?php Modal::beginDialog([
    'title' => $item['type'] === 'folder'
        ? Yii::t('CfilesModule.base', '<strong>Edit</strong> folder')
        : Yii::t('CfilesModule.base', '<strong>Edit</strong> file'),
]) ?>

    <?= VueComponent::widget([
        'name' => 'CfilesItemForm',
        'assetBundle' => CfilesVueAsset::class,
        'props' => [
            'item' => $item,
            // The stream has no list to refresh afterwards, so the form closes the modal and
            // reloads the entry instead of emitting into a browser it is not part of.
            'standalone' => true,
        ],
    ]) ?>

<?php Modal::endDialog() ?>
