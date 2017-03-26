<?php

use yii\helpers\Html;
use humhub\compat\CActiveForm;

$header = ($folder->isNewRecord) 
        ? Yii::t('CfilesModule.base', '<strong>Create</strong> folder') 
        : Yii::t('CfilesModule.base', '<strong>Edit</strong> folder');

?>

<?php \humhub\widgets\ModalDialog::begin([
    'header' =>  $header,
    'animation' => 'fadeIn',
    'size' => 'small']) ?>

        <?php $form = CActiveForm::begin(); ?>
            <div class="modal-body">
                <br />
                <?= $form->field($folder, 'title'); ?>
                <?= $form->field($folder, 'description'); ?>
            </div>

            <div class="modal-footer">
                <button href="#" class="btn btn-primary"
                   data-ui-loader
                   data-action-click="ui.modal.submit"
                   type="submit"
                   data-action-url="<?= $contentContainer->createUrl('/cfiles/edit/folder', ['fid' => $currentFolderId, 'id' => $folder->getItemId()]) ?>">
                       <?= Yii::t('CfilesModule.base', 'Save'); ?>
                </button>
                <button type="button" class="btn btn-primary" data-dismiss="modal">
                    <?= Yii::t('CfilesModule.base', 'Close'); ?>
                </button>

            </div>
        <?php CActiveForm::end() ?>

<?php \humhub\widgets\ModalDialog::end() ?>