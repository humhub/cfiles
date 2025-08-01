<?php

use humhub\libs\Html;
use humhub\modules\cfiles\models\forms\MoveForm;
use humhub\modules\ui\form\widgets\ActiveForm;
use humhub\widgets\ModalButton;
use humhub\widgets\ModalDialog;

/* @var $this \humhub\modules\ui\view\components\View */
/* @var $model MoveForm */
?>

<?php ModalDialog::begin(['header' => Yii::t('CfilesModule.base', '<strong>Move</strong> files'), 'size' => 'small']) ?>

    <?php $form = ActiveForm::begin(); ?>
        <div class="modal-body">
            <br />

            <?= $this->render('directory_tree', ['root' => $model->root]) ?>

            <?= $form->field($model, 'destId')->hiddenInput(['id' => 'input-hidden-selectedFolder'])->label(false) ?>

            <?php foreach ($model->selection as $index => $item) : ?>
                <?= Html::hiddenInput('selection[]', $item, ['class' => 'input-hidden-selectedItem']) ?>
            <?php endforeach; ?>
            
        </div>

        <div class="modal-footer">
            <?= ModalButton::cancel() ?>
            <?php if ($moveToContainerUrl = $model->getMoveToContainerUrl()) : ?>
                <?= ModalButton::info(Yii::t('CfilesModule.base', 'Move to another Space'))->action('ui.modal.load', $moveToContainerUrl) ?>
            <?php endif; ?>
            <?= ModalButton::submitModal($model->getMoveUrl())?>
        </div>
    <?php ActiveForm::end() ?>

<?php ModalDialog::end()?>
