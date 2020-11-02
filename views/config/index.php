<?php

use humhub\modules\cfiles\Module;
use humhub\widgets\Button;
use yii\bootstrap\ActiveForm;
use yii\helpers\Html;

/* @var $model \humhub\modules\cfiles\models\ConfigureForm */
?>

<div class="panel panel-default">

    <div class="panel-heading"><?= Yii::t('CfilesModule.base', '<strong>Files</strong> module configuration'); ?></div>

    <div class="panel-body">
        <?php $form = ActiveForm::begin(['id' => 'configure-form']); ?>

            <?= $form->field($model, 'uploadBehaviour')->radioList([
                    Module::UPLOAD_BEHAVIOUR_INDEX => Yii::t('CfilesModule.base', 'Use of file indexes for already existing files'),
                    Module::UPLOAD_BEHAVIOUR_REPLACE => Yii::t('CfilesModule.base', 'Replace existing files')
            ]); ?>

            <?= $form->field($model, 'disableZipSupport')->checkbox(null, false); ?>

            <?= $form->field($model, 'displayDownloadCount')->checkbox(null, false); ?>

        <?= Button::save()->submit() ?>
        <?php ActiveForm::end(); ?>
    </div>
</div>
