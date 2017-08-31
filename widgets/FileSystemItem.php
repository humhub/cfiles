<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2015 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\cfiles\widgets;

use humhub\modules\cfiles\models\File;
use humhub\modules\cfiles\models\Folder;
use humhub\modules\cfiles\models\rows\AbstractFileSystemItemRow;
use Yii;

/**
 * @inheritdoc
 */
class FileSystemItem extends \yii\base\Widget
{
    /**
     * @var AbstractFileSystemItemRow
     */
    public $row;

    /**
     * @var boolean
     */
    public $canWrite;

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
            'row' => $this->row,
            'canWrite' => $this->canWrite,

        ]);
    }

}
