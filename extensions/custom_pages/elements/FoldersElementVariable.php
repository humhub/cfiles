<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\cfiles\extensions\custom_pages\elements;

use humhub\modules\custom_pages\modules\template\elements\BaseElementVariableIterator;

class FoldersElementVariable extends BaseElementVariableIterator
{
    public function __construct(FoldersElement $elementContent)
    {
        parent::__construct($elementContent);

        foreach ($elementContent->getItems() as $folder) {
            $this->items[] = FolderElementVariable::instance($elementContent)->setRecord($folder);
        }
    }
}
