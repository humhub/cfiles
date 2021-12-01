<?php

use humhub\modules\cfiles\models\rows\FileSystemItemRow;
use humhub\widgets\LinkPager;
use yii\helpers\Html;
use humhub\modules\cfiles\widgets\FileSystemItem;

/* @var $itemsInFolder boolean */
/* @var $itemsSelectable boolean */
/* @var $canWrite boolean */
/* @var $folder \humhub\modules\cfiles\models\Folder */
/* @var $rows \humhub\modules\cfiles\models\rows\AbstractFileSystemItemRow[] */
/* @var $sort string */
/* @var $order string*/
/* @var $pagination \yii\data\Pagination */

?>
<?php if ($itemsInFolder) : ?>
    <div class="table-responsive">
        <table id="bs-table" class="table table-hover">
            <thead>
            <tr>
                <?php if ($itemsSelectable): ?>
                    <th class="text-center" style="width:38px;">
                        <?= Html::checkbox('allchk', false, ['class' => 'allselect']); ?>
                    </th>
                <?php endif; ?>

                <th class="text-left" data-ui-sort="<?= FileSystemItemRow::ORDER_TYPE_NAME ?>"  <?= $sort === FileSystemItemRow::ORDER_TYPE_NAME ? 'data-ui-order="'.Html::encode($order).'"' : '' ?>>
                    <?= Yii::t('CfilesModule.base', 'Name'); ?>
                </th>


                <th class="hidden-xs"></th>

                <th class="hidden-xs text-right" data-ui-sort="<?= FileSystemItemRow::ORDER_TYPE_SIZE ?>"  <?= $sort === FileSystemItemRow::ORDER_TYPE_SIZE ? 'data-ui-order="'.Html::encode($order).'"' : '' ?>><?= Yii::t('CfilesModule.base', 'Size'); ?></th>
                <th class="hidden-xxs text-right"  data-ui-sort="<?= FileSystemItemRow::ORDER_TYPE_UPDATED_AT ?>" <?= $sort === FileSystemItemRow::ORDER_TYPE_UPDATED_AT ? 'data-ui-order="'.Html::encode($order).'"' : '' ?>><?= Yii::t('CfilesModule.base', 'Updated'); ?></th>
                <?php if(Yii::$app->getModule('cfiles')->settings->get('displayDownloadCount')): ?>
                    <th class="hidden-xs text-right" data-ui-sort="<?= FileSystemItemRow::ORDER_TYPE_DOWNLOAD_COUNT ?>" <?= $sort === FileSystemItemRow::ORDER_TYPE_DOWNLOAD_COUNT ? 'data-ui-order="'.Html::encode($order).'"' : '' ?>><?= Yii::t('CfilesModule.base', 'Downloads'); ?></th>
                <?php endif; ?>

                <?php if (!$folder->isAllPostedFiles()): // Files currently have no content object but the Post they may be connected to.  ?>
                    <th class="text-right"><?= Yii::t('CfilesModule.base', 'Likes/Comments'); ?></th>
                <?php endif; ?>

                <th class="hidden-xxs text-right"><?= Yii::t('CfilesModule.base', 'Creator'); ?></th>
                <th class="file-actions"></th>
            </tr>
            </thead>

            <?php foreach ($rows as $row) : ?>
                <?= FileSystemItem::widget([
                    'folder' => $folder,
                    'row' => $row,
                    'itemsSelectable' => $itemsSelectable
                ]); ?>
            <?php endforeach; ?>

        </table>
        <?php if ($pagination) : ?>
            <div class="text-center">
                <?= LinkPager::widget(['pagination' => $pagination]); ?>
            </div>
        <?php endif; ?>
    </div>
<?php else : ?>
    <br/>
    <div class="folderEmptyMessage">
        <div class="panel">
            <div class="panel-body">
                <p>
                    <strong><?= Yii::t('CfilesModule.base', 'This folder is empty.'); ?></strong>
                </p>
                <?php if ($folder->isAllPostedFiles()): ?>
                    <?= Yii::t('CfilesModule.base', 'Upload files to the stream to fill this folder.'); ?>
                <?php elseif ($canWrite): ?>
                    <?= Yii::t('CfilesModule.base', 'Upload files or create a subfolder with the buttons on the top.'); ?>
                <?php else: ?>
                    <?= Yii::t('CfilesModule.base', 'Unfortunately you have no permission to upload/edit files.'); ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
<?php endif; ?>