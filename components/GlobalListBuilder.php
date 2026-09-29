<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2026 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\cfiles\components;

use Closure;
use humhub\components\listing\ListBuilder;
use humhub\components\listing\ListContext;
use humhub\modules\space\models\Space;
use LogicException;
use yii\db\ActiveQuery;

/**
 * What the filters of a {@see GlobalFolderList} narrow. It holds the spaces of the global files
 * page, the trusted scope ({@see GlobalFolderList::scopeQuery()}). In results mode it also holds
 * the items across those spaces.
 *
 * Why the items come late: the core list model creates the builder before it knows the mode.
 * And a {@see FolderListBuilder} needs its containers when it is constructed. Those are the
 * spaces left after every restriction, and never an empty set. So this builder keeps the space
 * query, and the filters on the items are queued ({@see self::onItems()}). The first call of
 * {@see self::items()} builds the {@see FolderListBuilder} over the spaces left and replays the
 * queue on it. The service makes that call after EVENT_BUILD, so a restriction of the spaces on
 * EVENT_BUILD narrows the hits as well as the tiles.
 *
 * What handlers get:
 *
 * - A filter added on {@see GlobalFolderList::EVENT_INIT} gets this builder in its `apply`. It
 *   narrows the spaces with {@see self::narrowToSpaces()}, and the items with `onItems()`.
 * - An `onItems()` callback runs in results mode only. It never runs in tile mode, and not when
 *   no space is left.
 * - A handler of {@see GlobalFolderList::EVENT_BUILD} gets this builder too. It may narrow
 *   {@see self::spaceQuery()} or call `onItems()`.
 * - Filters added on {@see FolderList::EVENT_INIT} do not reach the global page: it is a list of
 *   its own.
 *
 * ```php
 * $builder->onItems(static function (FolderListBuilder $items) use ($value) {
 *     $items->fileQuery()->andWhere(...);
 * });
 * ```
 *
 * Without a result filter the page shows one tile per space: {@see self::spaceQuery()}, ordered
 * by the list, paged by {@see \humhub\modules\cfiles\services\GlobalListingService}.
 *
 * @since 1.0
 */
class GlobalListBuilder implements ListBuilder
{
    /**
     * @var string the sort the list was built with — a key of {@see FolderList::sortLabels()}
     */
    public string $sort = FolderList::SORT_DEFAULT;

    /**
     * @var bool whether the list shows the hits across the spaces rather than their tiles
     */
    public bool $resultsMode = false;

    /**
     * @var Closure[] the filters on the items, until they exist
     */
    private array $pending = [];

    private ?FolderListBuilder $items = null;

    private bool $itemsBuilt = false;

    /**
     * @param ActiveQuery $spaceQuery the spaces of the page — the trust boundary: every space
     *        it finds is one the caller may browse ({@see GlobalFolderList::scopeQuery()})
     */
    public function __construct(
        private readonly ActiveQuery $spaceQuery,
        public readonly ListContext $context,
    ) {
    }

    /**
     * The spaces of the page: the tiles, and in results mode the containers of the items. Only
     * ever narrowed.
     */
    public function spaceQuery(): ActiveQuery
    {
        return $this->spaceQuery;
    }

    /**
     * Narrows the spaces to the given ones. A space outside the scope stays out. Only before
     * the items are built.
     *
     * @param int[] $spaceIds
     */
    public function narrowToSpaces(array $spaceIds): void
    {
        if ($this->itemsBuilt) {
            throw new LogicException('The spaces of the items are fixed once the items are built.');
        }

        $this->spaceQuery->andWhere(['space.id' => $spaceIds]);
    }

    /**
     * Narrows the items: `$apply` gets their {@see FolderListBuilder} once it is built, or at
     * once if it is. It runs in results mode only, and not at all when no space is left.
     *
     * @param callable $apply `fn(FolderListBuilder $items)`
     */
    public function onItems(callable $apply): void
    {
        $apply = Closure::fromCallable($apply);

        if ($this->itemsBuilt) {
            if ($this->items !== null) {
                $apply($this->items);
            }

            return;
        }

        $this->pending[] = $apply;
    }

    /**
     * The items across the spaces left, in results mode; `null` in tile mode and when no space
     * is left.
     *
     * Built on the first call: the spaces are loaded (with their container records, so the rows
     * find them without a query), the {@see FolderListBuilder} is built over them with each
     * item's path starting with its space, the queued callbacks run (the filters, then the
     * order the list queued), and it is scoped to the whole tree of every space. Called after
     * the list is built — by {@see \humhub\modules\cfiles\services\GlobalListingService}.
     */
    public function items(): ?FolderListBuilder
    {
        if (!$this->resultsMode) {
            return null;
        }

        if ($this->itemsBuilt) {
            return $this->items;
        }
        $this->itemsBuilt = true;

        /** @var Space[] $spaces */
        $spaces = (clone $this->spaceQuery)->with('contentContainerRecord')->all();

        if ($spaces !== []) {
            $this->items = new FolderListBuilder($spaces, $this->context);
            $this->items->prefixContainer = true;
            foreach ($this->pending as $apply) {
                $apply($this->items);
            }
            $this->items->scopeToSubtree();
        }
        $this->pending = [];

        return $this->items;
    }
}
