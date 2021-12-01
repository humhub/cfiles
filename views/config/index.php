<?php

use humhub\modules\cfiles\models\ConfigureForm;
use humhub\widgets\Button;
use yii\bootstrap\ActiveForm;

/* @var $model ConfigureForm */
?>

<div class="panel panel-default">

    <div class="panel-heading"><?= Yii::t('CfilesModule.base', '<strong>Files</strong> module configuration'); ?></div>

    <div class="panel-body">
        <?php $form = ActiveForm::begin(['id' => 'configure-form']); ?>

            <?= $form->field($model, 'disableZipSupport')->checkbox(null, false); ?>

            <?= $form->field($model, 'displayDownloadCount')->checkbox(null, false); ?>

        <?= Button::save()->submit() ?>
        <?php ActiveForm::end(); ?>
    </div>
</div>
