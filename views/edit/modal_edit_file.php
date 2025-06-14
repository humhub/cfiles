<?php


use humhub\modules\content\widgets\richtext\RichTextField;
use humhub\widgets\form\ContentHiddenCheckbox;
use humhub\widgets\form\ContentVisibilitySelect;
use humhub\widgets\modal\Modal;
use humhub\widgets\modal\ModalButton;

/* @var $file \humhub\modules\cfiles\models\File */
/* @var $submitUrl string */

?>

<?php $form = Modal::beginFormDialog([
    'title' => Yii::t('CfilesModule.base', '<strong>Edit</strong> file'),
    'footer' => ModalButton::cancel() . ' ' . ModalButton::save(null, $submitUrl),
]) ?>

    <?= $form->field($file->baseFile, 'file_name')->textInput(['autofocus' => '']) ?>
    <?= $form->field($file, 'description')->widget(RichTextField::class) ?>
    <?= $form->field($file, 'visibility')->widget(ContentVisibilitySelect::class, ['readonly' => $file->parentFolder->content->isPrivate()]) ?>
    <?= $form->field($file, 'hidden')->widget(ContentHiddenCheckbox::class, []) ?>
    <?= $form->field($file, 'download_count')->staticControl(['style' => 'display:inline']) ?>

<?php Modal::endFormDialog() ?>
