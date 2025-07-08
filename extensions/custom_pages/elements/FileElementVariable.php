<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\cfiles\extensions\custom_pages\elements;

use humhub\modules\cfiles\models\File;
use humhub\modules\custom_pages\modules\template\elements\BaseContentRecordElementVariable;
use humhub\modules\custom_pages\modules\template\elements\BaseElementContent;
use humhub\modules\custom_pages\modules\template\elements\BaseRecordElementVariable;
use humhub\modules\custom_pages\modules\template\elements\FileElementVariable as BaseFileElementVariable;
use yii\db\ActiveRecord;

class FileElementVariable extends BaseContentRecordElementVariable
{
    /**
     * @var BaseElementContent|FileElement
     */
    protected BaseElementContent $elementContent;

    public ?string $fileUrl;
    public ?string $description;
    public ?string $icon;
    public int $downloadCount;
    public BaseFileElementVariable $file;

    public function setRecord(?ActiveRecord $record): BaseRecordElementVariable
    {
        if ($record instanceof File) {
            $this->description = $record->description;
            $this->downloadCount = (int) $record->download_count;
            $this->icon = $record->getIcon();

            if ($record->baseFile->store->has()) {
                $this->fileUrl = $record->baseFile->getUrl();
                $this->file = BaseFileElementVariable::instance($this->elementContent)
                    ->setRecord($record->baseFile);
            }
        }

        return parent::setRecord($record);
    }
}
