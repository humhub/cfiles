<?php
use yii\helpers\Html;
use humhub\modules\like\widgets\LikeLink;
use humhub\modules\comment\widgets\CommentLink;
$emptyList = true;
?>

<tr data-type="<?php echo $type; ?>"
data-id="<?php echo $id; ?>"
data-url="<?php echo $downloadUrl; ?>"
data-wall-url="<?php echo $wallUrl; ?>">
    <?php if(in_array('select', $columns)): ?>
    <td class="text-muted text-right">
        <?php echo $selectable ? Html::checkbox('selected[]', false, [ 'value' => $id, 'class' => 'multiselect']) : ''; ?>
    </td>
    <?php endif;?>
    <?php if(in_array('title', $columns)): ?>
    <td class="text-left">
        <div class="title">
            <i class="fa <?php echo $iconClass; ?> fa-fw"></i>&nbsp;
            <?php if ($type === "image") : ?>
            <a class="preview-link" data-toggle="lightbox"
                data-parent="#bs-table"
                data-pjax-prevent="1"
                data-ui-gallery="FilesModule-Gallery-<?php echo $parentFolderId; ?>"
                href="<?php echo $url; ?>#.jpeg"
                data-footer='
                <button 
                type="button" class="btn btn-primary"
                data-dismiss="modal"><?php echo Yii::t('FileModule.base', 'Close'); ?></button>'>
                <?php echo $title; ?>
            </a>
            <?php else : ?>
            <a data-pjax-prevent="1" href="<?php echo $url; ?>">
                <?php echo $title; ?>
            </a>
            <?php endif; ?>
        </div>
    </td>
    <?php endif; ?>
    <?php if(in_array('size', $columns)): ?>
    <td class="hidden-xs text-right">
        <div class="size pull-right">
            <?php if ($size == 0): ?> 
                &mdash;
            <?php else: ?>
                <?php echo Yii::$app->formatter->asShortSize($size, 1); ?>
            <?php endif; ?>
        </div>
    </td>
    <?php endif; ?>
    <?php if(in_array('timestamp', $columns)): ?>
    <td class="hidden-xxs text-right">
        <div class="timestamp pull-right">
            <?php echo $updatedAt ? \humhub\widgets\TimeAgo::widget([ 'timestamp' => $updatedAt ]) : ""; ?>
        </div>
    </td>
    <?php endif; ?>
    <?php if(in_array('likesncomments', $columns)): ?>
    <td class="text-right">
        <?php if($socialActionsAvailable): ?>
        <div class="file-controls pull-right">
            <?php echo LikeLink::widget(['object' => $contentObject]); ?>
            |
            <?php echo CommentLink::widget(['object' => $contentObject, 'mode' => CommentLink::MODE_POPUP]); ?>
        </div>
        <?php endif; ?>
    </td>
    <?php endif; ?>
    <?php if(in_array('creator', $columns)): ?>
    <td class="hidden-xxs text-right">
        <div class="creator pull-right">
            <?php if(!empty($creator)): ?>
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
            <?php endif; ?>
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
    <?php endif; ?>
</tr>    