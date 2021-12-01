<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2015 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\cfiles\widgets;

use humhub\modules\cfiles\models\Folder;
use humhub\modules\cfiles\models\rows\AbstractFileSystemItemRow;
use humhub\widgets\JsWidget;

/**
 * @inheritdoc
 */
class FileSystemItem extends JsWidget
{

    /**
     * @inheritdoc
     */
    public $jsWidget = 'cfiles.FileItem';

    /**
     * @var Folder
     */
    public $folder;

    /**
     * @var AbstractFileSystemItemRow
     */
    public $row;

    /**
     * @var boolean
     */
    public $itemsSelectable = true;

    /**
     * @inheritdoc
     */
    public function run()
    {
        $this->row->showSelect = $this->itemsSelectable;

        return $this->render('fileSystemItem', [
            'folder' => $this->folder,
            'row' => $this->row,
            'options' => $this->getOptions()
        ]);
    }

    public function getData() {
        return [
            'cfiles-item' => $this->row->getItemId(),
            'cfiles-content' => $this->row->getContentId(),
            'cfiles-type' => $this->row->getType(),
            'cfiles-url' => $this->row->getUrl(),
            'cfiles-editable' => $this->row->canEdit(),
            'cfiles-url-full' => $this->row->getDisplayUrl(),
            'cfiles-wall-url' => $this->row->getWallUrl(),
            'cfiles-edit-url' => ($this->row->canEdit()) ? $this->row->getEditUrl() : '',
            'cfiles-move-url' => ($this->row->canEdit()) ? $this->row->getMoveUrl() : '',
            'cfiles-versions-url' => ($this->row->canEdit()) ? $this->row->getVersionsUrl() : '',
        ];
    }

}
