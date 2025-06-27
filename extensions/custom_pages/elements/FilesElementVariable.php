<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\cfiles\extensions\custom_pages\elements;

use humhub\modules\custom_pages\modules\template\elements\BaseElementVariableIterator;

class FilesElementVariable extends BaseElementVariableIterator
{
    public function __construct(FilesElement $elementContent)
    {
        parent::__construct($elementContent);

        foreach ($elementContent->getItems() as $file) {
            $this->items[] = FileElementVariable::instance($elementContent)->setRecord($file);
        }
    }
}
