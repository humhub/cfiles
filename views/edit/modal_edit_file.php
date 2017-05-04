<?php


use humhub\compat\CActiveForm;
?>

<?php \humhub\widgets\ModalDialog::begin([
    'header' =>  Yii::t('CfilesModule.base', '<strong>Edit</strong> file'),
    'animation' => 'fadeIn',
    'size' => 'small']) ?>

    <?php $form = CActiveForm::begin(); ?>

        <div class="modal-body">
            <?= $form->field($file, 'description'); ?>
        </div>

        <div class="modal-footer">
            <button href="#" class="btn btn-primary" data-action-click="ui.modal.submit" data-ui-loader type="submit"
               data-action-url="<?= $contentContainer->createUrl('/cfiles/edit/file', ['fid' => $currentFolderId, 'id' => $file->getItemId(), 'fromWall' => $fromWall]) ?>">
                   <?= Yii::t('CfilesModule.base', 'Save'); ?>
            </button>
            <button type="button" class="btn btn-primary" data-dismiss="modal">
                <?= Yii::t('CfilesModule.base', 'Close'); ?>
            </button>
        </div>
    <?php CActiveForm::end() ?>

<?php \humhub\widgets\ModalDialog::end() ?>