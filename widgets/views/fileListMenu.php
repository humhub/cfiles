<?php

use humhub\modules\file\widgets\FileHandlerButtonDropdown;

$deleteSelectionUrl = $contentContainer->createUrl('/cfiles/delete', ['fid' => $folder->id]);
$moveSelectionUrl = $contentContainer->createUrl('/cfiles/move', ['init' => 1, 'fid' => $folder->id]);

$zipSelectionUrl = $contentContainer->createUrl('/cfiles/zip/download');
$zipAllUrl = $contentContainer->createUrl('/cfiles/zip/download', ['fid' => $folder->id]);

$addFolderUrl = $contentContainer->createUrl('/cfiles/edit/folder', ['fid' => $folder->id]);
$editFolderUrl = $contentContainer->createUrl('/cfiles/edit/folder', ['id' => $folder->getItemId()]);

$uploadUrl = $contentContainer->createUrl('/cfiles/upload', ['fid' => $folder->id]);
?>

<div class="clearfix files-action-menu">
    <!-- selection menu -->
    <?php if ($canWrite || $zipEnabled): ?>
        <div class="selectedOnly pull-left">
            <div class="btn-group">
                <button class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
                    (<span class='chkCnt'></span>) <?= Yii::t('CfilesModule.base', 'Selected items...') ?> <span class="caret"></span>
                </button>
                <ul class="dropdown-menu">
                    <?php if ($canWrite): ?>
                        <li>
                            <a href="#" class="selectedOnly filedelete-button" style="display:none" 
                               data-action-click="deleteSelection" 
                               data-action-submit 
                               data-action-url="<?= $deleteSelectionUrl ?>">
                                <i class="fa fa-trash"></i> <?= Yii::t('CfilesModule.base', 'Delete') ?>
                            </a>
                        </li>
                        <li>
                            <a href="#" class="selectedOnly filemove-button" style="display:none" 
                               data-action-click="cfiles.move"
                               data-action-submit 
                               data-fid="<?= $folder->id ?>" 
                               data-action-url="<?= $moveSelectionUrl ?>">
                                <i class="fa fa-arrows"></i> <?= Yii::t('CfilesModule.base', 'Move') ?>
                            </a>
                        </li>
                    <?php endif; ?>
                    <?php if ($zipEnabled) : ?>
                        <li>
                            <a href="#" class="selectedOnly" style="display:none"
                               data-action-click="zipSelection"
                               data-action-submit
                               data-action-url="<?= $zipSelectionUrl; ?>">

                                <i class="fa fa-download"></i> <?= Yii::t('CfilesModule.base', 'ZIP selected') ?>
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    <?php endif; ?>

    <!-- FileList main menu -->
    <?php if ($folder->isAllPostedFiles() && $zipEnabled): ?>
        <div style="display:block;" class="pull-right">
            <a href="<?= $zipAllUrl ?>" data-pjax-prevent class="btn btn-default hasItems">
                <i class="fa fa-download"></i> <?= Yii::t('CfilesModule.base', 'ZIP all') ?>
            </a>
        </div>
    <?php elseif (!$folder->isAllPostedFiles()): ?>
        <div style="display:block;" class="pull-right">

            <!-- Directory dropdown -->
            <?php if ($canWrite): ?>
                <div class="btn-group">
                    <a href="#"  data-action-click="ui.modal.load" data-action-url="<?= $addFolderUrl ?>" class="btn btn-default overflow-ellipsis">
                        <i class="fa fa-folder"></i> &nbsp;<?= Yii::t('CfilesModule.base', 'Add directory') ?>
                    </a>
                    <?php if (!$folder->isRoot()): ?>
                        <button id="directory-toggle" type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="true">
                            <span class="caret"></span><span class="sr-only"></span>
                        </button>
                        <ul id="folder-dropdown" class="dropdown-menu">
                            <li class="visible">
                                <a href="#" data-action-click="ui.modal.load" data-action-url="<?= $editFolderUrl ?>">
                                    <i class="fa fa-folder"></i> <?= Yii::t('CfilesModule.base', 'Edit directory') ?>
                                </a>
                            </li>
                        </ul>
                    <?php endif; ?>
                </div>
            <?php endif; ?>    

            <!-- Upload Dropdown -->
            <?php if ($canWrite): ?>
                <?php
                $uploadButton = humhub\modules\file\widgets\UploadButton::widget([
                            'id' => 'cfilesUploadFiles',
                            'progress' => '#cfiles_progress',
                            'url' => $uploadUrl,
                            'preview' => '#cfiles-folderView',
                            'tooltip' => false,
                            'cssButtonClass' => 'btn-success',
                            'label' => Yii::t('CfilesModule.base', 'Add file(s)'),
                            'dropZone' => '#cfiles-container'
                        ])
                ?>

                <?= FileHandlerButtonDropdown::widget(['primaryButton' => $uploadButton, 'handlers' => $fileHandlers, 'cssButtonClass' => 'btn-success', 'pullRight' => true]); ?>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>
