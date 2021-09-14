<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2021 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

use humhub\modules\cfiles\models\forms\VersionForm;
use humhub\modules\cfiles\widgets\VersionsView;
use humhub\widgets\ModalButton;
use humhub\widgets\ModalDialog;

/* @var $model VersionForm */
?>

<?php ModalDialog::begin(['header' => Yii::t('CfilesModule.base', '<strong>File</strong> versions')]) ?>

    <div class="modal-body">
        <?= VersionsView::widget(['file' => $model->file]) ?>
    </div>

    <div class="modal-footer">
        <?= ModalButton::cancel(Yii::t('CfilesModule.base', 'Close')) ?>
    </div>

<?php ModalDialog::end()?>