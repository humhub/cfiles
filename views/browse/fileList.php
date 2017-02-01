<?php
use yii\helpers\Html;
use humhub\modules\cfiles\controllers\BrowseController;
use humhub\modules\file\models\File;
use humhub\modules\cfiles\widgets\FileSystemItem;
use humhub\models\Setting;
use humhub\modules\like\widgets\LikeLink;
use humhub\modules\comment\widgets\CommentLink;
use humhub\modules\comment\widgets\Comments;

$parentFolderId = null;
$itemsSelectable = ! (Setting::Get('disableZipSupport', 'cfiles') && $currentFolder->isAllPostedFiles());
$itemsInFolder = array_key_exists('specialFolders', $items) && sizeof($items['specialFolders']) > 0 || array_key_exists('folders', $items) && sizeof($items['folders']) > 0 || array_key_exists('files', $items) && sizeof($items['files']) > 0 || array_key_exists('postedFiles', $items) && sizeof($items['postedFiles']) > 0?>

<div class="panel panel-default">
    <div class="panel-head">
        <ol class="breadcrumb" dir="ltr">
        <?php foreach ($crumb as $parentFolder): ?>
            <li><a
                    href="<?php echo $contentContainer->createUrl('/cfiles/browse/'.($parentFolder->isAllPostedFiles() ? 'all-posted-files' : 'index'), ['fid' => $parentFolder->id]); ?>">
            <?php echo $parentFolder->isRoot() ? '<i class="fa fa-home fa-lg fa-fw"></i>' : Html::encode($parentFolder->title); ?></a></li>
            <?php $parentFolderId = $parentFolder->id; ?>
        <?php endforeach; ?>
        </ol>
    </div>
    <?php if(!$currentFolder->isRoot() && !$currentFolder->isAllPostedFiles()): ?>
    <div class="panel-body">
        <div class="cfiles-folder-description"><?php echo $currentFolder->description; ?></div>
        <div class="file-controls">
            <?php echo LikeLink::widget(['object' => $currentFolder]); ?>
            |
            <?php echo CommentLink::widget(['object' => $currentFolder, 'mode' => CommentLink::MODE_POPUP]); ?>
            |
            <a href="<?php echo $currentFolder->getWallUrl(); ?>"><?php echo Yii::t('CfilesModule.base', 'Show on Wall'); ?></a>
        </div>
    </div>
    <?php endif; ?>
</div>

<div id="cfiles-log"></div>

<?php if($itemsInFolder) : ?>
<div class="table-responsive">
    <table id="bs-table" class="table table-hover">
        <thead>
            <tr>
                <?php if($itemsSelectable): ?>
                <th class="text-right">
                    <?php echo Html::checkbox('allchk', false, [ 'class' => 'allselect']); ?></th>
                <?php endif; ?>
                <th class="text-left"><?php echo Yii::t('CfilesModule.base', 'Name'); ?></th>
                <th class="hidden-xs text-right"><?php echo Yii::t('CfilesModule.base', 'Size'); ?></th>
                <th class="hidden-xxs text-right"><?php echo Yii::t('CfilesModule.base', 'Updated'); ?></th>
                <?php if(!$parentFolder->isAllPostedFiles()): // Files currently have no content object but the Post they may be connected to. ?>
                <th class="text-right"><?php echo Yii::t('CfilesModule.base', 'Likes/Comments'); ?></th>
                <?php endif; ?>
                <th class="hidden-xxs text-right"><?php echo Yii::t('CfilesModule.base', 'Creator'); ?></th>
            </tr>
        </thead>
        <tfoot>
            <tr>
                <td colspan="<?php echo $itemsSelectable ? 3 : 2 ?>"></td>
                <td class="hidden-xs"></td>
                <td colspan="2" class="hidden-xxs"></td>
            </tr>
        </tfoot>
        
        <?php foreach ((array_key_exists('specialFolders', $items) ? $items['specialFolders'] : []) as $specialFolder) : ?>
        <?php
        
echo FileSystemItem::widget([
            'parentFolderId' => $parentFolderId,
            'socialActionsAvailable' => false,
            'selectable' => false,
            'type' => $specialFolder->getItemType(),
            'id' => $specialFolder->getItemId(),
            'downloadUrl' => $specialFolder->getUrl(true),
            'url' => $specialFolder->getUrl(false),
            'wallUrl' => $specialFolder->getWallUrl(),
            'iconClass' => $specialFolder->getIconClass(),
            'title' => $specialFolder->getTitle(),
            'size' => $specialFolder->getSize(),
            'creator' => $specialFolder->creator,
            'editor' => $specialFolder->editor,
            'updatedAt' => $specialFolder->content->updated_at,
            'contentObject' => $specialFolder
        ]);
        ?>
        <?php endforeach; ?>
        <?php foreach ((array_key_exists('folders', $items) ? $items['folders'] : []) as $folder) : ?>
        <?php
        
echo FileSystemItem::widget([
            'parentFolderId' => $parentFolderId,
            'type' => $folder->getItemType(),
            'id' => $folder->getItemId(),
            'downloadUrl' => $folder->getUrl(true),
            'url' => $folder->getUrl(false),
            'wallUrl' => $folder->getWallUrl(),
            'iconClass' => $folder->getIconClass(),
            'title' => $folder->getTitle(),
            'size' => $folder->getSize(),
            'creator' => $folder->creator,
            'editor' => $folder->editor,
            'updatedAt' => $folder->content->updated_at,
            'contentObject' => $folder
        ]);
        ?>
        <?php endforeach; ?>
        <?php foreach ((array_key_exists('files', $items) ? $items['files'] : []) as $file) : ?>
        <?php
        
echo FileSystemItem::widget([
            'parentFolderId' => $parentFolderId,
            'type' => $file->getItemType(),
            'id' => $file->getItemId(),
            'downloadUrl' => $file->getUrl(true),
            'url' => $file->getUrl(false),
            'wallUrl' => $file->getWallUrl(),
            'iconClass' => $file->getIconClass(),
            'title' => $file->getTitle(),
            'size' => $file->getSize(),
            'creator' => $file->creator,
            'editor' => $file->editor,
            'updatedAt' => $file->content->updated_at,
            'contentObject' => $file
        ]);
        ?>
        <?php endforeach; ?>
        <?php foreach ((array_key_exists('postedFiles', $items) ? $items['postedFiles'] : []) as $file) : ?>
        <?php
        
echo FileSystemItem::widget([
            'parentFolderId' => $parentFolderId,
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
            'downloadUrl' => $file->getUrl() . '&' . http_build_query([
                'download' => true
            ]),
            'url' => $file->getUrl(),
            'wallUrl' => \humhub\modules\cfiles\models\File::getBasePost($file)->getUrl(),
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
<div class="folderEmptyMessage">
    <div class="panel">
        <div class="panel-body">
            <p>
                <strong><?php echo Yii::t('CfilesModule.base', 'This folder is empty.');?></strong>
            </p>
            <?php if($currentFolder->isAllPostedFiles()): ?>
            <?php echo Yii::t('CfilesModule.base', 'Upload files to the stream to fill this folder.');?>
            <?php elseif($this->context->canWrite()): ?>
            <?php echo Yii::t('CfilesModule.base', 'Upload files or create a subfolder with the buttons on the top.');?>
            <?php else: ?>
            <?php echo Yii::t('CfilesModule.base', 'Unfortunately you have no permission to upload/edit files.');?>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php endif; ?>
<script>
    $(function () {
        initFileList();
    });
    //humhub.modules.ui.additions.applyTo($('#fileList'));
</script>