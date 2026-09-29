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
use humhub\modules\content\components\ActiveQueryContent;
use humhub\modules\content\components\ContentContainerActiveRecord;
use humhub\modules\space\models\Space;
use InvalidArgumentException;
use LogicException;
use yii\db\ActiveQuery;

/**
 * What the filters of a {@see FolderList} narrow: the two queries of one level — its folders
 * and its files — of one container, or of several ({@see self::$containers}).
 *
 * Several containers are listed in results mode only: their builder has no level of its own,
 * so the caller scopes it with {@see self::scopeToSubtree()} (no open folder — the top level of
 * each), typically with {@see self::$prefixContainer}.
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
     * @var bool whether a path ({@see self::pathOf()}) starts with the item's container — for a
     *      list over several containers, where the folders alone do not say where an item lies
     */
    public bool $prefixContainer = false;

    /**
     * @var array<int, array{type: string, id: int, title: string}[]> in results mode: the path
     *      of each folder of the scope, relative to the open folder (see {@see self::pathOf()})
     */
    private array $paths = [];

    /**
     * @var array<int, array{type: string, id: int, contentContainerId: int, guid: string, title: string}>|null the path
     *      entry of each container, by its `contentcontainer_id` — built on first use
     */
    private ?array $containerEntries = null;

    /**
     * @param ContentContainerActiveRecord[] $containers the containers listed, at least one:
     *        the file browser lists one, the global files page all spaces of the user. They
     *        are trusted: the caller has checked that the user may browse each of them (the
     *        module enabled, the space accessible) — `readable()` only filters per content item
     * @throws InvalidArgumentException without a container
     */
    public function __construct(
        public readonly array $containers,
        public readonly ListContext $context,
    ) {
        if ($containers === []) {
            throw new InvalidArgumentException('A folder list needs at least one container.');
        }

        $this->folderQuery = $this->inContainers(Folder::find())->readable();

        $this->fileQuery = $this->inContainers(File::find()->joinWith('baseFile'))->readable();
    }

    /**
     * The one container listed.
     *
     * @throws LogicException when the list spans several containers
     */
    public function container(): ContentContainerActiveRecord
    {
        if (count($this->containers) !== 1) {
            throw new LogicException('A folder list over several containers has no single container.');
        }

        return $this->containers[array_key_first($this->containers)];
    }

    /**
     * Narrows a query to the content of the listed containers — what
     * `ActiveQueryContent::contentContainer()` does for one, with the same joins.
     */
    private function inContainers(ActiveQueryContent $query): ActiveQueryContent
    {
        return $query
            ->joinWith(['content', 'content.contentContainer', 'content.createdBy'])
            ->andWhere(['content.contentcontainer_id' => array_map(
                static fn(ContentContainerActiveRecord $container) => (int)$container->contentcontainer_id,
                array_values($this->containers),
            )]);
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
     * The descendants and their titles come from ONE query of the containers' readable folders
     * (id, parent, title), walked from the open folder down. A folder the caller may not read
     * is not in it, so its whole subtree is cut off — even the parts they could read, which
     * they would have no way to reach in the browser either.
     *
     * Over several containers the top level is the top level of each: every container's
     * `parent_folder_id IS NULL`. A folder id is unique across containers, and a folder's
     * parent lies in its own container, so the walk never crosses from one into another — an
     * unreadable folder cuts off its own subtree only.
     */
    public function scopeToSubtree(): void
    {
        $this->resultsMode = true;

        $rows = $this->inContainers(Folder::find())
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

        // A cursor rather than array_shift(), which reindexes the queue on every step.
        for ($next = 0; $next < count($queue); $next++) {
            [$parentId, $path] = $queue[$next];
            foreach ($children[$parentId] ?? [] as $row) {
                $id = (int)$row['id'];
                if (isset($this->paths[$id])) {
                    // A corrupt parent chain (a cycle) must not hang the request.
                    continue;
                }
                $this->paths[$id] = [...$path, ['type' => 'folder', 'id' => $id, 'title' => (string)$row['title']]];
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
     * With {@see self::$prefixContainer} the path starts with the item's container — a space:
     * `{type: 'space', id, contentContainerId, guid, title}`, no URL (the page links it by the
     * space id itself). Only spaces are listed over several containers; a profile gets
     * `type: 'user'` and its display name.
     *
     * @param int|null $contentContainerId the item's `content.contentcontainer_id` — used for
     *        the prefix only and ignored without it; null is the only container of a list over one
     * @return array{type: string, id: int, title: string, contentContainerId?: int, guid?: string}[]
     * @throws LogicException with the prefix, for a container the list does not span
     */
    public function pathOf(?int $parentId, ?int $contentContainerId = null): array
    {
        $path = $parentId === null ? [] : ($this->paths[$parentId] ?? []);

        if (!$this->prefixContainer) {
            return $path;
        }

        return [$this->containerEntry($contentContainerId ?? (int)$this->container()->contentcontainer_id), ...$path];
    }

    /**
     * The path entry of a listed container.
     *
     * @return array{type: string, id: int, contentContainerId: int, guid: string, title: string}
     * @throws LogicException for a container the list does not span
     */
    private function containerEntry(int $contentContainerId): array
    {
        if ($this->containerEntries === null) {
            // The listed records already: no query, once per builder.
            $this->containerEntries = [];
            foreach ($this->containers as $container) {
                $this->containerEntries[(int)$container->contentcontainer_id] = [
                    'type' => $container instanceof Space ? 'space' : 'user',
                    'id' => (int)$container->id,
                    'contentContainerId' => (int)$container->contentcontainer_id,
                    'guid' => (string)$container->guid,
                    'title' => (string)$container->getDisplayName(),
                ];
            }
        }

        return $this->containerEntries[$contentContainerId]
            ?? throw new LogicException('The container ' . $contentContainerId . ' is not listed.');
    }
}
