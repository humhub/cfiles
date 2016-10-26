<?php
use yii\helpers\Html;
use humhub\modules\like\widgets\LikeLink;
use humhub\modules\comment\widgets\CommentLink;
use humhub\modules\cfiles\widgets\FileSystemItem;
use humhub\modules\cfiles\widgets\FilePreview;
?>

<style>
.cfiles-wallout-file {
    
}

.cfiles-wallout-file .info-small {
    color: #bebebe;
    font-size: 11px;
}

.cfiles-wallout-file .file-wallentry-header i {
    font-size: 20px;
}

.cfiles-wallout-file .file-wallentry-header h5 i,.cfiles-wallout-file .file-wallentry-header h5 span
    {
    vertical-align: middle;
}

.cfiles-wallout-file .file-wallentry-content {
    margin-bottom: 20px;
}

.cfiles-wallout-file .file-wallentry-content .preview a img {
    max-width: 100%;
}

.cfiles-wallout-file .file-wallentry-content ul {
    margin: 0;
    padding-left: 5px;
    list-style-type: none;
    
}
.cfiles-wallout-file .file-wallentry-content ul li {
    margin-bottom: 2px;
    vertical-align: middle;
    
}

.cfiles-wallout-file .file-wallentry-content ul li a {
    color: #555;
    vertical-align: middle;
}
</style>

<?php 
$type = $file->getItemType();
$id = $file->getItemId();
$downloadUrl = $file->getUrl(true);
$url = $file->getUrl();
$iconClass = $file->getIconClass();
$title = Html::encode($file->getTitle());
$description = trim($file->description) ? $file->description : '';
$size = $file->getSize();
$creator = $file->creator;
$editor = $file->editor;
$updatedAt = $file->content->updated_at;
$createdAt = $file->content->created_at;
?>

<div class="cfiles-wallout-file" id="cfiles-wallout-file-<?php echo $file->id; ?>">
    <div class="file-wallentry-header"
        style="overflow: hidden; margin-bottom: 20px;">
        <div><?php echo humhub\widgets\RichText::widget(['text' => $description, 'record' => $file]); ?></div>
    </div>
    <div class="file-wallentry-content"
        style="overflow: hidden;">
        <div class="preview">
            <?php echo FilePreview::widget(['file' => $file, 'width' => 600, 'height' => 350, 'htmlConf' => ['class' => 'preview', 'id' => 'cfiles-wallout-file-preview-'.$file->id]]); ?>
        </div>
        <hr />
        <ul>
            <li>
                <span><?php echo Yii::t('CfilesModule.base', 'Created at:')?>&nbsp;</span>
                <span><?php echo \humhub\widgets\TimeAgo::widget([ 'timestamp' => $createdAt ]); ?></span>
            </li>
            <li>
                <span><?php echo Yii::t('CfilesModule.base', 'Created by:')?>&nbsp;</span>
                <span>
                    <a href="<?php echo $creator->createUrl(); ?>"><?php echo $creator->getDisplayName();?></a>
                </span>
            </li>
            <?php if(!empty($editor) && $creator->id !== $editor->id):?>
            <li>
                <span><?php echo Yii::t('CfilesModule.base', 'Last edited at:')?>&nbsp;</span>
                <span><?php echo \humhub\widgets\TimeAgo::widget([ 'timestamp' => $updatedAt ]); ?></span>
            </li>
            <li>
                <span><?php echo Yii::t('CfilesModule.base', 'Last edited by:')?>&nbsp;</span>
                <span>
                    <a href="<?php echo $editor->createUrl(); ?>"><?php echo $editor->getDisplayName(); ?></a>
                </span>
            </li>
            <?php endif; ?>
            <li>
                <span><?php echo Yii::t('CfilesModule.base', 'Filesize:')?>&nbsp;</span>
                <span class="time"><?php echo Yii::$app->formatter->asShortSize($size, 1); ?></span>
            </li>
        </ul>
        <a class="more-link-cfiles-wallout-file hidden" id="more-link-cfiles-wallout-file-<?php echo $file->id; ?>" data-state="down"
           style="margin: 20px 0 20px 0; display: block;" href="javascript:showMoreFiles(<?php echo $file->id; ?>);"><i
                class="fa fa-arrow-down"></i> <?php echo Yii::t('CfilesModule.base', 'Show complete file preview'); ?>
        </a> 
    </div>
    <div class="file-wallentry-footer"
        style="overflow: hidden; margin-bottom: 10px;">
        <div style="overflow: hidden;">
            <a href="<?php echo $file->parentFolder->getUrl(); ?>"><?php echo Yii::t('CfilesModule.base', 'Open parent directory!'); ?></a>
        </div>
    </div>
</div>