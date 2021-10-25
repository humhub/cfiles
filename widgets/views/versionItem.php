<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2021 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

use humhub\libs\Html;
use humhub\modules\user\models\User;

/* @var array $options */
/* @var User $user */
/* @var string $date */
/* @var string $size */
/* @var string|bool $revertUrl */
/* @var string $downloadUrl */
/* @var string|bool $deleteUrl */
?>
<?= Html::beginTag('tr', $options) ?>
    <td><?= Yii::$app->formatter->asDatetime($date, 'short') ?></td>
    <td><?= Html::encode($user->displayName) ?></td>
    <td class="text-right"><?= Yii::$app->formatter->asShortSize($size, 1) ?></td>
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
