<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\cfiles\extensions\custom_pages\elements;

use humhub\libs\Html;
use humhub\modules\cfiles\models\File;
use humhub\modules\custom_pages\modules\template\elements\BaseContentRecordElement;
use humhub\modules\custom_pages\modules\template\elements\BaseElementVariable;
use humhub\modules\file\models\File as BaseFile;
use Yii;

/**
 * Class to manage content record of the File
 *
 * @property-read File|null $record
 */
class FileElement extends BaseContentRecordElement
{
    protected const RECORD_CLASS = File::class;

    /**
     * @inheritdoc
     */
    public function getLabel(): string
    {
        return Yii::t('CfilesModule.base', 'File (Module)');
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'contentRecordId' => Yii::t('CfilesModule.base', 'File ID'),
        ];
    }

    public function __toString()
    {
        if ($this->hasFile()) {
            return $this->getFile()->getUrl();
        }

        return Html::encode($this->record?->description);
    }

    /**
     * @inheritdoc
     */
    public function getTemplateVariable(): BaseElementVariable
    {
        return FileElementVariable::instance($this)->setRecord($this->getRecord());
    }

    /**
     * Get File
     *
     * @return BaseFile|null
     */
    public function getFile(): ?BaseFile
    {
        return empty($this->contentRecordId)
            ? null
            : BaseFile::findOne([
                'object_model' => File::class,
                'object_id' => $this->contentRecordId,
            ]);
    }

    /**
     * Check if a File is found for this Element
     *
     * @return bool
     */
    public function hasFile(): bool
    {
        return $this->getFile() !== null;
    }
}
