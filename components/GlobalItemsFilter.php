<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2026 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\cfiles\components;

use humhub\components\listing\FilterDefinition;
use humhub\components\listing\FilterValue;
use humhub\components\listing\ListBuilder;
use humhub\components\listing\ListContext;
use humhub\components\listing\ListFilter;

/**
 * A filter of the file browser ({@see FolderList::contentFilters()}) on the global files page:
 * the same parameter, validation and definition, but applied to the items of a
 * {@see GlobalListBuilder} once they exist ({@see GlobalListBuilder::onItems()}) — the
 * filter itself narrows a {@see FolderListBuilder} and knows nothing of the global page.
 *
 * @since 1.0
 */
final class GlobalItemsFilter extends ListFilter
{
    public function __construct(public readonly ListFilter $filter)
    {
    }

    public function key(): string
    {
        return $this->filter->key();
    }

    public function params(): array
    {
        return $this->filter->params();
    }

    public function isAvailable(ListContext $context): bool
    {
        return $this->filter->isAvailable($context);
    }

    public function parse(array $raw, ListContext $context): FilterValue
    {
        return $this->filter->parse($raw, $context);
    }

    /**
     * @param GlobalListBuilder $list
     */
    public function apply(ListBuilder $list, FilterValue $value, ListContext $context): void
    {
        $list->onItems(fn(FolderListBuilder $items) => $this->filter->apply($items, $value, $context));
    }

    public function definition(ListContext $context): ?FilterDefinition
    {
        return $this->filter->definition($context);
    }
}
