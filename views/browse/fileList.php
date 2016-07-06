<?php
use yii\helpers\Html;
use humhub\modules\cfiles\controllers\BrowseController;
use humhub\modules\file\models\File;
?>
<ol class="breadcrumb" dir="ltr">
<?php foreach ($crumb as $parentFolder): ?>
    <li><a
        href="<?php echo $contentContainer->createUrl('/cfiles/browse/'.($parentFolder->id == BrowseController::All_POSTED_FILES_ID ? 'all-posted-files' : 'index'), ['fid' => $parentFolder->id]); ?>">
    <?php echo $parentFolder->id == BrowseController::ROOT_ID ? '<i class="fa fa-home fa-lg fa-fw"></i>' : Html::encode($parentFolder->title); ?></a></li>
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
        <?php if ($allPostedFilesCount > 0) : ?>
            <tr data-type="all-posted-files"
            data-url="<?php echo $contentContainer->createUrl('/cfiles/browse/all-posted-files', ['fid' => BrowseController::All_POSTED_FILES_ID]); ?>"
            data-id="<?php echo 'folder_'.BrowseController::All_POSTED_FILES_ID; ?>">
            <td></td>
            <td class="text-left title">
                <div class="title">
                    <i class="fa fa-folder fa-fw"></i>&nbsp;
                    <a
                    href="<?php echo $contentContainer->createUrl('/cfiles/browse/all-posted-files', ['fid' => BrowseController::All_POSTED_FILES_ID]); ?>">
                            <?php echo Yii::t('CfilesModule.base', 'Files from the stream'); ?> (<?php echo ''. $allPostedFilesCount; ?>)
                    </a>
                </div>
            </td>
            <td></td>
            <td class="hidden-xs"></td>
            <td class="hidden-xxs"></td>
        </tr>
        <?php endif; ?>
        <?php foreach ($items as $item) : 
        $type = $item['file'] instanceof File ? \humhub\modules\cfiles\models\File::getItemTypeByExt($item['file']->getExtension()) : $item['file']->getItemType();
        $id = $item['file'] instanceof File ? 'baseFile_'.$item['file']->id : $item['file']->getItemId();
        $downloadUrl = $item['file'] instanceof File ? $item['file']->getUrl().'&'.http_build_query(['download' => true]) : $item['file']->getUrl(true);
        $url = $item['file'] instanceof File ? $item['file']->getUrl() : $item['file']->getUrl();
        $contentUrl = !empty($item['content']) ? $item['content']->getUrl() : "";
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
            data-content-url="<?php echo $contentUrl; ?>">
            <td class="text-muted text-right">
                <?php echo Html::checkbox('selected[]', false, [ 'value' => $id, 'class' => 'multiselect']); ?>
            </td>
            <td class="text-left">
                <div class="title">
                    <i class="fa <?php echo $iconClass; ?> fa-fw"></i>&nbsp;
                    <?php if ($type === "image") : ?>
                    <a class="preview-link" data-toggle="lightbox"
                        href="<?php echo $url; ?>#.jpeg"
                        data-footer='
                        <button type="button" class="btn btn-primary" data-dismiss="modal"><?php echo Yii::t('FileModule.base', 'Close'); ?></button>'>
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
                    <a href="<?php echo $creator->createUrl(); ?>">
                        <img class="img-rounded tt img_margin"
                            src="<?php echo $creator->getProfileImage()->getUrl(); ?>"
                            width="21" height="21" alt="21x21" data-src="holder.js/21x21"
                            style="width: 21px; height: 21px;"
                            data-original-title="<?php echo (!empty($editor) && $creator->id !== $editor->id ? Yii::t('CfilesModule.base', 'created:') . ' ' : '') . $creator->getDisplayName();?>"
                            data-placement="top" title="" data-toggle="tooltip">
                    </a>
                    <?php if(!empty($editor) && $creator->id !== $editor->id):?>
                    <a href="<?php echo $editor->createUrl(); ?>">
                        <img class="img-rounded tt img_margin"
                            src="<?php echo $editor->getProfileImage()->getUrl(); ?>"
                            width="21" height="21" alt="21x21" data-src="holder.js/21x21"
                            style="width: 21px; height: 21px;"
                            data-original-title="<?php echo Yii::t('CfilesModule.base', 'changed:') . ' ' . $editor->getDisplayName();?>"
                            data-placement="top" title="" data-toggle="tooltip">
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
            <p><strong><?php echo Yii::t('CfilesModule.base', 'This folder is empty.');?></strong></p>
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