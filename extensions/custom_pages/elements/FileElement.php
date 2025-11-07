<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\cfiles\extensions\custom_pages\elements;

use humhub\helpers\Html;
use humhub\modules\cfiles\models\File;
use humhub\modules\content\models\Content;
use humhub\modules\custom_pages\modules\template\elements\BaseContentRecordElement;
use humhub\modules\custom_pages\modules\template\elements\BaseElementVariable;
use humhub\modules\file\models\File as BaseFile;
use Yii;

/**
 * Class to manage content record of the File
 *
 * @property-read File|null $record
 */
class FileElement extends BaseContentRecordElement implements \Stringable
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
            'contentId' => Yii::t('CfilesModule.base', 'File content ID'),
        ];
    }

    public function __toString(): string
    {
        if ($this->hasFile()) {
            return $this->getFile()->getUrl();
        }

        return (string) Html::encode($this->record?->description);
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
        return empty($this->contentId) ? null
            : BaseFile::find()
            ->innerJoin(Content::tableName(), Content::tableName() . '.object_model = ' . BaseFile::tableName() . '.object_model' . ' AND ' . Content::tableName() . '.object_id = ' . BaseFile::tableName() . '.object_id')
            ->where([BaseFile::tableName() . '.object_model' => File::class])
            ->andWhere([Content::tableName() . '.id' => $this->contentId])
            ->one();
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
