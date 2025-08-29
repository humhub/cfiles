<?php

use humhub\helpers\Html;
use humhub\modules\content\widgets\richtext\RichText;
use humhub\modules\file\libs\FileHelper;


/* @var $previewImage \humhub\modules\file\converter\PreviewImage */
/* @var $cFile \humhub\modules\cfiles\models\File */
/* @var $file  \humhub\modules\file\models\File */
/* @var $fileSize integer */
/* @var $folderUrl string|null */

?>

<?php if ($previewImage->applyFile($file)): ?>
<div class="float-start">
    <?= $previewImage->renderGalleryLink(['style' => 'padding-right:12px']); ?>
</div>
<?php endif; ?>

<strong><?= FileHelper::createLink($file, null, ['style' => 'text-decoration: underline']); ?></strong><br>
<small><?= Yii::t('CfilesModule.base', 'Size: {size}', ['size' => Yii::$app->formatter->asShortSize($fileSize, 1)]); ?></small><br>

<?php if (!empty($cFile->description)): ?>
    <br>
    <div data-ui-markdown>
        <?= RichText::convert($cFile->description, RichText::FORMAT_HTML) ?>
    </div>
<?php endif; ?>

<br>

<?php if ($folderUrl) : ?>
    <?= Html::a(Yii::t('CfilesModule.base', 'Open file folder'), $folderUrl, ['class' => 'btn btn-sm btn-light', 'data-ui-loader' => '']); ?>
<?php endif; ?>

<div class="clearfix"></div>
