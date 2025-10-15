<?php

use humhub\modules\cfiles\models\ConfigureForm;
use humhub\widgets\bootstrap\Button;
use humhub\widgets\form\ActiveForm;
use humhub\widgets\form\ContentHiddenCheckbox;

/* @var $model ConfigureForm */
?>

<div class="panel panel-default">

    <div class="panel-heading"><?= Yii::t('CfilesModule.base', '<strong>Files</strong> module configuration'); ?></div>

    <div class="panel-body">
        <?php $form = ActiveForm::begin(['id' => 'configure-form']); ?>

        <?= $form->field($model, 'disableZipSupport')->checkbox(); ?>

        <?= $form->field($model, 'displayDownloadCount')->checkbox(); ?>

        <?= $form->field($model, 'contentHiddenDefault')->widget(ContentHiddenCheckbox::class, [
            'type' => ContentHiddenCheckbox::TYPE_GLOBAL,
        ]); ?>

        <?= Button::save()->submit() ?>
        <?php ActiveForm::end(); ?>
    </div>
</div>
