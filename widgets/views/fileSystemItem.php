<?php

use humhub\helpers\Html;
use humhub\modules\cfiles\models\Folder;
use humhub\modules\cfiles\models\rows\FileSystemItemRow;
use humhub\modules\cfiles\widgets\FileListContextMenu;
use humhub\modules\comment\widgets\CommentLink;
use humhub\modules\content\widgets\ContentObjectLinks;
use humhub\modules\file\libs\FileHelper;
use humhub\modules\stream\assets\StreamAsset;
use humhub\modules\user\widgets\Image;
use humhub\widgets\bootstrap\Button;
use humhub\widgets\TimeAgo;

/* @var $folder Folder */
/* @var $row FileSystemItemRow */
/* @var $options array */
/* @var $canWrite boolean */

StreamAsset::register($this);
?>

<?= Html::beginTag('tr', $options) ?>

    <?php if ($row->isRenderColumn(FileSystemItemRow::COLUMN_SELECT)) : ?>
        <td class="item-selection text-muted text-center">
            <?= $row->isSelectable() ? Html::checkbox('selection[]', false, ['value' => $row->getItemId(), 'class' => 'multiselect']) : ''; ?>
        </td>
    <?php endif; ?>

    <?php if ($row->isRenderColumn(FileSystemItemRow::COLUMN_TITLE)) : ?>
        <td class="text-start">
            <div style="position: relative">
                <div class="title">
                    <i class="fa <?= $row->getIconClass(); ?>"></i>&nbsp;
                    <?php if ($row->getType() === "image") : ?>
                        <a href="<?= $row->getUrl(); ?>" data-ui-gallery="FilesModule-Gallery-<?= $row->getParentFolderId(); ?>" class="tt" title="<?= Html::encode($row->getDescription()) ?>"><?= Html::encode($row->getTitle()); ?></a>
                    <?php elseif ($row->getBaseFile() !== null) : ?>
                        <?= FileHelper::createLink($row->getBaseFile(), [], ['class' => 'tt', 'title' => Html::encode($row->getDescription())]); ?>
                    <?php else: ?>
                        <a href="<?= $row->getLinkUrl(); ?>" class="tt" title="<?= Html::encode($row->getDescription()) ?>"><?= Html::encode($row->getTitle()); ?></a>
                    <?php endif; ?>
                </div>
            </div>
            <?= FileListContextMenu::widget([
                'folder' => $folder,
                'row' => $row,
            ]) ?>
        </td>
    <?php endif; ?>

    <?php if ($row->isRenderColumn(FileSystemItemRow::COLUMN_VISIBILITY)) : ?>
        <td class="d-none d-sm-table-cell text-muted text-end">
            <i class="fa <?= $row->getVisibilityIcon() ?> fa-fw tt" title="<?= $row->getVisibilityTitle() ?>"></i>
        </td>
    <?php endif; ?>

    <?php if ($row->isRenderColumn(FileSystemItemRow::COLUMN_SIZE)) : ?>
        <td class="d-none d-sm-table-cell text-end">
            <div class="size float-end">
                <?php if (!$row->getSize()) : ?>
                    -
                <?php else : ?>
                    <?= Yii::$app->formatter->asShortSize($row->getSize(), 1); ?>
                <?php endif; ?>
            </div>
        </td>
    <?php endif; ?>

    <?php if ($row->isRenderColumn(FileSystemItemRow::COLUMN_TIMESTAMP)) : ?>
        <td class="d-none d-sm-table-cell text-end">
            <div class="timestamp float-end">
                <?= $row->getUpdatedAt() ? TimeAgo::widget(['timestamp' => $row->getUpdatedAt()]) : ""; ?>
            </div>
        </td>
    <?php endif; ?>

    <?php if (Yii::$app->getModule('cfiles')->settings->get('displayDownloadCount') &&
              $row->isRenderColumn(FileSystemItemRow::COLUMN_DOWNLOAD_COUNT)) : ?>
        <td class="d-none d-sm-table-cell text-end">
            <div class="float-end">
                <?= $row->getDownloadCount(); ?>
            </div>
        </td>
    <?php endif; ?>

    <?php if ($row->isRenderColumn(FileSystemItemRow::COLUMN_SOCIAL)): ?>
        <td class="d-none d-sm-table-cell text-end">
            <?php if ($row->isSocialActionsAvailable()): ?>
                <div class="file-controls float-end">
                    <?= ContentObjectLinks::widget([
                        'object' => $row->getModel(),
                        'widgetParams' => [CommentLink::class => ['mode' => CommentLink::MODE_POPUP]],
                    ]); ?>
                </div>
            <?php endif; ?>
        </td>
    <?php endif; ?>

    <?php if ($row->isRenderColumn(FileSystemItemRow::COLUMN_CREATOR)): ?>
        <td class="text-end">
            <div class="creator float-end">
                <?php if (!empty($row->getCreator())): ?>
                    <?= Image::widget(['user' => $row->getCreator(), 'width' => 21, 'showTooltip' => true]) ?>
                <?php endif; ?>
            </div>
        </td>
    <?php endif; ?>

    <?php if ($row->isRenderColumn(FileSystemItemRow::COLUMN_ACTIONS)): ?>
        <td class="file-actions">
            <?= Button::light()
                ->icon('list')
                ->options(['data-contextmenu-toggler' => '#bs-table tr'])
                ->cssClass('context-icon')
                ->sm()
                ->loader(false); ?>
        </td>
    <?php endif; ?>

<?= Html::endTag('tr') ?>
