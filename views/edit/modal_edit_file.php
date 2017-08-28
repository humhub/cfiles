<?php


use humhub\widgets\ModalButton;
use yii\bootstrap\ActiveForm;
use humhub\widgets\ModalDialog;

/* @var $file \humhub\modules\cfiles\models\File */
/* @var $currentFolderId integer */
/* @var $fromWall boolean */

$submitUrl = $contentContainer->createUrl('/cfiles/edit/file', ['fid' => $currentFolderId, 'id' => $file->getItemId(), 'fromWall' => $fromWall]);
?>

<?php ModalDialog::begin([
    'header' =>  Yii::t('CfilesModule.base', '<strong>Edit</strong> file'),
    'animation' => 'fadeIn',
    'size' => 'small']) ?>

    <?php $form = ActiveForm::begin(); ?>

        <div class="modal-body">
            <?= $form->field($file->baseFile, 'file_name'); ?>
            <?= $form->field($file, 'description'); ?>
            <?= $form->field($file, 'visibility')->checkbox(['disabled' => $file->parentFolder->content->isPrivate()]) ?>
        </div>

        <div class="modal-footer">
            <?= ModalButton::submitModal($submitUrl) ?>
            <?= ModalButton::cancel() ?>
        </div>
    <?php ActiveForm::end() ?>

<?php ModalDialog::end() ?>