<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\cfiles\components;

use humhub\components\listing\FilterableList;
use humhub\components\listing\ListBuilder;
use humhub\components\listing\ListContext;
use humhub\components\listing\filters\EnumFilter;
use humhub\components\listing\filters\SearchFilter;
use humhub\modules\cfiles\Module;
use humhub\modules\cfiles\services\BrowserPreferences;
use humhub\modules\content\models\ContentTagRelation;
use humhub\modules\topic\components\listing\TopicFilter;
use humhub\modules\user\components\listing\UserFilter;
use Yii;
use yii\base\InvalidConfigException;

/**
 * One level of a container's file tree, as the file browser lists it:
 * `GET /api/v2/cfiles/<containerId>/items` is built with it, and the page's `FilterBar` gets
 * its {@see self::definitions()}.
 *
 * ```php
 * $builder = (new FolderList())->build(['parent' => 12, 'sort' => 'newest'], ListContext::forCurrentUser(null, $container));
 * ```
 *
 * The context needs the container ({@see ListContext::$container}); visibility (`readable()`)
 * is the base of both queries ({@see FolderListBuilder}), no filter can bring back an item the
 * caller may not see.
 *
 * Parameters: `parent` (a folder id of the container, {@see FolderParentFilter}; absent = the
 * top level), the filters of the page's bar — `q` (folder titles, file names and the
 * descriptions of both), `userId` (the author: who created the folder or file — not for
 * guests, see {@see UserFilter}), `topicId` (one or several topics of the container or global
 * ones, {@see self::topicFilter()} — not for guests either), `type` (files of one of
 * {@see self::TYPE_GROUPS}, no folders), `modified` (the content's last change: `7d`, `30d`,
 * `12m` or `older`) — and `sort` (one of {@see self::sorts()}); paging stays with the caller.
 *
 * **Results mode**: once one of {@see self::RESULT_FILTERS} is set, the list no longer shows
 * the level but the hits in the open folder and all its readable subfolders (the whole
 * container at the top level) — see {@see FolderListBuilder::scopeToSubtree()}; the builder
 * then knows where each hit lies ({@see FolderListBuilder::pathOf()}). A module's filter that
 * should search the subtree too is added to {@see self::$resultFilters}.
 *
 * Without a `sort` the list is ordered by {@see self::$storedSort} (the user's last choice,
 * which the browser's endpoints read and write through {@see BrowserPreferences} — building a
 * list has no side effects), else by `default`: the module's configured order
 * ({@see Module::$defaultSort}/{@see Module::$defaultOrder}). The builder reports which applied
 * ({@see FolderListBuilder::$sort}). Every order ends on the id, so pages neither repeat nor
 * skip rows with equal values.
 *
 * A module adds a filter on {@see self::EVENT_INIT} and restricts the list on
 * {@see self::EVENT_BUILD}; both narrow the two queries of the level, each with the columns of
 * its own type ({@see FolderListBuilder}):
 *
 * ```php
 * ['class' => FolderList::class, 'event' => FolderList::EVENT_INIT, 'callback' => [Events::class, 'onFolderListInit']],
 * ['class' => FolderList::class, 'event' => FolderList::EVENT_BUILD, 'callback' => [Events::class, 'onFolderListBuild']],
 *
 * public static function onFolderListInit(ListEvent $event)
 * {
 *     $event->list->addFilter(new SearchFilter('keyword', apply: static function (FolderListBuilder $list, string $keyword) {
 *         $list->folderQuery()->andWhere(['like', 'cfiles_folder.description', $keyword]);
 *         $list->fileQuery()->andWhere(['like', 'cfiles_file.description', $keyword]);
 *     }, definition: ['label' => Yii::t('MyModule.base', 'Keyword'), 'sortOrder' => 150]));
 *     // searches the subtree, as the list's own filters do
 *     $event->list->resultFilters[] = 'keyword';
 * }
 *
 * // a plain restriction, after all filters: e.g. while searching, only files
 * public static function onFolderListBuild(ListEvent $event)
 * {
 *     if ($event->value('q') !== null) {
 *         $event->builder->folderQuery()->andWhere('0=1');
 *     }
 * }
 * ```
 *
 * @since 1.0
 */
class FolderList extends FilterableList
{
    public const SORT_DEFAULT = 'default';

    /**
     * An item's last change, as its row shows it: the content's, falling back to its creation.
     */
    public const LAST_CHANGE = 'COALESCE(content.updated_at, content.created_at)';

    /**
     * @var string|null the sort applied when the request names none — the user's last choice
     *      ({@see BrowserPreferences::sort()}); one the list does not know is ignored
     */
    public ?string $storedSort = null;

    /**
     * The filters whose value switches the list into results mode (see the class docblock).
     */
    public const RESULT_FILTERS = ['q', 'userId', 'topicId', 'type', 'modified'];

