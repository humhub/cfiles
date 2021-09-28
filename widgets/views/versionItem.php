<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2021 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

use humhub\libs\Html;
use humhub\modules\file\models\File as BaseFile;

/* @var array $options */
/* @var BaseFile $file */
/* @var string|bool $revertUrl */
/* @var string $downloadUrl */
/* @var string $deleteUrl */
?>
<?= Html::beginTag('tr', $options) ?>
    <td><?= Yii::$app->formatter->asDatetime($file->created_at, 'short') ?></td>
    <td><?= Html::encode($file->createdBy->displayName) ?></td>
    <td class="text-right"><?= Yii::$app->formatter->asShortSize($file->size, 1) ?></td>
    <td class="text-center">
        <?php if ($revertUrl) : ?>
            <?= Html::a('<i class="fa fa-undo"></i>', $revertUrl, [
                'title' => Yii::t('CfilesModule.base', 'Revert to this version'),
                'data-method' => 'POST',
            ]) ?>
        <?php endif; ?>
        <?= Html::a('<i class="fa fa-cloud-download"></i>', $downloadUrl, [
            'title' => Yii::t('CfilesModule.base', 'Download'),
            'target' => '_blank',
        ]) ?>
        <?php if ($deleteUrl) : ?>
            <?= Html::a('<i class="fa fa-trash"></i>', $deleteUrl, [
                'title' => Yii::t('CfilesModule.base', 'Delete this version!'),
                'data-action-confirm' => Yii::t('CfilesModule.user', 'Are you really sure to delete this version?'),
                'data-action-click' => 'cfiles.deleteVersion',
            ]) ?>
        <?php endif; ?>
    </td>
<?= Html::endTag('tr') ?>
