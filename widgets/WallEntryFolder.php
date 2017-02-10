<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2015 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\cfiles\widgets;

use humhub\modules\cfiles\models\Folder;

/**
 * @inheritdoc
 */
class WallEntryFolder extends \humhub\modules\content\widgets\WallEntry
{

    /**
     * @inheritdoc
     */
    public $editRoute = "/cfiles/edit/folder";

    /**
     * @inheritdoc
     */
    public function run()
    {
        return $this->render('wallEntryFolder', array('folder' => $this->contentObject));
    }

    /**
     * Returns the edit url to edit the content (if supported)
     *
     * @return string url
     */
    public function getEditUrl()
    {
        if (parent::getEditUrl() === "") {
            return "";
        }

        if ($this->contentObject instanceof Folder) {
            return $this->contentObject->content->container->createUrl($this->editRoute, ['id' => $this->contentObject->getItemId(), 'fromWall' => true]);
        }
        return "";
    }

}
