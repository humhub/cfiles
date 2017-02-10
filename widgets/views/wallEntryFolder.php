<?php

use yii\helpers\Html;
use humhub\modules\cfiles\widgets\FolderPreview;
?>

<div class="cfiles-wallout-folder">
    <div class="folder-wallentry-header" style="overflow: hidden; margin-bottom: 20px;">        
        <div><?php echo humhub\widgets\RichText::widget(['text' => (trim($folder->description) ? $folder->description : ''), 'record' => $folder]); ?></div>
    </div>
    <div class="folder-wallentry-content" id="cfiles-wallout-folder-content-<?php echo $folder->id; ?>" style="overflow: hidden;">
        <div class="preview">
            <?php echo FolderPreview::widget(['folder' => $folder, 'lightboxDataParent' => "#cfiles-wallout-folder-content-$folder->id", 'lightboxDataGallery' => "FilesModule-Gallery-$folder->id"]); ?>
        </div>
    </div>
    <a class="more-link-cfiles-wallout-folder hidden" id="more-link-cfiles-wallout-folder-<?php echo $folder->id; ?>" data-state="down"
       style="margin: 20px 0 20px 0; display: block;" href="javascript:showMoreFiles(<?php echo $folder->id; ?>);"><i
            class="fa fa-arrow-down"></i> <?php echo Yii::t('CfilesModule.base', 'Show all files'); ?>
    </a>
    <div class="folder-wallentry-footer" style="overflow: hidden; margin-bottom: 10px;">
        <div style="overflow: hidden;"><a href="<?php echo $folder->getUrl(); ?>"><?php echo Yii::t('CfilesModule.base', 'Open directory!'); ?></a></div>   
    </div>
</div>

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
