<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\cfiles\services;

use humhub\models\RecordMap;
use humhub\modules\cfiles\components\FolderList;
use humhub\modules\cfiles\components\FolderListBuilder;
use humhub\modules\cfiles\models\File;
use humhub\modules\cfiles\models\Folder;
use humhub\modules\cfiles\models\FileSystemItem;
use humhub\modules\cfiles\permissions\WriteAccess;
use humhub\modules\cfiles\serializers\FileSerializer;
use humhub\modules\cfiles\serializers\FolderSerializer;
use humhub\modules\cfiles\serializers\ItemTopicSerializer;
use humhub\modules\like\serializers\LikeSerializer;
use Yii;
use yii\data\Pagination;
use yii\db\ActiveQuery;

/**
 * The contents of one folder, as the file browser needs them: one page of a level
 * {@see FolderList} built (which folder, filtered and ordered), cut across the folder/file seam
 * and serialized.
 *
 * Used from both ends of the same payload: the API endpoint
 * ({@see \humhub\modules\cfiles\controllers\api\FolderController::actionItems()}) and the page
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
     * How a listing may be displayed. A tile grid fits more on a screen than a row list, so
     * each brings its own page size.
     */
    public const VIEWS = [
        'list' => 50,
        'tiles' => 96,
    ];

    /**
     * Filled while serializing a page — see {@see self::collectLikeStates()}.
     *
     * @var array<int, array{total: int, liked: bool, canLike: bool}> record id => like state
     */
    private array $likeStates = [];

    /**
     * @param FolderListBuilder $list the level to list, filtered and ordered
     *        ({@see FolderList::build()})
     */
    public function __construct(private FolderListBuilder $list)
    {
    }

    /**
     * The folder, its path from the root, and one page of its contents with folders sorted
     * ahead of files.
     *
     * @param int|null $pageSize null = the page of the user's view ({@see self::VIEWS}): the
     *        view is a preference of its own, not a parameter of the list
     *        ({@see BrowserPreferences}), so a client asks for its view's page size itself
     */
    public function payload(int $page = 1, ?int $pageSize = null): array
    {
        $view = (new BrowserPreferences($this->list->context->user))->view();
        $pageSize ??= self::VIEWS[$view];
        $folder = $this->list->folder();

        $folderQuery = $this->list->folderQuery();
        $fileQuery = $this->list->fileQuery();

        $folderCount = (int)(clone $folderQuery)->count();
        $fileCount = (int)(clone $fileQuery)->count();

        $pagination = new Pagination(['totalCount' => $folderCount + $fileCount]);
        $pagination->setPageSize(max(1, min($pageSize, self::MAX_PAGE_SIZE)));
        $pagination->setPage(max(1, $page) - 1);

        $results = $this->page($folderQuery, $folderCount, $fileQuery, $pagination);

        return [
            // null at the top level: there is no folder record standing in for it.
            'folder' => $folder === null ? null : FolderSerializer::folder($folder),
            'path' => FolderSerializer::path($folder),
            // The key the list was built with — `default` when none was chosen.
            'sort' => $this->list->sort,
            'view' => $view,
            'results' => $results,
            // The hits in the open folder and all its subfolders, not the level: every row
            // then says where it lies (`path`, relative to the open folder).
            'resultsMode' => $this->list->resultsMode,
            'canWrite' => $this->canWrite(),
            // The one per-caller section of this payload. Kept out of the rows themselves,
            // which stay caller-neutral (see FileSerializer/FolderSerializer): who liked what
            // is about the reader, not about the file.
            'likeStates' => $this->likeStates,
            'total' => (int)$pagination->totalCount,
            'page' => $pagination->getPage() + 1,
            'pageSize' => $pagination->getPageSize(),
            'pages' => $pagination->getPageCount(),
        ];
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

        $this->attachContainers(array_merge($folders, $files));
        $itemCounts = $this->countChildren($folders);
        $this->collectLikeStates(array_merge($folders, $files));
        // One query for the topics of the page, not one per row.
        $topics = ItemTopicSerializer::forContents(array_map(
            static fn(FileSystemItem $item) => (int)$item->content->id,
            array_merge($folders, $files),
        ));

        // Where each row lies, relative to the open folder: part of this listing, not of the
        // item, so it is added here rather than by the serializers.
        $placed = fn(FileSystemItem $item, array $row) => $row + [
            'path' => $this->list->pathOf($row['parentFolderId'], (int)$item->content->contentcontainer_id),
        ];

        return array_merge(
            array_map(
                static fn(Folder $subFolder) => $placed($subFolder, FolderSerializer::folder(
                    $subFolder,
                    $itemCounts[$subFolder->id] ?? 0,
                    $topics[(int)$subFolder->content->id] ?? [],
                )),
                $folders,
            ),
            array_map(
                static fn(File $file) => $placed($file, FileSerializer::file($file, $topics[(int)$file->content->id] ?? [])),
                $files,
            ),
        );
    }

    /**
     * The like states of one page, in two grouped queries rather than two per row.
     *
     * They belong to the page as a whole: a row list shows a like link per item, and asking
     * per item is what makes a list of 50 rows cost 100 queries. Stashed on the instance
     * because they are per caller and therefore travel in their own section of the payload,
     * not inside the rows.
     *
     * @param FileSystemItem[] $items
     */
    private function collectLikeStates(array $items): void
    {
        $this->likeStates = [];

        if ($items === [] || !$this->likesEnabled()) {
            return;
        }

        $records = [];
        foreach ($items as $item) {
            $records[RecordMap::getId($item)] = $item;
        }

        $this->likeStates = LikeSerializer::statesForRecords($records);
    }

    /**
     * Hands every row the listed record of its container — the builder's containers are loaded
     * already —, so a row naming its container (a file's download URL) looks up nothing: over
     * the many spaces of the global files page, one lookup per row otherwise.
     *
     * @param FileSystemItem[] $items
     */
    private function attachContainers(array $items): void
    {
        $containers = [];
        foreach ($this->list->containers as $container) {
            $containers[(int)$container->contentcontainer_id] = $container;
        }

        foreach ($items as $item) {
            $container = $containers[(int)$item->content->contentcontainer_id] ?? null;
            if ($container !== null) {
                $item->content->setContainer($container);
            }
        }
    }

    /**
     * Whether the user may add to the level: write access in its container, as the file
     * browser page checks it. A list over several containers (the global page's results) has
     * no one container to write in. The browser takes it from every level it loads — on the
     * global page and in a space's own browser, whose page hands it only the first value
     * (`canWrite`, before any payload said). Sent with every page, not only the first: it is one
     * cached permission check, and every payload has the same keys.
     */
    private function canWrite(): bool
    {
        return count($this->list->containers) === 1
            && $this->list->container()->getPermissionManager($this->list->context->user)->can(WriteAccess::class);
    }

    private function likesEnabled(): bool
    {
        /** @var \humhub\modules\like\Module|null $module */
        $module = Yii::$app->getModule('like');

        return $module !== null && $module->isEnabled;
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
