<?php

use humhub\modules\cfiles\models\forms\VersionForm;
use humhub\modules\ui\form\widgets\ActiveForm;
use humhub\widgets\ModalButton;
use humhub\widgets\ModalDialog;

/* @var $model VersionForm */
?>

<?php ModalDialog::begin(['header' => Yii::t('CfilesModule.base', '<strong>File</strong> versions')]) ?>

    <?php $form = ActiveForm::begin(); ?>
        <div class="modal-body">
            <?= $form->field($model, 'version')->radioList($model->versions) ?>
        </div>

        <div class="modal-footer">
            <?= ModalButton::submitModal(null, Yii::t('CfilesModule.base', 'Switch'))?>
            <?= ModalButton::cancel() ?>
        </div>
    <?php ActiveForm::end() ?>

<?php ModalDialog::end()?>