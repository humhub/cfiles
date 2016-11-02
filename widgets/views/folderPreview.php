<?php
// available params:
// $htmlConf;

use yii\helpers\Html;
use humhub\modules\like\widgets\LikeLink;
use humhub\modules\comment\widgets\CommentLink;
use humhub\modules\cfiles\libs\FileUtils;

$emptyList = true;
?>

<style>
.cfiles-wallout-folder-preview .content-list {
    margin: 0;
}
.cfiles-wallout-folder-preview .content-list li {
    margin-bottom: 4px;
}
.cfiles-wallout-folder-preview .content-list li i {
    font-size: 20px;
    color: #91A0B0;
}
.cfiles-wallout-folder-preview .content-list .creator {
    vertical-align: bottom;
}
.cfiles-wallout-folder-preview .folder-title i {
    font-size: 20px;
}
.cfiles-wallout-folder-preview .folder-title h5 i, .cfiles-wallout-folder-preview .folder-title h5 span {
    vertical-align: middle;
}
.cfiles-wallout-folder-preview .info-small {
    color: #bebebe;
    font-size: 11px;
}
.cfiles-wallout-folder .folder-wallentry-content {
    margin-bottom: 20px;
}
</style>

<div class="cfiles-wallout-folder-preview">
    <div class="folder-title">
        <h5><i class="fa <?php echo $folder->getIconClass(); ?> fa-fw"></i><span>&nbsp;<?php echo Html::encode($folder->getTitle()); ?></span></h5>
    </div>
    
    <hr />
    
    <ul class="files content-list">
        <?php $folders = $folder->folders;
        $files = $folder->files;
        $items = array_merge(is_array($folders) ? $folders : [], is_array($files) ? $files : []);
        ?>
        <?php foreach ($items as $item) : 
        $emptyList = false;
        $type = $item->getItemType();
        $id = $item->getItemId();
        $downloadUrl = $item->getUrl(true);
        $url = $item->getUrl();
        $contentUrl = $item->content->getUrl();
        $iconClass = $item->getIconClass();
        $title = Html::encode($item->getTitle());
        $size = $item->getSize();
        $creator = $item->creator;
        $editor = $item->editor;
        $updatedAt = $item->content->updated_at;
        ?>
        <li>
            <span class="title">
                <i class="fa <?php echo $iconClass; ?> fa-fw"></i>
                <?php if ($type === "image") : ?>
                <a class="preview-link" data-toggle="lightbox" data-parent="<?php echo $lightboxDataParent; ?>"  data-gallery="<?php echo $lightboxDataGallery; ?>"
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
            </span>
            <span class="info-small"> - 
                <span class="creator">
                        <?php if(!empty($editor)): ?>
                        <a href="<?php echo $editor->createUrl(); ?>">
                            <img class="img-rounded tt img_margin"
                                src="<?php echo $editor->getProfileImage()->getUrl(); ?>"
                                width="20" height="20" alt="20x20" data-src="holder.js/20x20"
                                style="width: 20px; height: 20px;"
                                data-original-title="<?php echo Yii::t('CfilesModule.base', 'changed:') . ' ' . $editor->getDisplayName();?>"
                                data-placement="top" title="" data-toggle="tooltip">
                        </a>
                        <?php endif; ?>
                </span> - 
                <span class="timestamp">
                        <?php echo \humhub\widgets\TimeAgo::widget([ 'timestamp' => $updatedAt ]); ?>
                </span>
            </span>
        </li> 
        <?php endforeach; ?>
    </ul>
    <?php if($emptyList): ?>
        <div class="folderEmptyMessage">
            <strong><?php echo Yii::t('CfilesModule.base', 'This folder is still empty.');?></strong>
        </div>
    <?php endif; ?>
</div>
