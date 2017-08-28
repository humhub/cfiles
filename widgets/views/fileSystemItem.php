<?php

use humhub\modules\content\models\Content;
use yii\helpers\Html;
use humhub\modules\file\libs\FileHelper;
use humhub\modules\like\widgets\LikeLink;
use humhub\modules\comment\widgets\CommentLink;

/* @var $id string */
/* @var $type string */
/* @var $downloadUrl string */
/* @var $wallUrl string */
/* @var $url string */
/* @var $size integer */
/* @var $updatedAt string */
/* @var $editUrl string */
/* @var $moveUrl string */
/* @var $moveUrl string */
/* @var $iconClass string */
/* @var $contentObject \humhub\modules\cfiles\models\FileSystemItem */
/* @var $parentFolderId integer */
/* @var $visibilityIcon string */
/* @var $visibilityTitle string */
/* @var $selectable boolean */
/* @var $socialActionsAvailable boolean */
/* @var $columns string[] */
/* @var $description string[] */
/* @var $creator \humhub\modules\user\models\User */
/* @var $editor \humhub\modules\user\models\User */

?>

<tr data-ui-widget="cfiles.FileItem"
    data-cfiles-item="<?= $id ?>"
    data-cfiles-type="<?= $type; ?>"
    data-cfiles-url="<?= $downloadUrl; ?>"
    data-cfiles-wall-url="<?= $wallUrl; ?>"
    data-cfiles-edit-url="<?= $editUrl ?>"
    data-cfiles-move-url="<?= $moveUrl ?>">

    <?php if (in_array('select', $columns)): ?>
        <td class="item-selection text-muted text-center">
            <?= $selectable ? Html::checkbox('selection[]', false, ['value' => $id, 'class' => 'multiselect']) : ''; ?>
        </td>
    <?php endif; ?>
    <?php if (in_array('title', $columns)): ?>
        <td class="text-left">
            <div class="title" style="position:relative">
                <i class="fa <?= $iconClass; ?>"></i>&nbsp;
                <?php if ($type != 'folder' && $contentObject !== null): ?>
                    <?= FileHelper::createLink($contentObject->baseFile, [], ['class' => 'tt', 'title' => $description]); ?>
                    <?php if ($type === "image") : ?>
                        <a href="<?= $url; ?>" class="preview-link" data-ui-gallery="FilesModule-Gallery-<?= $parentFolderId; ?>" style="display:none;" class="tt" title="<?= $description ?>"></a>
                    <?php endif; ?>
                <?php elseif ($this->context->baseFile !== null): ?>
                    <?= FileHelper::createLink($this->context->baseFile, ['class' => 'tt', 'title' => $description]); ?>
                <?php else: ?>
                    <a href="<?= $url; ?>" class="tt" title="<?= $description ?>"><?= Html::encode($title); ?></a>
                <?php endif; ?>
            </div>
        </td>
    <?php endif; ?>
    <?php if (in_array('visibility', $columns)): ?>
        <td class="hidden-xs text-muted text-right">
            <i class="fa <?= $visibilityIcon ?> fa-fw tt" title="<?= $visibilityTitle ?>"></i>
        </td>
    <?php endif; ?>
    <?php if (in_array('size', $columns)): ?>
        <td class="hidden-xs text-right">
            <div class="size pull-right">
                <?php if ($size == 0): ?>
                    -
                <?php else: ?>
                    <?= Yii::$app->formatter->asShortSize($size, 1); ?>
                <?php endif; ?>
            </div>
        </td>
    <?php endif; ?>
    <?php if (in_array('timestamp', $columns)): ?>
        <td class="hidden-xxs text-right">
            <div class="timestamp pull-right">
                <?= $updatedAt ? \humhub\widgets\TimeAgo::widget(['timestamp' => $updatedAt]) : ""; ?>
            </div>
        </td>
    <?php endif; ?>
    <?php if (in_array('likesncomments', $columns)): ?>
        <td class="text-right">
            <?php if ($socialActionsAvailable): ?>
                <div class="file-controls pull-right">
                    <?= LikeLink::widget(['object' => $contentObject]); ?>
                    |
                    <?= CommentLink::widget(['object' => $contentObject, 'mode' => CommentLink::MODE_POPUP]); ?>
                </div>
            <?php endif; ?>
        </td>
    <?php endif; ?>
    <?php if (in_array('creator', $columns)): ?>
        <td class="hidden-xxs text-right">
            <div class="creator pull-right">
                <?php if (!empty($creator)): ?>
                    <a href="<?= $creator->createUrl(); ?>"> <img
                                class="img-rounded tt img_margin"
                                src="<?= $creator->getProfileImage()->getUrl(); ?>"
                                width="21" height="21" alt="21x21"
                                data-src="holder.js/21x21"
                                style="width: 21px; height: 21px;"
                                data-original-title="<?= (!empty($editor) && $creator->id !== $editor->id ? Yii::t('CfilesModule.base', 'created:') . ' ' : '') . $creator->getDisplayName(); ?>"
                                data-placement="top" title="" data-toggle="tooltip">
                    </a>
                <?php endif; ?>
                <?php if (!empty($editor) && $creator->id !== $editor->id): ?>
                    <a href="<?= $editor->createUrl(); ?>"> <img
                                class="img-rounded tt img_margin"
                                src="<?= $editor->getProfileImage()->getUrl(); ?>"
                                width="21" height="21" alt="21x21"
                                data-src="holder.js/21x21"
                                style="width: 21px; height: 21px;"
                                data-original-title="<?= Yii::t('CfilesModule.base', 'changed:') . ' ' . $editor->getDisplayName(); ?>"
                                data-placement="top" title="" data-toggle="tooltip">
                    </a>
                <?php endif; ?>
            </div>
        </td>
    <?php endif; ?>
</tr>
