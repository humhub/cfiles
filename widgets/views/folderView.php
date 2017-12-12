<?php

use humhub\modules\cfiles\widgets\BreadcrumbBar;
use humhub\modules\cfiles\widgets\FileListMenu;
use humhub\modules\cfiles\widgets\FileList;
use humhub\modules\cfiles\widgets\FileSelectionMenu;
use humhub\modules\file\widgets\UploadProgress;
use yii\helpers\Html;

/* @var $this humhub\components\View */
/* @var $contentContainer humhub\components\View */
/* @var $folder humhub\modules\cfiles\models\Folder */
?>

<?= Html::beginTag('div', $options) ?>

<?= BreadcrumbBar::widget(['folder' => $folder, 'contentContainer' => $contentContainer]) ?>

<?= UploadProgress::widget(['id' => 'cfiles_progress']) ?>

<?= FileListMenu::widget([
    'folder' => $folder,
    'contentContainer' => $contentContainer,
]) ?>

<div id="fileList">
    <?= FileList::widget([
        'folder' => $folder,
        'contentContainer' => $contentContainer,
    ])?>
</div>
<?= Html::endTag('div') ?>