    /**
     * @var string[] the keys of the filters that switch the list into results mode —
     *      {@see self::RESULT_FILTERS}, plus what a module added on {@see self::EVENT_INIT}
     */
    public array $resultFilters = self::RESULT_FILTERS;

    /**
     * The groups of the `type` filter, by the extension of the file name (case-insensitive) —
     * the same extensions the type icons use, not the mime type a browser guessed on upload.
     */
    public const TYPE_GROUPS = [
        'image' => ['jpg', 'jpeg', 'png', 'bmp', 'svg', 'gif', 'webp', 'avif', 'tif', 'tiff', 'heic', 'ico'],
        'document' => ['pdf', 'doc', 'docx', 'docm', 'odt', 'rtf', 'pages', 'txt', 'log', 'md', 'markdown'],
        'spreadsheet' => ['xls', 'xlsx', 'xlsb', 'xlsm', 'ods', 'numbers', 'csv', 'tsv'],
        'presentation' => ['ppt', 'pptx', 'pps', 'ppsx', 'odp', 'key'],
        'media' => ['mp3', 'wav', 'ogg', 'oga', 'm4a', 'aac', 'flac', 'wma', 'aiff', 'mp4', 'mov', 'avi', 'webm', 'mkv', 'm4v', 'wmv', 'mpeg'],
    ];

    /**
     * The `modified` values: since when (a `strtotime()` offset), or before 12 months ago.
     */
    public const MODIFIED_SINCE = [
        '7d' => '-7 days',
        '30d' => '-30 days',
        '12m' => '-12 months',
    ];

    public const MODIFIED_OLDER = 'older';

    /**
     * The order of each sort key: a column of {@see self::SORT_COLUMNS} and a direction.
     */
    public const SORT_ORDERS = [
        'name' => ['name', SORT_ASC],
        'nameDesc' => ['name', SORT_DESC],
        'newest' => ['updatedAt', SORT_DESC],
        'oldest' => ['updatedAt', SORT_ASC],
        'largest' => ['size', SORT_DESC],
        'smallest' => ['size', SORT_ASC],
    ];

    /**
     * The columns a level can be ordered by, mapped to the order expression of each row type.
     * A null means the type cannot be sorted that way and falls back to its name — folders
     * have no size, so sorting a mixed listing by size still has to put them somewhere.
     * `downloadCount` has no key; the module's default order may still name it.
     */
    public const SORT_COLUMNS = [
        'name' => ['folder' => 'cfiles_folder.title', 'file' => 'file.file_name'],
        'size' => ['folder' => null, 'file' => 'cast(file.size as unsigned)'],
        // The last change the rows show (see the serializers) and `modified` filters by.
        'updatedAt' => ['folder' => self::LAST_CHANGE, 'file' => self::LAST_CHANGE],
        'downloadCount' => ['folder' => null, 'file' => 'cfiles_file.download_count'],
    ];

    /**
     * The sort key standing for a column and a direction, `null` if none does — how an order
     * stored before the sort keys is read.
     */
    public static function keyOfOrder(string $column, int $direction): ?string
    {
        $key = array_search([$column, $direction === SORT_DESC ? SORT_DESC : SORT_ASC], self::SORT_ORDERS, true);

        return $key === false ? null : $key;
    }

    /**
     * @inheritdoc
     */
    protected function filters(): array
    {
        $content = self::contentFilters();

        // In the order of the bar: search, author, topic, file type, modified — the sort is at 200.
        return [
            new FolderParentFilter(),
            $content['q'],
            $content['userId'],
            // The topics of the context's container (and the global ones).
            self::topicFilter(),
            $content['type'],
            $content['modified'],
        ];
    }

