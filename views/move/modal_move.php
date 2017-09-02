<?php

use humhub\modules\cfiles\models\forms\MoveForm;
use humhub\widgets\ActiveForm;
use humhub\widgets\ModalButton;
use humhub\widgets\ModalDialog;

/* @var $this \humhub\components\View */
/* @var $model MoveForm */
?>

<?php ModalDialog::begin(['header' => Yii::t('CfilesModule.base', '<strong>Move</strong> files'), 'size' => 'small']); ?>

    <?php $form = ActiveForm::begin(); ?>
        <div class="modal-body">
            <br>

            <?= $this->render('directory_tree', ['root' => $model->root]); ?>

            <?= $form->field($model, 'destId')->hiddenInput(['id' => 'input-hidden-selectedFolder'])->label(false); ?>

            <?php if (is_array($selectedItems)) {
                foreach ($selectedItems as $index => $item) {
                    echo "<input class='input-hidden-selectedItem' type='hidden' name='selected[]' value='$item'/>";
                }
            }
            ?>
            
        </div>

        <div class="modal-footer">
            <?= ModalButton::submitModal($model->getMoveUrl()); ?>
            <?= ModalButton::cancel(); ?>
        </div>
    <?php ActiveForm::end(); ?>

<?php ModalDialog::end(); ?>
