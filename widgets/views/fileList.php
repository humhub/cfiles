<?php

use humhub\modules\cfiles\models\File;
use humhub\modules\file\libs\FileHelper;
use humhub\widgets\LinkPager;
use yii\helpers\Html;
use humhub\modules\cfiles\widgets\FileSystemItem;

/* @var $itemsInFolder boolean */
/* @var $itemsSelectable boolean */
/* @var $canWrite boolean */
/* @var $folder \humhub\modules\cfiles\models\Folder */
/* @var $rows \humhub\modules\cfiles\models\rows\AbstractFileSystemItemRow[] */
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

                <th class="text-left"><?= Yii::t('CfilesModule.base', 'Name'); ?></th>

                <?php if (!$folder->isAllPostedFiles()): // Files currently have no content object but the Post they may be connected to.  ?>
                    <th class="hidden-xxs"></th>
                <?php endif ?>

                <th class="hidden-xs text-right"><?= Yii::t('CfilesModule.base', 'Size'); ?></th>
                <th class="hidden-xxs text-right"><?= Yii::t('CfilesModule.base', 'Updated'); ?></th>

                <?php if (!$folder->isAllPostedFiles()): // Files currently have no content object but the Post they may be connected to.  ?>
                    <th class="text-right"><?= Yii::t('CfilesModule.base', 'Likes/Comments'); ?></th>
                <?php endif; ?>

                <th class="hidden-xxs text-right"><?= Yii::t('CfilesModule.base', 'Creator'); ?></th>
            </tr>
            </thead>

            <?php foreach ($rows as $row) : ?>
                <?= FileSystemItem::widget([
                    'row' => $row,
                    'canWrite' => $canWrite,
                    'itemsSelectable' => $itemsSelectable
                ]);
                ?>
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