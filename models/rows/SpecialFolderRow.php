<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2017 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 *
 */

namespace humhub\modules\cfiles\models\rows;

/**
 * Created by PhpStorm.
 * User: buddha
 * Date: 31.08.2017
 * Time: 00:44
 */

class SpecialFolderRow extends FolderRow
{
    /**
     * @inheritdoc
     */
    public function isSocialActionsAvailable()
    {
        return false;
    }

    /**
     * @inheritdoc
     */
    public function isSelectable()
    {
        return false;
    }

    /**
     * @inheritdoc
     */
    public function getCreator()
    {
        // do not display creator of automatically generated folders
        return false;
    }

    /**
     * @inheritdoc
     */
    public function getEditor()
    {
        // do not display editor of automatically generated folders
        return false;
    }

    /**
     * @inheritdoc
     */
    public function getUpdatedAt()
    {
        return ($this->item->isAllPostedFiles()) ? '' : parent::getUpdatedAt();
    }
}