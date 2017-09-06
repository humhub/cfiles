<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2017 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 *
 */

namespace humhub\modules\cfiles\models\rows;

use Yii;

/**
 * Created by PhpStorm.
 * User: buddha
 * Date: 30.08.2017
 * Time: 23:34
 */

class FolderRow extends FileSystemItemRow
{

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
}