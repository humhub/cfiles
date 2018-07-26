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
    const DEFAULT_ORDER = null;

    const ORDER_MAPPING = [
        self::ORDER_TYPE_NAME => null,
        self::ORDER_TYPE_UPDATED_AT => null,
        self::ORDER_TYPE_SIZE => null,
    ];

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

    public function canEdit()
    {
        return false;
    }
}