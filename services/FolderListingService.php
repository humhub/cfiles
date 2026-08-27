<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\cfiles\services;

use humhub\modules\cfiles\models\File;
use humhub\modules\cfiles\models\Folder;
use humhub\modules\cfiles\Module;
use humhub\modules\content\components\ContentContainerActiveRecord;
use humhub\modules\cfiles\serializers\FileSerializer;
use humhub\modules\cfiles\serializers\FolderSerializer;
use Yii;
use yii\data\Pagination;
use yii\db\ActiveQuery;

/**
 * The contents of one folder, as the file browser needs them.
 *
 * Used from both ends of the same payload: the API endpoint
 * ({@see \humhub\modules\cfiles\controllers\api\FolderController::actionView()}) and the page
 * controller, which embeds the first page in the island's props so the first paint needs no
 * request at all. One implementation, so the two cannot disagree.
 *
 * @since 1.0
 */
class FolderListingService
{
    /**
     * @var int the largest page a caller may ask for
     */
    public const MAX_PAGE_SIZE = 200;

    public const DEFAULT_PAGE_SIZE = 50;

    /**
     * Sort keys the listing understands, mapped to the order expression of each row type.
     * A null means the type cannot be sorted that way and falls back to its name — folders
     * have no size, so sorting a mixed listing by size still has to put them somewhere.
     */
    public const SORT_COLUMNS = [
        'name' => ['folder' => 'cfiles_folder.title', 'file' => 'file.file_name'],
        'size' => ['folder' => null, 'file' => 'cast(file.size as unsigned)'],
        'updatedAt' => ['folder' => 'content.updated_at', 'file' => 'file.updated_at'],
        'downloadCount' => ['folder' => null, 'file' => 'cfiles_file.download_count'],
    ];

    private FolderContentService $content;

    /**
     * @param Folder|null $folder the folder to list, or null for the container's top level.
     */
    public function __construct(
        private ContentContainerActiveRecord $container,
        private ?Folder $folder = null,
    ) {
        $this->content = new FolderContentService($container, $folder);
    }

    /**
     * The folder, its path from the root, and one page of its contents with folders sorted
     * ahead of files.
     */
    public function payload(?string $sort = null, ?string $order = null, int $page = 1, int $pageSize = self::DEFAULT_PAGE_SIZE): array
    {
        [$sort, $sortOrder] = $this->resolveSortOrder($sort, $order);

        $folderQuery = $this->subFolderQuery($sort, $sortOrder);
        $fileQuery = $this->subFileQuery($sort, $sortOrder);

        $folderCount = (int)(clone $folderQuery)->count();
        $fileCount = (int)(clone $fileQuery)->count();

        $pagination = new Pagination(['totalCount' => $folderCount + $fileCount]);
        $pagination->setPageSize(max(1, min($pageSize, self::MAX_PAGE_SIZE)));
        $pagination->setPage(max(1, $page) - 1);

        return [
            // null at the top level: there is no folder record standing in for it.
            'folder' => $this->folder === null ? null : FolderSerializer::folder($this->folder),
            'path' => FolderSerializer::path($this->folder),
            'sort' => $sort,
            'order' => $sortOrder === SORT_DESC ? 'desc' : 'asc',
            'results' => $this->page($folderQuery, $folderCount, $fileQuery, $pagination),
            'total' => (int)$pagination->totalCount,
            'page' => $pagination->getPage() + 1,
            'pageSize' => $pagination->getPageSize(),
            'pages' => $pagination->getPageCount(),
        ];
    }

