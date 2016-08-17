<?php
use yii\helpers\Html;
use humhub\modules\like\widgets\LikeLink;
use humhub\modules\comment\widgets\CommentLink;
$emptyList = true;
?>

<style>
.cfiles-wallout-folder .breadcrumb {
    margin-bottom: 0;
}
.cfiles-wallout-folder .breadcrumb a {
    color: #555;
}
.cfiles-wallout-folder .breadcrumb .link-info {
    color: #bebebe;
    font-size: 11px;
}
.cfiles-wallout-folder .table-responsive .table a {
    color: #555;
}
.cfiles-wallout-folder .table-responsive .table .file-controls {
    color: #bebebe;
    font-size: 11px;
}
.cfiles-wallout-folder .table-responsive .table .file-controls hr {
    margin-top: 2px;
    margin-bottom: 2px;    
}
.cfiles-wallout-folder .table-responsive .table .file-controls a {
    color: #bebebe;
}

</style>

<div style="overflow: hidden; margin-bottom: 10px;">
    <?php echo trim($folder->description) ? $folder->description : '&nbsp;' ?>
</div>
<div class="cfiles-wallout-folder" id="cfiles-wallout-folder-content-<?php echo $folder->id; ?>" style="overflow: hidden; margin-bottom: 5px;">
    <ol class="breadcrumb" dir="ltr">
        <li><a
            href="<?php echo $folder->getUrl(); ?>"><i class="fa fa-home fa-lg fa-fw"></i><?php echo Html::encode($folder->getFullPath(' / ')); ?></a> <span class="link-info"><?php echo Yii::t('CfilesModule.base', '(Click to edit and move files in directory)'); ?></span>
        </li>
    </ol>
    
    <div class="table-responsive">
        <table id="bs-table" class="table table-hover">
            <thead>
                <tr>
                    <th class="text-left"><?php echo Yii::t('CfilesModule.base', 'Name'); ?></th>
                    <th class="hidden-xxs text-right"><?php echo Yii::t('CfilesModule.base', 'Updated'); ?></th>
                    <th class="text-right"><?php echo Yii::t('CfilesModule.base', 'Likes/Comments'); ?></th>
                    <th class="text-right"><?php echo Yii::t('CfilesModule.base', 'Creator'); ?></th>
                </tr>
            </thead>
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
            <tr data-type="<?php echo $type; ?>"
                data-id="<?php echo $id; ?>"
                data-url="<?php echo $downloadUrl; ?>"
                data-content-url="<?php echo $contentUrl; ?>">
                <td class="text-left">
                    <div class="title">
                        <i class="fa <?php echo $iconClass; ?> fa-fw"></i>&nbsp;
                        <?php if ($type === "image") : ?>
                        <a class="preview-link" data-toggle="lightbox" data-parent="#bs-table"  data-gallery="FilesModule-Gallery-<?php echo $folder->id; ?>"
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
                <td class="hidden-xxs text-right">
                    <div class="timestamp pull-right">
                        <?php echo \humhub\widgets\TimeAgo::widget([ 'timestamp' => $updatedAt ]); ?>
                    </div>
                </td>
                <td class="text-right">
                    <div class="file-controls pull-right">
                        <?php echo LikeLink::widget(['object' => $item]); ?>
                        <hr>
                        <?php echo CommentLink::widget(['object' => $item, 'mode' => CommentLink::MODE_POPUP]); ?>
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
    <?php if($emptyList): ?>
    <div class="folderEmptyMessage">
        <p><strong><?php echo Yii::t('CfilesModule.base', 'This folder is still empty.');?></strong></p>
    </div>
    <?php endif;?>
</div>
<a class="more-link-cfiles-wallout-folder hidden" id="more-link-cfiles-wallout-folder-<?php echo $folder->id; ?>" data-state="down"
   style="margin: 20px 0 20px 0; display: block;" href="javascript:showMoreFiles(<?php echo $folder->id; ?>);"><i
        class="fa fa-arrow-down"></i> <?php echo Yii::t('CfilesModule.base', 'Show all files'); ?>
</a>
<script type="text/javascript">
            
    $(document).ready(function () {

        var _folderContentHeight = $('#cfiles-wallout-folder-content-<?php echo $folder->id; ?>').outerHeight();

        if (_folderContentHeight > 310) {
            // show more-button
            $('#more-link-cfiles-wallout-folder-<?php echo $folder->id; ?>').removeClass('hidden');
            // set limited height
            $('#cfiles-wallout-folder-content-<?php echo $folder->id; ?>').css({'display': 'block', 'max-height': '310px'});
        }
    });

    function showMoreFiles(folder_id) {

        // set current state
        var _state = $('#more-link-cfiles-wallout-folder-' + folder_id).attr('data-state');

        if (_state == "down") {

            $('#cfiles-wallout-folder-content-' + folder_id).css('max-height', '2000px');

            // set new link content
            $('#more-link-cfiles-wallout-folder-' + folder_id).html('<i class="fa fa-arrow-up"></i> <?php echo Html::encode(Yii::t('CfilesModule.base', 'Collapse')); ?>');

            // update link state
            $('#more-link-cfiles-wallout-folder-' + folder_id).attr('data-state', 'up');

        } else {
            // set back to limited length
            $('#cfiles-wallout-folder-content-' + folder_id).css('max-height', '310px');

            // set new link content
            $('#more-link-cfiles-wallout-folder-' + folder_id).html('<i class="fa fa-arrow-down"></i> <?php echo Html::encode(Yii::t('CfilesModule.base', 'Show all files')); ?>');

            // update link state
            $('#more-link-cfiles-wallout-folder-' + folder_id).attr('data-state', 'down');

            $('body, html').animate({ 
                scrollTop: $('#more-link-cfiles-wallout-folder-' + folder_id).closest('.wall-entry').offset().top - 100
            }, 600);

        }

    }
</script>
