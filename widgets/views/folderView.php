<?php

use yii\helpers\Html;

/* @var $this humhub\components\View */
/* @var $contentContainer humhub\components\View */
/* @var $canWrite boolean */
/* @var $folder humhub\modules\cfiles\models\Folder */
?>

<?= Html::beginTag('div', $options) ?>

<?= \humhub\modules\cfiles\widgets\FileListBar::widget(['folder' => $folder, 'contentContainer' => $contentContainer]) ?>

<?= \humhub\modules\file\widgets\UploadProgress::widget(['id' => 'cfiles_progress']) ?>

<?=
humhub\modules\cfiles\widgets\FileListMenu::widget([
    'folder' => $folder,
    'contentContainer' => $contentContainer,
    'canWrite' => $canWrite,
])
?>

<div id="fileList">
    <?=
    humhub\modules\cfiles\widgets\FileList::widget([
        'folder' => $folder,
        'contentContainer' => $contentContainer,
        'canWrite' => $canWrite
    ])
    ?>
</div>

<?= Html::endTag('div') ?>