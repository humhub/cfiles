<?php


use humhub\modules\content\widgets\richtext\RichTextField;
use humhub\modules\topic\widgets\TopicPicker;
use humhub\modules\ui\form\widgets\ContentVisibilitySelect;
use humhub\widgets\ModalButton;
use humhub\widgets\ModalDialog;
use yii\bootstrap\ActiveForm;

/* @var $file \humhub\modules\cfiles\models\File */
/* @var $submitUrl string */

?>

<?php ModalDialog::begin([
    'header' =>  Yii::t('CfilesModule.base', '<strong>Edit</strong> file'),
    'animation' => 'fadeIn',
    'size' => 'small']) ?>

    <?php $form = ActiveForm::begin(); ?>

        <div class="modal-body">
            <?= $form->field($file->baseFile, 'file_name'); ?>
            <?= $form->field($file, 'description')->widget(RichTextField::class); ?>
            <?= $form->field($file, 'topics')->widget(TopicPicker::class, ['contentContainer' => $file->content->container])->label(false); ?>
            <?= $form->field($file, 'visibility')->widget(ContentVisibilitySelect::class, ['readonly' => $file->parentFolder->content->isPrivate()]); ?>
            <?= $form->field($file, 'download_count')->staticControl(['style' => 'display:inline']); ?>
        </div>

        <div class="modal-footer">
            <?= ModalButton::submitModal($submitUrl) ?>
            <?= ModalButton::cancel() ?>
        </div>
    <?php ActiveForm::end() ?>

<?php ModalDialog::end() ?>