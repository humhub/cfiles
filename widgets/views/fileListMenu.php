<?php

use humhub\modules\cfiles\widgets\FileSelectionMenu;
use humhub\modules\file\widgets\FileHandlerButtonDropdown;
use humhub\modules\file\widgets\UploadButton;
use humhub\modules\file\widgets\UploadInput;
use humhub\widgets\Button;
use humhub\widgets\ModalButton;

/* @var $folder \humhub\modules\cfiles\models\Folder */
/* @var $contentContainer \humhub\modules\content\components\ContentContainerActiveRecord */
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

    <?php if($folder->parentFolder) : ?>
        <?= Button::back($folder->parentFolder->getUrl())->left()->setText('');  ?>
    <?php endif; ?>

    <!-- FileList main menu -->
    <?php if (!$folder->isAllPostedFiles()): ?>
        <div style="display:block;" class="pull-right">

            <!-- Directory dropdown -->
            <?php if ($canUpload): ?>
                <div class="btn-group">
                    <?= ModalButton::defaultType(Yii::t('CfilesModule.base', 'Add directory'))->load($addFolderUrl)->icon('fa-folder')->cssClass('dropdown-toggle')?>
                    <?php if (!$folder->isRoot()): ?>
                        <button id="directory-toggle" type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="true">
                            <span class="caret"></span><span class="sr-only"></span>
                        </button>
                        <ul id="folder-dropdown" class="dropdown-menu">
                            <li class="visible">
                               <?= ModalButton::asLink(Yii::t('CfilesModule.base', 'Edit directory'))->load($editFolderUrl)->icon('fa-pencil'); ?>
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
                            'cssButtonClass' => 'btn-success',
                            'label' => Yii::t('CfilesModule.base', 'Add file(s)'),
                            'dropZone' => '#cfiles-container',
                            'pasteZone' => 'body',
                 ])  ?>
                <?= FileHandlerButtonDropdown::widget(['primaryButton' => $uploadButton, 'handlers' => $fileHandlers, 'cssButtonClass' => 'btn-success', 'pullRight' => true]); ?>

                <?= UploadInput::widget([
                    'id' => 'cfilesUploadZipFile',
                    'progress' => '#cfiles_progress',
                    'url' => $zipUploadUrl,
                    'preview' => '#cfiles-folderView'
                ])  ?>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>
