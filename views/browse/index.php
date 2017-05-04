<?php

use yii\helpers\Html;

/* @var $folder humhub\modules\cfiles\models\Folder */
/* @var $contentContainer humhub\components\View */
/* @var $canWrite boolean */

$bundle = \humhub\modules\cfiles\assets\Assets::register($this);

$this->registerJsConfig('cfiles', [
    'text' => [
        'confirm.delete' => Yii::t('CfilesModule.base', 'Do you really want to delete this {number} item(s) with all subcontent?'),
        'confirm.delete.header' => Yii::t('CfilesModule.base', '<strong>Confirm</strong> delete file'),
        'confirm.delete.confirmText' => Yii::t('CfilesModule.base', 'Delete')
    ]
]);
?>

<?= Html::beginForm(null, null, ['data-target' => '#globalModal', 'id' => 'cfiles-form']); ?>
<div id="cfiles-container" class="panel panel-default cfiles-content">

    <div class="panel-body">

        <?=
        humhub\modules\cfiles\widgets\FolderView::widget([
            'contentContainer' => $contentContainer,
            'folder' => $folder,
            'canWrite' => $canWrite])
        ?>

    </div>
</div>
<?php echo Html::endForm(); ?>

<?= humhub\modules\cfiles\widgets\FileListContextMenu::widget(['folder' => $folder, 'canWrite' => $canWrite]); ?>