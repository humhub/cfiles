<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2026 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\cfiles\services;

use humhub\modules\cfiles\components\GlobalFolderList;
use humhub\modules\cfiles\components\GlobalListBuilder;
use humhub\modules\cfiles\models\File;
use humhub\modules\cfiles\models\Folder;
use humhub\modules\space\models\Space;
use humhub\modules\space\serializers\SpaceSerializer;
use yii\data\Pagination;
use yii\db\Query;

/**
 * One page of the global files page ({@see GlobalFolderList}): the payload of
 * `GET /api/v2/cfiles/items`. It has the keys of the file browser's payload
 * ({@see FolderListingService::payload()}), so the page reads both the same way. It adds
 * `global: true` and `space: null`: the page is in no space.
 *
 * In results mode the rows are the file browser's rows. Each has a `path` starting with its
 * space.
 *
 * Without a result filter the rows are one tile per space:
 * `{type: 'space', id, contentContainerId, guid, name, url, color, imageUrl, browseUrl, itemCount}`.
 * That is the short space shape of the core ({@see SpaceSerializer::short()}) plus two fields.
 * `browseUrl` is the space's own file browser. `itemCount` is the readable items at the top
 * level of the space. Note that a tile names the space in `name` (the core's shape), while a
 * row of the results has a `title`. Tiles have no like states: `likeStates` is empty.
 *
 * @since 1.0
 */
class GlobalListingService
{
    public function __construct(private readonly GlobalListBuilder $list)
    {
    }

    /**
     * @param int $page 1-based
     * @param int|null $pageSize null = the page size of the user's view
     *        ({@see FolderListingService::VIEWS}), at most {@see FolderListingService::MAX_PAGE_SIZE}
     */
    public function payload(int $page = 1, ?int $pageSize = null): array
    {
        $items = $this->list->resultsMode ? $this->list->items() : null;

        $payload = $items !== null
            ? (new FolderListingService($items))->payload($page, $pageSize)
            : $this->tiles($page, $pageSize);

        return ['global' => true, 'space' => null] + $payload;
    }

    /**
     * The tiles — or, in results mode without any space left, no hits.
     */
    private function tiles(int $page, ?int $pageSize): array
    {
        $view = (new BrowserPreferences($this->list->context->user))->view();
        $query = $this->list->spaceQuery();

        $pagination = new Pagination(['totalCount' => $this->list->resultsMode ? 0 : (int)(clone $query)->count()]);
        $pagination->setPageSize(max(1, min($pageSize ?? FolderListingService::VIEWS[$view], FolderListingService::MAX_PAGE_SIZE)));
        $pagination->setPage(max(1, $page) - 1);

        /** @var Space[] $spaces */
        $spaces = $pagination->totalCount === 0 ? [] : (clone $query)->offset($pagination->offset)->limit($pagination->limit)->all();
        $counts = $this->itemCounts($spaces);

        return [
            'folder' => null,
            'path' => [],
            'sort' => $this->list->sort,
            'view' => $view,
            'results' => array_map(static fn(Space $space) => ['type' => 'space'] + SpaceSerializer::short($space) + [
                'browseUrl' => $space->createUrl('/cfiles/browse/index'),
                'itemCount' => $counts[(int)$space->contentcontainer_id] ?? 0,
            ], $spaces),
            'resultsMode' => $this->list->resultsMode,
            // Nothing to add to: the top level holds the spaces.
            'canWrite' => false,
            'likeStates' => [],
            'total' => (int)$pagination->totalCount,
            'page' => $pagination->getPage() + 1,
            'pageSize' => $pagination->getPageSize(),
            'pages' => $pagination->getPageCount(),
        ];
    }

    /**
     * The readable top-level folders and files of each space, by its `contentcontainer_id` —
     * one grouped query over both, for the spaces of the page.
     *
     * @param Space[] $spaces
     * @return array<int, int>
     */
    private function itemCounts(array $spaces): array
    {
        if ($spaces === []) {
            return [];
        }

        $containerIds = array_map(static fn(Space $space) => (int)$space->contentcontainer_id, $spaces);

        // Distinct per item: the joins of `readable()` must not count an item twice.
        $items = static fn($query, string $table) => $query
            ->readable()
            ->select(['containerId' => 'content.contentcontainer_id', 'itemId' => $table . '.id'])
            ->distinct()
            ->andWhere([$table . '.parent_folder_id' => null, 'content.contentcontainer_id' => $containerIds]);

        $rows = (new Query())
            ->select(['containerId', 'total' => 'COUNT(*)'])
            ->from(['items' => $items(Folder::find(), 'cfiles_folder')->union($items(File::find(), 'cfiles_file'), true)])
            ->groupBy('containerId')
            ->all();

        return array_map('intval', array_column($rows, 'total', 'containerId'));
    }
}
