<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\cfiles\components;

use humhub\components\listing\FilterValue;
use humhub\components\listing\ListBuilder;
use humhub\components\listing\ListContext;
use humhub\components\listing\ListFilter;
use humhub\modules\cfiles\models\Folder;

/**
 * `parent`: the folder whose contents are listed — a context filter, without UI. Absent (or
 * `0`) is the container's top level, which has no folder record of its own.
 *
 * It authorizes itself while parsing: the folder has to exist, be readable by the caller and
 * belong to the list's container ({@see ListContext::$container}); anything else is an error
 * for `parent`. Unknown, unreadable and foreign folders are deliberately indistinguishable, so
 * the list does not confirm the existence of content the caller may not see.
 *
 * @since 1.0
 */
class FolderParentFilter extends ListFilter
{
    public const KEY = 'parent';

    /**
     * @inheritdoc
     */
    public function key(): string
    {
        return self::KEY;
    }

    /**
     * @inheritdoc
     */
    public function parse(array $raw, ListContext $context): FilterValue
    {
        $value = $raw[self::KEY] ?? null;

        if ($value === null || $value === '' || $value === 0 || $value === '0') {
            return FilterValue::absent();
        }

        if ((!is_string($value) && !is_int($value)) || !ctype_digit((string)$value)) {
            return $this->invalid($this->unknownValue($value));
        }

        $folder = $context->container === null ? null : Folder::find()
            ->contentContainer($context->container)
            ->readable()
            ->andWhere(['cfiles_folder.id' => (int)$value])
            ->one();

        return $folder instanceof Folder ? FilterValue::of($folder) : $this->invalid($this->unknownValue($value));
    }

    /**
     * @inheritdoc
     * @param FolderListBuilder $list
     */
    public function apply(ListBuilder $list, FilterValue $value, ListContext $context): void
    {
        $list->setFolder($value->value);
    }
}
