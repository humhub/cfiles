<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2017 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 *
 */

namespace humhub\modules\cfiles\models\rows;

use humhub\modules\cfiles\widgets\WallEntryFile;
use humhub\modules\content\widgets\stream\WallStreamModuleEntryWidget;

/**
 * Created by PhpStorm.
 * User: buddha
 * Date: 30.08.2017
 * Time: 23:34
 */

class FileRow extends FileSystemItemRow
{
    public const DEFAULT_ORDER = ['file.file_name' => SORT_ASC];

    public const ORDER_MAPPING = [
        self::ORDER_TYPE_NAME => 'file.file_name',
        self::ORDER_TYPE_UPDATED_AT => 'file.updated_at',
        self::ORDER_TYPE_DOWNLOAD_COUNT => 'cfiles_file.download_count',
        self::ORDER_TYPE_SIZE => 'cast(file.size as unsigned)',
    ];

    /**
     * @var \humhub\modules\cfiles\models\File
     */
    public $item;

    /**
     * @inheritdoc
     */
    public function getUrl()
    {
        return $this->item->content->container->createUrl('/cfiles/download', ['guid' => $this->item->baseFile->guid], true);
    }

    /**
     * @inheritdoc
     */
    public function getDisplayUrl()
    {
        return $this->getUrl();
    }

    /**
     * @inheritdoc
     */
    public function getBaseFile()
    {
        return $this->item->baseFile;
    }

    /**
     * @return bool
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
        return new WallEntryFile(['model' => $this->item]);
    }
}
