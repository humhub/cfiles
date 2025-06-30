<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\cfiles\extensions\custom_pages\elements;

use humhub\modules\cfiles\models\File;
use humhub\modules\custom_pages\modules\template\elements\BaseContentRecordsElement;
use humhub\modules\custom_pages\modules\template\elements\BaseElementVariable;
use Yii;

/**
 * Class to manage content records of the elements with Files list
 */
class FilesElement extends BaseContentRecordsElement
{
    public const RECORD_CLASS = File::class;

    /**
     * @inheritdoc
     */
    public function getLabel(): string
    {
        return Yii::t('CfilesModule.base', 'Files (Module)');
    }

    /**
     * @inheritdoc
     */
    public function getTemplateVariable(): BaseElementVariable
    {
        return new FilesElementVariable($this);
    }
}