    /**
     * The sort to apply, remembered per user.
     *
     * The browser has no settings screen to persist a sort through, so the listing owns it,
     * exactly as the old `FileList` widget did: an explicit sort both sorts and is remembered,
     * and a request without one gets what the user last chose, falling back to the module
     * default.
     *
     * @return array{0: string, 1: int}
     */
    private function resolveSortOrder(?string $sort, ?string $order): array
    {
        /** @var Module $module */
        $module = Yii::$app->getModule('cfiles');

        $sortOrder = strtolower((string)$order) === 'desc' ? SORT_DESC : SORT_ASC;

        if ($sort !== null && !isset(self::SORT_COLUMNS[$sort])) {
            $sort = null;
        }

        if (Yii::$app->user->isGuest) {
            return $sort === null
                ? [$module->defaultSort, (int)$module->defaultOrder]
                : [$sort, $sortOrder];
        }

        $settings = $module->settings->user(Yii::$app->user->getIdentity());

        if ($sort !== null) {
            $settings->set('defaultSort', $sort);
            $settings->set('defaultOrder', $sortOrder);

            return [$sort, $sortOrder];
        }

        $stored = (string)$settings->get('defaultSort', $module->defaultSort);

        return [
            isset(self::SORT_COLUMNS[$stored]) ? $stored : $module->defaultSort,
            (int)$settings->get('defaultOrder', $module->defaultOrder),
        ];
    }

    /**
     * The folder's own queries, ordered by the requested sort.
     *
     * A null column means the type cannot be sorted that way (a folder has no size); it falls
     * back to the name so a mixed listing still has a defined order.
     */
    private function subFolderQuery(string $sort, int $order): ActiveQuery
    {
        $column = self::SORT_COLUMNS[$sort]['folder'] ?? null;

        return $this->content->subFolderQuery(
            [$column ?? 'cfiles_folder.title' => $column === null ? SORT_ASC : $order],
        );
    }

    private function subFileQuery(string $sort, int $order): ActiveQuery
    {
        $column = self::SORT_COLUMNS[$sort]['file'] ?? null;

        return $this->content->subFileQuery(
            [$column ?? 'file.file_name' => $column === null ? SORT_ASC : $order],
        );
    }

    /**
     * One page of a listing in which folders always precede files.
     *
     * Two ordered queries rather than a UNION, so both keep the `readable()` content scope
     * that decides what this caller may see at all. The page is cut across the seam: it takes
     * whatever folders fall inside the window and fills the rest with files.
     *
     * @return array[]
     */
    private function page(ActiveQuery $folderQuery, int $folderCount, ActiveQuery $fileQuery, Pagination $pagination): array
    {
        $offset = $pagination->offset;
        $limit = $pagination->limit;

        $folders = $offset < $folderCount
            ? $folderQuery->offset($offset)->limit($limit)->all()
            : [];

        $remaining = $limit - count($folders);
        $files = $remaining > 0
            ? $fileQuery->offset(max(0, $offset - $folderCount))->limit($remaining)->all()
            : [];

        $itemCounts = $this->countChildren($folders);

        return array_merge(
            array_map(
                static fn(Folder $subFolder) => FolderSerializer::folder($subFolder, $itemCounts[$subFolder->id] ?? 0),
                $folders,
            ),
            array_map(FileSerializer::file(...), $files),
        );
    }

    /**
     * How many items each of the given folders holds, in two grouped queries rather than two
     * per folder.
     *
     * Deliberately counts without the `readable()` scope: a count is not a disclosure of what
     * is inside, and running the content-permission scope per folder would cost far more than
     * the number it produces is worth.
     *
     * @param Folder[] $folders
     * @return array<int, int>
     */
    private function countChildren(array $folders): array
    {
        if ($folders === []) {
            return [];
        }

        $ids = array_map(static fn(Folder $folder) => (int)$folder->id, $folders);
        $counts = array_fill_keys($ids, 0);

        foreach ([Folder::find(), File::find()] as $query) {
            $rows = $query
                ->select(['parent_folder_id', 'total' => 'COUNT(*)'])
                ->where(['parent_folder_id' => $ids])
                ->groupBy('parent_folder_id')
                ->asArray()
                ->all();

            foreach ($rows as $row) {
                $counts[(int)$row['parent_folder_id']] += (int)$row['total'];
            }
        }

        return $counts;
    }
}
