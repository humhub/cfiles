<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2015 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\cfiles\widgets;

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
    public $editRoute = '/cfiles/edit/file';

    /**
     * @inheritdoc
     */
    public $editMode = self::EDIT_MODE_MODAL;

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

        $folderUrl = '#';
        if ($cFile->parentFolder !== null) {
            $folderUrl = $cFile->parentFolder->getUrl();
        }

        return $this->render('wallEntryFile', [
            'cFile' => $cFile,
            'fileSize' => $cFile->getSize(),
            'file' => $cFile->baseFile,
            'previewImage' => new PreviewImage(),
            'folderUrl' => $folderUrl,
        ]);
    }

    /**
     * Returns the edit url to edit the content (if supported)
     *
     * @return string url
     */
    public function getEditUrl()
    {
        if (empty(parent::getEditUrl())) {
            return '';
        }

        if ($this->model instanceof File) {
            return $this->model->content->container->createUrl($this->editRoute, ['id' => $this->model->getItemId(), 'fromWall' => true]);
        }

        return '';
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

}
