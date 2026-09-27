<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\cfiles\components;

use humhub\components\listing\ListBuilder;
use humhub\components\listing\ListContext;
use humhub\modules\cfiles\models\File;
use humhub\modules\cfiles\models\Folder;
use humhub\modules\content\components\ContentContainerActiveRecord;
use yii\db\ActiveQuery;

/**
 * What the filters of a {@see FolderList} narrow: the two queries of one level — its folders
 * and its files.
 *
 * Two ordered queries rather than a UNION, so both keep the `readable()` content scope that
 * decides what the caller may see at all; the listing pages across their seam, folders first
 * ({@see \humhub\modules\cfiles\services\FolderListingService}). A filter narrows both, each
 * with the columns of its own type:
 *
 * ```php
 * $builder->folderQuery()->andWhere(['cfiles_folder.title' => ...]);
 * $builder->fileQuery()->andWhere(['file.file_name' => ...]);
 * ```
 *
 * The scope — the level itself (`parent`, {@see FolderParentFilter}), or in results mode the
 * open folder with its subtree ({@see self::scopeToSubtree()}) — and the order are applied by
 * the list's `finalize()`, after the filters.
 *
 * @since 1.0
 */
class FolderListBuilder implements ListBuilder
{
    private ?Folder $folder = null;

    private ActiveQuery $folderQuery;

    private ActiveQuery $fileQuery;

    /**
     * @var string the sort the list was built with: a key of {@see FolderList::sorts()} —
     *      `default` when neither the request nor the user's last choice named one
     */
    public string $sort = FolderList::SORT_DEFAULT;

    /**
     * @var bool whether the list shows the hits in the open folder and all its subfolders
     *      rather than the level ({@see FolderList::$resultFilters})
     */
    public bool $resultsMode = false;

    /**
     * @var array<int, array{id: int, title: string}[]> in results mode: the path of each
     *      folder of the scope, relative to the open folder (see {@see self::pathOf()})
     */
    private array $paths = [];

    public function __construct(
        public readonly ContentContainerActiveRecord $container,
        public readonly ListContext $context,
    ) {
        $this->folderQuery = Folder::find()
            ->contentContainer($container)
            ->readable();

        $this->fileQuery = File::find()
            ->joinWith('baseFile')
            ->contentContainer($container)
            ->readable();
    }

    /**
     * The folder listed, `null` for the container's top level.
     */
    public function folder(): ?Folder
    {
        return $this->folder;
    }

    public function setFolder(?Folder $folder): void
    {
        $this->folder = $folder;
    }

    public function folderQuery(): ActiveQuery
    {
        return $this->folderQuery;
    }

    public function fileQuery(): ActiveQuery
    {
        return $this->fileQuery;
    }

    /**
     * Narrows both queries to the open folder and all its readable subfolders — at the top
     * level the whole container: `parent_folder_id IN (<folder>, <descendants>)`, at the top
     * level also `IS NULL`.
     *
     * The descendants and their titles come from ONE query of the container's readable folders
     * (id, parent, title), walked from the open folder down. A folder the caller may not read
     * is not in it, so its whole subtree is cut off — even the parts they could read, which
     * they would have no way to reach in the browser either.
     */
    public function scopeToSubtree(): void
    {
        $this->resultsMode = true;

        $rows = Folder::find()
            ->contentContainer($this->container)
            ->readable()
            ->select(['cfiles_folder.id', 'cfiles_folder.parent_folder_id', 'cfiles_folder.title'])
            // A command, not `all()`: no records, and none of the scope's eager relations.
            ->createCommand()
            ->queryAll();

        $children = [];
        foreach ($rows as $row) {
            $children[(int)$row['parent_folder_id']][] = $row;
        }

        $rootId = $this->folder?->id;
        $this->paths = $rootId === null ? [] : [(int)$rootId => []];
        $queue = [[(int)$rootId, []]];

        while ($queue !== []) {
            [$parentId, $path] = array_shift($queue);
            foreach ($children[$parentId] ?? [] as $row) {
                $id = (int)$row['id'];
                if (isset($this->paths[$id])) {
                    // A corrupt parent chain (a cycle) must not hang the request.
                    continue;
                }
                $this->paths[$id] = [...$path, ['id' => $id, 'title' => (string)$row['title']]];
                $queue[] = [$id, $this->paths[$id]];
            }
        }

        $ids = array_keys($this->paths);

        foreach ([[$this->folderQuery, 'cfiles_folder.parent_folder_id'], [$this->fileQuery, 'cfiles_file.parent_folder_id']] as [$query, $column]) {
            $query->andWhere($rootId === null
                ? ['or', [$column => null], [$column => $ids]]
                : [$column => $ids]);
        }
    }

    /**
     * Where an item with the given parent lies, relative to the open folder: the folders from
     * below it down to the parent — empty when the item is directly in the open folder (and
     * outside results mode, where every item is).
     *
     * @return array{id: int, title: string}[]
     */
    public function pathOf(?int $parentId): array
    {
        return $parentId === null ? [] : ($this->paths[$parentId] ?? []);
    }
}
