<?php

use yii\helpers\Html;
use humhub\modules\cfiles\widgets\FolderView;

/* @var $folder humhub\modules\cfiles\models\Folder */
/* @var $contentContainer humhub\components\View */
/* @var $canWrite boolean */

$bundle = \humhub\modules\cfiles\assets\Assets::register($this);

$this->registerJsConfig('cfiles', [
    'text' => [
        'confirm.delete' => Yii::t('CfilesModule.base', 'Do you really want to delete this {number} item(s) with all subcontent?'),
        'confirm.delete.header' => Yii::t('CfilesModule.base', '<strong>Confirm</strong> delete file'),
        'confirm.delete.confirmText' => Yii::t('CfilesModule.base', 'Delete')
    ],
    'showUrlModal' => [
        'head' => Yii::t('CfilesModule.base', '<strong>File</strong> url'),
        'headFile' => Yii::t('CfilesModule.base', '<strong>File</strong> download url'),
        'headFolder' => Yii::t('CfilesModule.base', '<strong>Folder</strong> url'),
        'info' => Yii::t('base', 'Copy to clipboard'),
        'buttonClose' => Yii::t('base', 'Close'),
    ],
    'reloadEntryUrl' => $contentContainer->createUrl('/cfiles/browse/load-entry'),
]);
?>

<?= Html::beginForm(null, null, ['data-target' => '#globalModal', 'id' => 'cfiles-form']); ?>
    <div id="cfiles-container" class="panel panel-default cfiles-content">

        <div class="panel-body">

            <?=  FolderView::widget([
                'contentContainer' => $contentContainer,
                'folder' => $folder
            ])?>

        </div>
    </div>
<?= Html::endForm(); ?>