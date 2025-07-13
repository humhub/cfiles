<?php

use humhub\widgets\form\ContentHiddenCheckbox;
use humhub\widgets\form\ContentVisibilitySelect;
use humhub\widgets\modal\Modal;
use humhub\widgets\modal\ModalButton;

/* @var $folder \humhub\modules\cfiles\models\Folder */
/* @var $submitUrl string */

$header = ($folder->isNewRecord)
    ? Yii::t('CfilesModule.base', '<strong>Create</strong> folder')
    : Yii::t('CfilesModule.base', '<strong>Edit</strong> folder');
?>

<?php $form = Modal::beginFormDialog([
    'title' => $header,
    'footer' => ModalButton::cancel() . ' ' . ModalButton::save()->submit($submitUrl),
]) ?>

    <?= $form->field($folder, 'title')->textInput(['autofocus' => '']) ?>
    <?= $form->field($folder, 'description') ?>
    <?= $form->field($folder, 'visibility')->widget(ContentVisibilitySelect::class, ['readonly' => !$folder->isRoot() && $folder->parentFolder->content->isPrivate()]) ?>
    <?= $form->field($folder, 'hidden')->widget(ContentHiddenCheckbox::class) ?>

<?php Modal::endFormDialog() ?>
