<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2017 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 *
 */

namespace humhub\modules\cfiles\models\rows;

use humhub\modules\cfiles\widgets\WallEntryFolder;
use humhub\modules\content\widgets\stream\WallStreamModuleEntryWidget;

/**
 * Created by PhpStorm.
 * User: buddha
 * Date: 30.08.2017
 * Time: 23:34
 */

class FolderRow extends FileSystemItemRow
{

    const DEFAULT_ORDER = 'title ASC';

    const ORDER_MAPPING = [
        self::ORDER_TYPE_NAME => 'title',
        self::ORDER_TYPE_UPDATED_AT => 'content.updated_at',
        self::ORDER_TYPE_SIZE => null,
    ];

    /**
     * @var \humhub\modules\cfiles\models\Folder
     */
    public $item;

    /**
     * @inheritdoc
     */
    public function getUrl()
    {
        return $this->item->getUrl();
    }

    /**
     * @inheritdoc
     */
    public function getBaseFile()
    {
        return null;
    }

    /**
     * @return boolean
     */
    public function canEdit()
    {
        return $this->item->content->canEdit();
    }

    /**
     * @inheritdoc
     */
    public function getContext(): WallStreamModuleEntryWidget
    {
        return new WallEntryFolder(['model' => $this->item]);
    }
}