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
    public $editRoute = '/cfiles/browse/index';

    /**
     * @inheritdoc
     */
    public $editMode = self::EDIT_MODE_NEW_WINDOW;

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
            'folderUrl' => $this->model->getUrl(),
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
            'edit' => 'folder:' . $this->model->id,
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

}
