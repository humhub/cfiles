<?php

use yii\helpers\Html;
use humhub\modules\cfiles\widgets\FileSystemItem;
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
                    <th class="hidden-xs text-right"><?= Yii::t('CfilesModule.base', 'Size'); ?></th>
                    <th class="hidden-xxs text-right"><?= Yii::t('CfilesModule.base', 'Updated'); ?></th>
                    <?php if (!$folder->isAllPostedFiles()): // Files currently have no content object but the Post they may be connected to.  ?>
                        <th class="text-right"><?= Yii::t('CfilesModule.base', 'Likes/Comments'); ?></th>
                    <?php endif; ?>
                    <th class="hidden-xxs text-right"><?= Yii::t('CfilesModule.base', 'Creator'); ?></th>
                </tr>
            </thead>

            <?php foreach ((array_key_exists('specialFolders', $items) ? $items['specialFolders'] : []) as $specialFolder) : ?>
                <?=
                FileSystemItem::widget([
                    'parentFolderId' => $folder->id,
                    'canWrite' => $canWrite,
                    'socialActionsAvailable' => false,
                    'selectable' => false,
                    'item' => $specialFolder,
                    'creator' => false, // do not display creator / editr of automatically generated folders
                    'editor' => false, // do not display creator / editr of automatically generated folders
                    'updatedAt' => $specialFolder->isAllPostedFiles() ? "" : $specialFolder->content->updated_at, // do not display timestamp of all posted files folder      
                ]);
                ?>
            <?php endforeach; ?>
            <?php foreach ((array_key_exists('folders', $items) ? $items['folders'] : []) as $folderItem) : ?>
                <?=
                FileSystemItem::widget([
                    'parentFolderId' => $folder->id,
                    'canWrite' => $canWrite,
                    'contentObject' => $folderItem,
                    'item' => $folderItem
                ]);
                ?>
            <?php endforeach; ?>
            <?php foreach ((array_key_exists('files', $items) ? $items['files'] : []) as $file) : ?>
                <?=
                FileSystemItem::widget([
                    'parentFolderId' => $folder->id,
                    'canWrite' => $canWrite,
                    'contentObject' => $file,
                    'item' => $file
                ]);
                ?>
            <?php endforeach; ?>
            <?php foreach ((array_key_exists('postedFiles', $items) ? $items['postedFiles'] : []) as $file) : ?>
                <?=
                FileSystemItem::widget([
                    'parentFolderId' => $folder->id,
                    'type' => \humhub\modules\cfiles\models\File::getItemTypeByExt($file->getExtension()),
                    'columns' => $itemsSelectable ? [
                        'select',
                        'title',
                        'size',
                        'timestamp',
                        'creator'
                            ] : [
                        'title',
                        'size',
                        'timestamp',
                        'creator'
                            ],
                    'id' => 'baseFile_' . $file->id,
                    'downloadUrl' => $file->getUrl() . '&' . http_build_query(['download' => true]),
                    'url' => $file->getUrl(),
                    'wallUrl' => \humhub\modules\cfiles\models\File::getBasePost($file)->getUrl(),
                    'canWrite' => $canWrite,
                    'baseFile' => $file,
                    'iconClass' => \humhub\modules\cfiles\models\File::getIconClassByExt($file->getExtension()),
                    'title' => $file->file_name,
                    'size' => $file->size,
                    'creator' => \humhub\modules\cfiles\models\File::getUserById($file->created_by),
                    'editor' => \humhub\modules\cfiles\models\File::getUserById($file->updated_by),
                    'updatedAt' => $file->updated_at
                ]);
                ?>
            <?php endforeach; ?>
        </table>
    </div>
<?php else : ?>
    <br />
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