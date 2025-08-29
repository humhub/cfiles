<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2021 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

use humhub\modules\cfiles\models\forms\VersionForm;
use humhub\modules\cfiles\widgets\VersionsView;
use humhub\widgets\modal\ModalButton;
use humhub\widgets\modal\Modal;

/* @var $model VersionForm */
?>

<?php Modal::beginDialog([
    'title' => Yii::t('CfilesModule.base', '<strong>File</strong> versions'),
    'footer' => ModalButton::cancel(Yii::t('CfilesModule.base', 'Close')),
]) ?>

    <?= VersionsView::widget(['file' => $model->file]) ?>

<?php Modal::endDialog() ?>
