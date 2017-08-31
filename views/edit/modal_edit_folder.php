<?php

use humhub\widgets\ModalButton;
use humhub\widgets\ModalDialog;
use yii\bootstrap\ActiveForm;

/* @var $folder \humhub\modules\cfiles\models\Folder */
/* @var $submitUrl string */

$header = ($folder->isNewRecord) 
        ? Yii::t('CfilesModule.base', '<strong>Create</strong> folder') 
        : Yii::t('CfilesModule.base', '<strong>Edit</strong> folder');

?>

<?php ModalDialog::begin([
    'header' =>  $header,
    'animation' => 'fadeIn',
    'size' => 'small']) ?>

        <?php $form = ActiveForm::begin(); ?>
            <div class="modal-body">
                <br />
                <?= $form->field($folder, 'title'); ?>
                <?= $form->field($folder, 'description'); ?>
                <?= $form->field($folder, 'visibility')->checkbox(['disabled' => !$folder->isRoot() && $folder->parentFolder->content->isPrivate()]) ?>
            </div>

            <div class="modal-footer">
                <?= ModalButton::submitModal($submitUrl)?>
                <?= ModalButton::cancel() ?>
            </div>
        <?php ActiveForm::end() ?>

<?php ModalDialog::end() ?>