    /**
     * The filters of the bar that narrow the items themselves — search, author, file type and
     * modified, by key, in the order of the bar —, shared with the global files page
     * ({@see GlobalFolderList}): each narrows the two queries of a {@see FolderListBuilder},
     * whatever containers it spans.
     *
     * Positions in the bar: search 100, author 120, file type 130, modified 140 (the global
     * page puts its space at 110, the topic ({@see self::topicFilter()}) is at 125, the sort is at 200).
     *
     * @return array<string, \humhub\components\listing\ListFilter>
     */
    public static function contentFilters(): array
    {
        return [
            'q' => new SearchFilter(
                'q',
                apply: static function (FolderListBuilder $list, string $keywords) {
                    $list->folderQuery()->andWhere(['or',
                        ['like', 'cfiles_folder.title', $keywords],
                        ['like', 'cfiles_folder.description', $keywords],
                    ]);
                    $list->fileQuery()->andWhere(['or',
                        ['like', 'file.file_name', $keywords],
                        ['like', 'cfiles_file.description', $keywords],
                    ]);
                },
                definition: [
                    'label' => Yii::t('CfilesModule.base', 'Search'),
                    'placeholder' => Yii::t('CfilesModule.base', 'Search'),
                    'sortOrder' => 100,
                ],
                maxLength: 255,
            ),
            'userId' => new UserFilter(
                'userId',
                // A callback, not `column`: the filter applies a column to a single query only.
                apply: static function (FolderListBuilder $list, int $userId) {
                    $list->folderQuery()->andWhere(['content.created_by' => $userId]);
                    $list->fileQuery()->andWhere(['content.created_by' => $userId]);
                },
                definition: [
                    'label' => Yii::t('CfilesModule.base', 'Author'),
                    'sortOrder' => 120,
                ],
            ),
            'type' => new EnumFilter(
                'type',
                values: [
                    'image' => Yii::t('CfilesModule.base', 'Images'),
                    'document' => Yii::t('CfilesModule.base', 'Documents'),
                    'spreadsheet' => Yii::t('CfilesModule.base', 'Spreadsheets'),
                    'presentation' => Yii::t('CfilesModule.base', 'Presentations'),
                    'media' => Yii::t('CfilesModule.base', 'Audio & Video'),
                ],
                apply: static function (FolderListBuilder $list, string $group) {
                    // A type is a kind of file: no folder has one.
                    $list->folderQuery()->andWhere('0=1');
                    $condition = ['or'];
                    foreach (self::TYPE_GROUPS[$group] as $extension) {
                        $condition[] = ['like', 'LOWER(file.file_name)', '%.' . $extension, false];
                    }
                    $list->fileQuery()->andWhere($condition);
                },
                definition: [
                    'label' => Yii::t('CfilesModule.base', 'File Type'),
                    'placeholder' => Yii::t('CfilesModule.base', 'File Type'),
                    'sortOrder' => 130,
                ],
            ),
            'modified' => new EnumFilter(
                'modified',
                values: [
                    '7d' => Yii::t('CfilesModule.base', 'Last 7 days'),
                    '30d' => Yii::t('CfilesModule.base', 'Last 30 days'),
                    '12m' => Yii::t('CfilesModule.base', 'Last 12 months'),
                    self::MODIFIED_OLDER => Yii::t('CfilesModule.base', 'Older'),
                ],
                apply: static function (FolderListBuilder $list, string $modified) {
                    $column = self::LAST_CHANGE;
                    $bound = date('Y-m-d H:i:s', strtotime(self::MODIFIED_SINCE[$modified] ?? self::MODIFIED_SINCE['12m']));
                    $condition = [$modified === self::MODIFIED_OLDER ? '<' : '>=', $column, $bound];
                    $list->folderQuery()->andWhere($condition);
                    $list->fileQuery()->andWhere($condition);
                },
                definition: [
                    'label' => Yii::t('CfilesModule.base', 'Modified'),
                    'placeholder' => Yii::t('CfilesModule.base', 'Modified'),
                    'sortOrder' => 140,
                ],
            ),
        ];
    }

    /**
     * The "Topic" filter (`topicId`) over the items of a {@see FolderListBuilder}: folders and
     * files with any of the chosen topics — the {@see TopicFilter}'s own condition, applied to
     * both queries (its default applies to the single query of a `QueryListBuilder` only).
     *
     * It offers and accepts the topics of the context's container and the global ones (the
     * core filter reads the container from the context) — without a container in the context
     * (the global files page) every topic the caller may see.
     */
    public static function topicFilter(): TopicFilter
    {
        return new TopicFilter(
            'topicId',
            apply: static function (FolderListBuilder $list, array $topicIds) {
                foreach ([$list->folderQuery(), $list->fileQuery()] as $query) {
                    $query->andWhere(['content.id' => ContentTagRelation::find()
                        ->select('content_tag_relation.content_id')
                        ->where(['content_tag_relation.tag_id' => $topicIds])]);
                }
            },
            definition: [
                'label' => Yii::t('CfilesModule.base', 'Topic'),
                'sortOrder' => 125,
            ],
        );
    }

    /**
     * @inheritdoc
     */
    protected function sorts(): array
    {
        return self::sortLabels();
    }

    /**
     * The sort keys and their labels — also those of the global files page
     * ({@see GlobalFolderList}), whose results are ordered the same way ({@see self::orderItems()}).
     *
     * @return array<string, string|null>
     */
    public static function sortLabels(): array
    {
        // No option for the default order: the select's label is it, as "all" is for a select.
        return [
            self::SORT_DEFAULT => null,
            'name' => Yii::t('CfilesModule.base', 'Name (A–Z)'),
            'nameDesc' => Yii::t('CfilesModule.base', 'Name (Z–A)'),
            'newest' => Yii::t('CfilesModule.base', 'Newest first'),
            'oldest' => Yii::t('CfilesModule.base', 'Oldest first'),
            'largest' => Yii::t('CfilesModule.base', 'Largest first'),
            'smallest' => Yii::t('CfilesModule.base', 'Smallest first'),
        ];
    }

