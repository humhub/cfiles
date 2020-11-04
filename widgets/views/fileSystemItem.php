<?php

use humhub\libs\Html;
use humhub\modules\cfiles\models\rows\FileSystemItemRow;
use humhub\modules\comment\widgets\CommentLink;
use humhub\modules\file\libs\FileHelper;
use humhub\modules\like\widgets\LikeLink;
use humhub\modules\user\widgets\Image;
use humhub\widgets\TimeAgo;


/* @var $row \humhub\modules\cfiles\models\rows\AbstractFileSystemItemRow */
/* @var $optoins array */
/* @var $canWrite boolean */

?>

<?= Html::beginTag('tr', $options) ?>

    <?php if ($row->isRenderColumn(FileSystemItemRow::COLUMN_SELECT)) : ?>
        <td class="item-selection text-muted text-center">
            <?= $row->isSelectable() ? Html::checkbox('selection[]', false, ['value' => $row->getItemId(), 'class' => 'multiselect']) : ''; ?>
        </td>
    <?php endif; ?>

    <?php if ($row->isRenderColumn(FileSystemItemRow::COLUMN_TITLE)) : ?>
        <td class="text-left">
            <div class="title" style="position:relative">
                <i class="fa <?= $row->getIconClass(); ?>"></i>&nbsp;
                <?php if ($row->getType() === "image") : ?>
                    <a href="<?= $row->getUrl(); ?>" data-ui-gallery="FilesModule-Gallery-<?= $row->getParentFolderId(); ?>" class="tt" title="<?= Html::encode($row->getDescription()) ?>"><?= Html::encode($row->getTitle()); ?></a>
                <?php elseif ($row->getBaseFile() !== null) : ?>
                    <?= FileHelper::createLink($row->getBaseFile(), [], ['class' => 'tt', 'title' => Html::encode($row->getDescription())]); ?>
                <?php else: ?>
                    <a href="<?= $row->getLinkUrl(); ?>" class="tt" title="<?= Html::encode($row->getDescription()) ?>"><?= Html::encode($row->getTitle()); ?></a>
                <?php endif; ?>
            </div>
        </td>
    <?php endif; ?>

    <?php if ($row->isRenderColumn(FileSystemItemRow::COLUMN_VISIBILITY)) : ?>
        <td class="hidden-xs text-muted text-right">
            <i class="fa <?= $row->getVisibilityIcon() ?> fa-fw tt" title="<?= $row->getVisibilityTitle() ?>"></i>
        </td>
    <?php endif; ?>

    <?php if ($row->isRenderColumn(FileSystemItemRow::COLUMN_SIZE)) : ?>
        <td class="hidden-xs text-right">
            <div class="size pull-right">
                <?php if (!$row->getSize()) : ?>
                    -
                <?php else : ?>
                    <?= Yii::$app->formatter->asShortSize($row->getSize(), 1); ?>
                <?php endif; ?>
            </div>
        </td>
    <?php endif; ?>

    <?php if ($row->isRenderColumn(FileSystemItemRow::COLUMN_TIMESTAMP)) : ?>
        <td class="hidden-xxs text-right">
            <div class="timestamp pull-right">
                <?= $row->getUpdatedAt() ? TimeAgo::widget(['timestamp' => $row->getUpdatedAt()]) : ""; ?>
            </div>
        </td>
    <?php endif; ?>

    <?php if (Yii::$app->getModule('cfiles')->settings->get('displayDownloadCount') &&
              $row->isRenderColumn(FileSystemItemRow::COLUMN_DOWNLOAD_COUNT)) : ?>
        <td class="hidden-xs text-right">
            <div class="pull-right">
                <?= $row->getDownloadCount(); ?>
            </div>
        </td>
    <?php endif; ?>

    <?php if ($row->isRenderColumn(FileSystemItemRow::COLUMN_SOCIAL)): ?>
        <td class="text-right">
            <?php if ($row->isSocialActionsAvailable()): ?>
                <div class="file-controls pull-right">
                    <?= LikeLink::widget(['object' => $row->getModel()]); ?>
                    |
                    <?= CommentLink::widget(['object' => $row->getModel(), 'mode' => CommentLink::MODE_POPUP]); ?>
                </div>
            <?php endif; ?>
        </td>
    <?php endif; ?>

    <?php if ($row->isRenderColumn(FileSystemItemRow::COLUMN_CREATOR)): ?>
        <td class="hidden-xxs text-right">
            <div class="creator pull-right">
                <?php if (!empty($row->getCreator())): ?>
                    <?= Image::widget(['user' => $row->getCreator(), 'width' => 21, 'showTooltip' => true]) ?>
                <?php endif; ?>
                <?php if (!empty($row->getEditor()) && !$row->getCreator()->is($row->getEditor())): ?>
                    <?= Image::widget(['user' => $row->getEditor(), 'width' => 21, 'showTooltip' => true]) ?>
                <?php endif; ?>
            </div>
        </td>
    <?php endif; ?>
<?= Html::endTag('tr') ?>
