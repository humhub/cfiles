<?php

use humhub\helpers\Html;
use humhub\modules\cfiles\models\Folder;
use humhub\modules\cfiles\widgets\FileSelectionMenu;
use humhub\modules\content\components\ContentContainerActiveRecord;
use humhub\modules\file\widgets\FileHandlerButtonDropdown;
use humhub\modules\file\widgets\UploadButton;
use humhub\modules\file\widgets\UploadInput;
use humhub\widgets\bootstrap\Button;
use humhub\widgets\bootstrap\Link;
use humhub\widgets\modal\ModalButton;

/* @var $folder Folder */
/* @var $contentContainer ContentContainerActiveRecord */
/* @var $canUpload boolean */
/* @var $zipEnabled boolean */
/* @var $fileHandlers humhub\modules\file\handler\BaseFileHandler[] */

$zipAllUrl = $contentContainer->createUrl('/cfiles/zip/download', ['fid' => $folder->id]);
$zipUploadUrl = $contentContainer->createUrl('/cfiles/zip/upload', ['fid' => $folder->id]);

$addFolderUrl = $contentContainer->createUrl('/cfiles/edit/folder', ['fid' => $folder->id]);
$editFolderUrl = $contentContainer->createUrl('/cfiles/edit/folder', ['id' => $folder->getItemId()]);

$uploadUrl = $contentContainer->createUrl('/cfiles/upload', ['fid' => $folder->id]);
?>

<div class="clearfix files-action-menu">
    <?= FileSelectionMenu::widget([
        'folder' => $folder,
        'contentContainer' => $contentContainer,
    ]);?>

    <?php if ($folder->parentFolder) : ?>
        <?= Button::back($folder->parentFolder->getUrl(), '')->left() ?>
    <?php endif; ?>

    <!-- FileList main menu -->
    <?php if (!$folder->isAllPostedFiles()): ?>
        <div style="display:block;" class="float-end">

            <!-- Directory dropdown -->
            <?php if ($canUpload): ?>
                <div class="btn-group">
                    <?= ModalButton::light(Html::tag('span', Yii::t('CfilesModule.base', 'Add directory'), ['class' => 'd-none d-sm-inline']))
                        ->load($addFolderUrl)
                        ->icon('fa-folder') ?>
                    <?php if (!$folder->isRoot()): ?>
                        <button id="directory-toggle" type="button" class="btn btn-light dropdown-toggle" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="true">
                            <span class="sr-only"></span>
                        </button>
                        <ul id="folder-dropdown" class="dropdown-menu">
                            <li>
                               <?= Link::modal(Yii::t('CfilesModule.base', 'Edit directory'))
                                   ->load($editFolderUrl)
                                   ->icon('fa-pencil')
                                   ->cssClass('dropdown-item') ?>
                            </li>
                        </ul>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <!-- Upload Dropdown -->
            <?php if ($canUpload): ?>
                <?php  $uploadButton = UploadButton::widget([
                    'id' => 'cfilesUploadFiles',
                    'progress' => '#cfiles_progress',
                    'url' => $uploadUrl,
                    'preview' => '#cfiles-folderView',
                    'tooltip' => false,
                    'cssButtonClass' => 'btn-accent',
                    'label' => Html::tag('span', Yii::t('CfilesModule.base', 'Add file(s)'), ['class' => 'd-none d-sm-inline']),
                    'dropZone' => '#cfiles-container',
                    'pasteZone' => 'body',
                ]) ?>
                <?= FileHandlerButtonDropdown::widget(['primaryButton' => $uploadButton, 'handlers' => $fileHandlers, 'cssButtonClass' => 'btn-accent', 'pullRight' => true]); ?>

                <?= UploadInput::widget([
                    'id' => 'cfilesUploadZipFile',
                    'progress' => '#cfiles_progress',
                    'url' => $zipUploadUrl,
                    'preview' => '#cfiles-folderView',
                ]) ?>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>
