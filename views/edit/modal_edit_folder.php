<?php

use yii\bootstrap\ActiveForm;
use yii\helpers\Html;

$header = ($folder->isNewRecord) 
        ? Yii::t('CfilesModule.base', '<strong>Create</strong> folder') 
        : Yii::t('CfilesModule.base', '<strong>Edit</strong> folder');

$submitUrl = $contentContainer->createUrl('/cfiles/edit/folder', ['fid' => $currentFolderId, 'id' => $folder->getItemId()]);
?>

<?php \humhub\widgets\ModalDialog::begin([
    'header' =>  $header,
    'animation' => 'fadeIn',
    'size' => 'small']) ?>

        <?php $form = ActiveForm::begin(); ?>
            <div class="modal-body">
                <br />
                <?= $form->field($folder, 'title'); ?>
                <?= $form->field($folder, 'description'); ?>
                <?= $form->field($folder, 'visibility')->checkbox() ?>
            </div>

            <div class="modal-footer">
                <?= \humhub\widgets\ModalButton::submitModal($submitUrl)?>
                <?= \humhub\widgets\ModalButton::cancel() ?>
            </div>
        <?php ActiveForm::end() ?>

<?php \humhub\widgets\ModalDialog::end() ?>