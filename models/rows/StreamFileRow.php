<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2017 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 *
 */

namespace humhub\modules\cfiles\models\rows;


class StreamFileRow extends BaseFileRow
{
    const DEFAULT_ORDER = ['file.updated_at' => SORT_ASC, 'file.title' => SORT_ASC];

    const ORDER_MAPPING = [
        self::ORDER_TYPE_NAME => 'file.file_name',
        self::ORDER_TYPE_UPDATED_AT => 'file.updated_at',
        self::ORDER_TYPE_SIZE => 'cast(file.size as unsigned)',
    ];
}