    /**
     * @inheritdoc
     */
    protected function sortLabel(): string
    {
        return Yii::t('CfilesModule.base', 'Sort by');
    }

    /**
     * @inheritdoc
     * @return FolderListBuilder
     */
    public function build(array $params, ListContext $context): FolderListBuilder
    {
        /** @var FolderListBuilder $builder */
        $builder = parent::build($params, $context);

        return $builder;
    }

    /**
     * @inheritdoc
     * @throws InvalidConfigException without a container in the context
     */
    protected function createBuilder(ListContext $context): ListBuilder
    {
        if ($context->container === null) {
            throw new InvalidConfigException('A folder list needs the container in its context.');
        }

        return new FolderListBuilder([$context->container], $context);
    }

    /**
     * Whether the values switch the list into results mode ({@see self::$resultFilters}).
     *
     * @param array<string, \humhub\components\listing\FilterValue> $values
     */
    public function isResultsMode(array $values): bool
    {
        foreach ($this->resultFilters as $key) {
            if ($this->value($values, $key) !== null) {
                return true;
            }
        }

        return false;
    }

    /**
     * The scope — the level (`parent`, top level when absent), or in results mode the open
     * folder with its subtree —, then the order; see the class docblock for which sort applies.
     *
     * @inheritdoc
     * @param FolderListBuilder $builder
     */
    protected function finalize(ListBuilder $builder, array $values, ListContext $context): void
    {
        if ($this->isResultsMode($values)) {
            $builder->scopeToSubtree();
        } else {
            $parentId = $builder->folder()?->id;
            $builder->folderQuery()->andWhere(['cfiles_folder.parent_folder_id' => $parentId]);
            $builder->fileQuery()->andWhere(['cfiles_file.parent_folder_id' => $parentId]);
        }

        $sort = $this->value($values, self::SORT_PARAM)
            ?? self::storedOrDefault($this->storedSort, $this->getSorts());

        $builder->sort = $sort;

        // A sort with its own callback - one a module added, also for a key of the list's own.
        self::orderItems($builder, $this->applySortCallback($builder, $sort, $context) ? null : $sort);
    }

    /**
     * The sort applied when the request names none: the stored one if the list knows it, else
     * `default`.
     *
     * @param array<string, string|null> $sorts the list's sorts
     */
    public static function storedOrDefault(?string $stored, array $sorts): string
    {
        return $stored !== null && array_key_exists($stored, $sorts) ? $stored : self::SORT_DEFAULT;
    }

    /**
     * Orders both queries by the sort key — a key of {@see self::SORT_ORDERS}, any other the
     * module's configured order —, or keeps the order a sort callback applied (`null`); then
     * ends on the id, so rows with equal values keep one order and pages neither repeat nor
     * skip them.
     */
    public static function orderItems(FolderListBuilder $builder, ?string $sort): void
    {
        if ($sort !== null) {
            [$column, $direction] = self::SORT_ORDERS[$sort] ?? self::defaultOrder();
            self::applyOrder($builder, $column, $direction);
        }

        $builder->folderQuery()->addOrderBy(['cfiles_folder.id' => SORT_ASC]);
        $builder->fileQuery()->addOrderBy(['cfiles_file.id' => SORT_ASC]);
    }

    /**
     * The module's configured order, a column and a direction — name, ascending, where it
     * names a column the list does not know.
     *
     * @return array{0: string, 1: int}
     */
    private static function defaultOrder(): array
    {
        /** @var Module $module */
        $module = Yii::$app->getModule('cfiles');

        return isset(self::SORT_COLUMNS[$module->defaultSort])
            ? [$module->defaultSort, (int)$module->defaultOrder === SORT_DESC ? SORT_DESC : SORT_ASC]
            : self::SORT_ORDERS['name'];
    }

    /**
     * Orders both queries by the column; a type that cannot be sorted by it keeps its name
     * order.
     */
    private static function applyOrder(FolderListBuilder $builder, string $column, int $direction): void
    {
        $folderColumn = self::SORT_COLUMNS[$column]['folder'] ?? null;
        $fileColumn = self::SORT_COLUMNS[$column]['file'] ?? null;

        $builder->folderQuery()->orderBy(
            [$folderColumn ?? 'cfiles_folder.title' => $folderColumn === null ? SORT_ASC : $direction],
        );
        $builder->fileQuery()->orderBy(
            [$fileColumn ?? 'file.file_name' => $fileColumn === null ? SORT_ASC : $direction],
        );
    }
}
