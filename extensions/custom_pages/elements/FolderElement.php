<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\cfiles\extensions\custom_pages\elements;

use humhub\helpers\Html;
use humhub\modules\cfiles\models\Folder;
use humhub\modules\custom_pages\modules\template\elements\BaseContentRecordElement;
use humhub\modules\custom_pages\modules\template\elements\BaseElementVariable;
use Yii;

/**
 * Class to manage content record of the Folder
 *
 * @property-read Folder|null $record
 */
class FolderElement extends BaseContentRecordElement
{
    protected const RECORD_CLASS = Folder::class;

    /**
     * @inheritdoc
     */
    public function getLabel(): string
    {
        return Yii::t('CfilesModule.base', 'Folder');
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'contentId' => Yii::t('CfilesModule.base', 'Folder content ID'),
        ];
    }

    public function __toString()
    {
        return Html::encode($this->record?->title);
    }

    /**
     * @inheritdoc
     */
    public function getTemplateVariable(): BaseElementVariable
    {
        return FolderElementVariable::instance($this)->setRecord($this->getRecord());
    }
}
