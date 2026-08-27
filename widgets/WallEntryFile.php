<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2015 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\cfiles\widgets;

use humhub\modules\cfiles\models\Folder;
use humhub\modules\content\models\Content;
use humhub\modules\content\widgets\stream\WallStreamModuleEntryWidget;
use humhub\modules\file\converter\PreviewImage;
use humhub\modules\cfiles\models\File;

/**
 * @inheritdoc
 */
class WallEntryFile extends WallStreamModuleEntryWidget
{
    /**
     * @inheritdoc
     */
    public $editRoute = '/cfiles/browse/index';

    /**
     * @inheritdoc
     */
    public $editMode = self::EDIT_MODE_NEW_WINDOW;

    /**
     * @var File
     */
    public $model;

    /**
     * @inheritdoc
     */
    public function renderContent()
    {
        $cFile = $this->model;

        return $this->render('wallEntryFile', [
            'cFile' => $cFile,
            'fileSize' => $cFile->getSize(),
            'file' => $cFile->baseFile,
            'previewImage' => new PreviewImage(),
            'folderUrl' => $this->getFolderUrl(),
        ]);
    }

    /**
     * Editing happens in the file browser, not in a modal of its own.
     *
     * The browser already owns the edit dialog; rendering a second one here would mean a
     * second form and a second render path for the same thing. The stream links into it
     * instead, with the folder to open and the item to edit.
     */
    public function getEditUrl()
    {
        if (empty(parent::getEditUrl())) {
            return '';
        }

        return $this->model->content->container->createUrl($this->editRoute, [
            'fid' => $this->model->parent_folder_id,
            'edit' => 'file:' . $this->model->id,
        ]);
    }

    /**
     * @return string
     */
    protected function getIcon()
    {
        return $this->model->getIcon();
    }

    /**
     * @return string a non encoded plain text title (no html allowed) used in the header of the widget
     */
    protected function getTitle()
    {
        return $this->model->getTitle();
    }

    protected function getFolderUrl(): ?string
    {
        if (!$this->model->parentFolder instanceof Folder) {
            return null;
        }

        if ($this->model->parentFolder->content->getStateService()->isDeleted()) {
            return null;
        }

        return $this->model->parentFolder->getUrl();
    }

}
