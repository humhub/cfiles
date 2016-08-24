<?php
use yii\helpers\Html;
use humhub\modules\cfiles\controllers\BrowseController;
use humhub\modules\file\models\File;
use humhub\modules\cfiles\widgets\FileSystemItem;

$parentFolderId = null;

?>
<ol class="breadcrumb" dir="ltr">

<?php foreach ($crumb as $parentFolder): ?>
    
    <li><a
        href="<?php echo $contentContainer->createUrl('/cfiles/browse/'.($parentFolder->isAllPostedFiles() ? 'all-posted-files' : 'index'), ['fid' => $parentFolder->id]); ?>">
    <?php echo $parentFolder->isRoot() ? '<i class="fa fa-home fa-lg fa-fw"></i>' : Html::encode($parentFolder->title); ?></a></li>
    <?php $parentFolderId = $parentFolder->id; ?>
<?php endforeach; ?>
</ol>

<div id="cfiles-log"></div>

<?php if(sizeof($items) > 0 || $allPostedFilesCount > 0) : ?>
<div class="table-responsive">
    <table id="bs-table" class="table table-hover">
        <thead>
            <tr>
                <th class="text-right">
                    <?php echo Html::checkbox('allchk', false, [ 'class' => 'allselect']); ?></th>
                <th class="text-left"><?php echo Yii::t('CfilesModule.base', 'Name'); ?></th>
                <th class="hidden-xs text-right"><?php echo Yii::t('CfilesModule.base', 'Size'); ?></th>
                <th class="hidden-xxs text-right"><?php echo Yii::t('CfilesModule.base', 'Updated'); ?></th>
                <th class="text-right"><?php echo Yii::t('CfilesModule.base', 'Creator'); ?></th>
            </tr>
        </thead>
        <tfoot>
            <tr>
                <td colspan="3"></td>
                <td class="hidden-xs"></td>
                <td class="hidden-xxs"></td>
            </tr>
        </tfoot>
        
        <?php foreach ((array_key_exists('specialFolders', $items) ? $items['specialFolders'] : []) as $specialFolder) : ?>
        <?php echo FileSystemItem::widget([
            'selectable' => false,
            'editable' => false,
            'parentFolderId' => $parentFolderId,
            'type' => $specialFolder->getItemType(),
            'id' => $specialFolder->getItemId(),
            'downloadUrl' => $specialFolder->getUrl(true),
            'url' => $specialFolder->getUrl(),
            'wallUrl' => $specialFolder->getWallUrl(),
            'iconClass' => $specialFolder->getIconClass(),
            'title' => $specialFolder->getTitle(),
            'size' => $specialFolder->getSize(),
            'creator' => $specialFolder->creator,
            'editor' => $specialFolder->editor,
            'updatedAt' => $specialFolder->content->updated_at
        ]); ?>
        <?php endforeach; ?>
        <?php foreach ((array_key_exists('folders', $items) ? $items['folders'] : []) as $folder) : ?>
        <?php echo FileSystemItem::widget([
            'parentFolderId' => $parentFolderId,
            'type' => $folder->getItemType(),
            'id' => $folder->getItemId(),
            'downloadUrl' => $folder->getUrl(true),
            'url' => $folder->getUrl(),
            'wallUrl' => $folder->getWallUrl(),
            'iconClass' => $folder->getIconClass(),
            'title' => $folder->getTitle(),
            'size' => $folder->getSize(),
            'creator' => $folder->creator,
            'editor' => $folder->editor,
            'updatedAt' => $folder->content->updated_at
        ]); ?>
        <?php endforeach; ?>
        <?php foreach ((array_key_exists('files', $items) ? $items['files'] : []) as $file) : ?>
        <?php echo FileSystemItem::widget([
            'parentFolderId' => $parentFolderId,
            'type' => $file->getItemType(),
            'id' => $file->getItemId(),
            'downloadUrl' => $file->getUrl(true),
            'url' => $file->getUrl(),
            'wallUrl' => $file->getWallUrl(),
            'iconClass' => $file->getIconClass(),
            'title' => $file->getTitle(),
            'size' => $file->getSize(),
            'creator' => $file->creator,
            'editor' => $file->editor,
            'updatedAt' => $file->content->updated_at
        ]); ?>
        <?php endforeach; ?>
        <?php foreach ((array_key_exists('postedFiles', $items) ? $items['postedFiles'] : []) as $file) : ?>
        <?php echo FileSystemItem::widget([
            'parentFolderId' => $parentFolderId,
            'type' => \humhub\modules\cfiles\models\File::getItemTypeByExt($file->getExtension()),
            'id' => 'baseFile_'.$file->id,
            'downloadUrl' => $file->getUrl().'&'.http_build_query(['download' => true]),
            'url' =>  $file->getUrl(),
            'wallUrl' => \humhub\modules\cfiles\models\File::getBasePost($file)->getUrl(),
            'iconClass' => \humhub\modules\cfiles\models\File::getIconClassByExt($file->getExtension()),
            'title' => $file->file_name,
            'size' =>  $file->size,
            'creator' => \humhub\modules\cfiles\models\File::getUserById($file->created_by),
            'editor' => \humhub\modules\cfiles\models\File::getUserById($file->updated_by),
            'updatedAt' => $file->updated_at
        ]); ?>
        <?php endforeach; ?>
        <?php foreach ($items as $item) : 
        break;
        $type = $item['file'] instanceof File ? \humhub\modules\cfiles\models\File::getItemTypeByExt($item['file']->getExtension()) : $item['file']->getItemType();
        $id = $item['file'] instanceof File ? 'baseFile_'.$item['file']->id : $item['file']->getItemId();
        $downloadUrl = $item['file'] instanceof File ? $item['file']->getUrl().'&'.http_build_query(['download' => true]) : $item['file']->getUrl(true);
        $url = $item['file'] instanceof File ? $item['file']->getUrl() : $item['file']->getUrl();
        $wallUrl = $item['file'] instanceof File ?  $item['content']->getUrl() : $item['file']->getWallUrl();
        $iconClass = $item['file'] instanceof File ? \humhub\modules\cfiles\models\File::getIconClassByExt($item['file']->getExtension()) : $item['file']->getIconClass();
        $title = Html::encode($item['file'] instanceof File ? $item['file']->file_name : $item['file']->getTitle());
        $size = $item['file'] instanceof File ? $item['file']->size : $item['file']->getSize();
        $creator = $item['file'] instanceof File ? \humhub\modules\cfiles\models\File::getUserById($item['file']->created_by) : $item['file']->creator;
        $editor = $item['file'] instanceof File ? \humhub\modules\cfiles\models\File::getUserById($item['file']->updated_by) : $item['file']->editor;
        $updatedAt = $item['file'] instanceof File ? $item['file']->updated_at : $item['file']->content->updated_at;
        ?>
        <tr data-type="<?php echo $type; ?>"
            data-id="<?php echo $id; ?>"
            data-url="<?php echo $downloadUrl; ?>"
            data-wall-url="<?php echo $wallUrl; ?>">
            <td class="text-muted text-right">
                <?php echo Html::checkbox('selected[]', false, [ 'value' => $id, 'class' => 'multiselect']); ?>
            </td>
            <td class="text-left">
                <div class="title">
                    <i class="fa <?php echo $iconClass; ?> fa-fw"></i>&nbsp;
                    <?php if ($type === "image") : ?>
                    <a class="preview-link" data-toggle="lightbox"
                        data-parent="#bs-table"
                        data-gallery="FilesModule-Gallery-<?php echo $parentFolderId; ?>"
                        href="<?php echo $url; ?>#.jpeg"
                        data-footer='
                        <button 
                        
                        
                        
                        type="button" class="btn btn-primary"
                        data-dismiss="modal"><?php echo Yii::t('FileModule.base', 'Close'); ?></button>'>
                        <?php echo $title; ?>
                    </a>
                    <?php else : ?>
                    <a href="<?php echo $url; ?>">
                        <?php echo $title; ?>
                    </a>
                    <?php endif; ?>
                </div>
            </td>
            <td class="hidden-xs text-right">
                <div class="size pull-right">
                    <?php if ($size == 0): ?> 
                        &mdash;
                    <?php else: ?>
                        <?php echo Yii::$app->formatter->asShortSize($size, 1); ?>
                    <?php endif; ?>
                </div>
            </td>
            <td class="hidden-xxs text-right">
                <div class="timestamp pull-right">
                    <?php echo \humhub\widgets\TimeAgo::widget([ 'timestamp' => $updatedAt ]); ?>
                </div>
            </td>
            <td class="text-right">
                <div class="creator pull-right">
                    <a href="<?php echo $creator->createUrl(); ?>"> <img
                        class="img-rounded tt img_margin"
                        src="<?php echo $creator->getProfileImage()->getUrl(); ?>"
                        width="21" height="21" alt="21x21"
                        data-src="holder.js/21x21"
                        style="width: 21px; height: 21px;"
                        data-original-title="<?php echo (!empty($editor) && $creator->id !== $editor->id ? Yii::t('CfilesModule.base', 'created:') . ' ' : '') . $creator->getDisplayName();?>"
                        data-placement="top" title=""
                        data-toggle="tooltip">
                    </a>
                    <?php if(!empty($editor) && $creator->id !== $editor->id):?>
                    <a href="<?php echo $editor->createUrl(); ?>"> <img
                        class="img-rounded tt img_margin"
                        src="<?php echo $editor->getProfileImage()->getUrl(); ?>"
                        width="21" height="21" alt="21x21"
                        data-src="holder.js/21x21"
                        style="width: 21px; height: 21px;"
                        data-original-title="<?php echo Yii::t('CfilesModule.base', 'changed:') . ' ' . $editor->getDisplayName();?>"
                        data-placement="top" title=""
                        data-toggle="tooltip">
                    </a>
                    <?php endif; ?>
                </div>
            </td>
        </tr>
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
            <?php if($this->context->action->id == "all-posted-files"): ?>
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
</script>