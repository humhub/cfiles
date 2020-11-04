<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2017 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\cfiles\widgets;

use humhub\modules\cfiles\models\Folder;
use humhub\modules\content\widgets\stream\WallStreamModuleEntryWidget;

/**
 * Wall Entry for Folder
 * 
 * Used for Search
 */
class WallEntryFolder extends WallStreamModuleEntryWidget
{

    /**
     * @inheritdoc
     */
    public $editRoute = '/cfiles/edit/folder';

    /**
     * @inheritdoc
     */
    public $editMode = self::EDIT_MODE_MODAL;

    /**
     * @var Folder
     */
    public $model;

    /**
     * @inheritdoc
     */
    public function renderContent()
    {
        return $this->render('wallEntryFolder', [
                    'folder' => $this->model,
                    'folderUrl' => $this->model->getUrl()
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

        if ($this->model instanceof Folder) {
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
