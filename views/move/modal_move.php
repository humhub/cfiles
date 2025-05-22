<?php

use humhub\components\View;
use humhub\modules\cfiles\models\forms\MoveForm;
use humhub\widgets\modal\Modal;
use humhub\widgets\modal\ModalButton;

/* @var $this View */
/* @var $model MoveForm */
?>

<?php $form = Modal::beginFormDialog([
    'title' => Yii::t('CfilesModule.base', '<strong>Move</strong> files'),
    'footer' => ModalButton::cancel() . ' ' . ModalButton::save(null, $model->getMoveUrl()),
]) ?>

    <?= $this->render('directory_tree', ['root' => $model->root]) ?>

    <?= $form->field($model, 'destId')->hiddenInput(['id' => 'input-hidden-selectedFolder'])->label(false) ?>

    <?php foreach ($model->selection as $index => $item) : ?>
        <input class='input-hidden-selectedItem' type='hidden' name='selection[]' value='<?= $item ?>' />
    <?php endforeach; ?>

<?php Modal::endFormDialog()?>
