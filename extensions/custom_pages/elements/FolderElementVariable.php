<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\cfiles\extensions\custom_pages\elements;

use humhub\modules\cfiles\models\Folder;
use humhub\modules\custom_pages\modules\template\elements\BaseContentRecordElementVariable;
use humhub\modules\custom_pages\modules\template\elements\BaseRecordElementVariable;
use yii\db\ActiveRecord;

class FolderElementVariable extends BaseContentRecordElementVariable
{
    public ?string $title = null;
    public ?string $description = null;
    public ?string $type = null;
    public ?string $icon = null;

    /**
     * @var FolderElementVariable[]
     */
    public array $subFolders = [];

    /**
     * @var FileElementVariable[]
     */
    public array $subFiles = [];

    public function setRecord(?ActiveRecord $record): BaseRecordElementVariable
    {
        if ($record instanceof Folder) {
            $this->title = $record->title;
            $this->description = $record->description;
            $this->type = $record->type;
            $this->icon = $record->getIcon();

            foreach ($record->subFolders as $subFolder) {
                $this->subFolders[] = self::instance($this->elementContent)->setRecord($subFolder);
            }

            foreach ($record->subFiles as $file) {
                $this->subFiles[] = FileElementVariable::instance($this->elementContent)->setRecord($file);
            }
        }

        return parent::setRecord($record);
    }
}
