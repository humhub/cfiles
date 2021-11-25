<?php

use humhub\modules\content\widgets\richtext\RichText;
use humhub\modules\file\libs\FileHelper;
use humhub\libs\Html;


/* @var $previewImage \humhub\modules\file\converter\PreviewImage */
/* @var $cFile \humhub\modules\cfiles\models\File */
/* @var $file  \humhub\modules\file\models\File */
/* @var $fileSize integer */
/* @var $folderUrl string */

?>

<?php if ($previewImage->applyFile($file)): ?>
<div class="pull-left">
    <?= $previewImage->renderGalleryLink(['style' => 'padding-right:12px']); ?>
</div>
<?php endif; ?>

<strong><?= FileHelper::createLink($file, null, ['style' => 'text-decoration: underline']); ?></strong><br />
<small><?= Yii::t('CfilesModule.base', 'Size: {size}', ['size' => Yii::$app->formatter->asShortSize($fileSize, 1)]); ?></small><br />

<?php if (!empty($cFile->description)): ?>
    <br />
    <?= RichText::convert($cFile->description, RichText::FORMAT_HTML); ?>
    <br />
<?php endif; ?>

<br />

<?= Html::a(Yii::t('CfilesModule.base', 'Open file folder'), $folderUrl, ['class' => 'btn btn-sm btn-default', 'data-ui-loader' => '']); ?>

<div class="clearfix"></div>