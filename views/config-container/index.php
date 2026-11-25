<?php

use humhub\modules\cfiles\models\ConfigureForm;
use humhub\widgets\bootstrap\Button;
use humhub\widgets\form\ActiveForm;
use humhub\widgets\form\ContentHiddenCheckbox;

/* @var $model ConfigureForm */
?>

<div class="card content-card">

    <div class="card-header"><div class="card-title"><?= Yii::t('CfilesModule.base', '<strong>Files</strong> module configuration'); ?></div></div>

    <div class="card-body">
        <?php $form = ActiveForm::begin(['id' => 'configure-form']); ?>

        <?= $form->field($model, 'contentHiddenDefault')->widget(ContentHiddenCheckbox::class, [
            'type' => ContentHiddenCheckbox::TYPE_CONTENTCONTAINER,
        ]); ?>

        <?= Button::save()->submit() ?>
        <?php ActiveForm::end(); ?>
    </div>
</div>
