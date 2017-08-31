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

class FileRow extends FileSystemItemRow
{

    /**
     * @var \humhub\modules\cfiles\models\File
     */
    public $item;

    /**
     * @inheritdoc
     */
    public function getUrl()
    {
        return $this->item->getDownloadUrl(true);
    }

    /**
     * @return string
     */
    public function getVisibilityTitle()
    {
       return  $this->item->content->isPublic()
           ?  Yii::t('CfilesModule.base', 'This file is public.')
           : Yii::t('CfilesModule.base', 'This file is private.');
    }

    /**
     * @inheritdoc
     */
    public function getBaseFile()
    {
        return $this->item->baseFile;
    }
}