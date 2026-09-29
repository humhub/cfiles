<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2026 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\cfiles\components;

use humhub\components\listing\FilterableList;
use humhub\components\listing\ListBuilder;
use humhub\components\listing\ListContext;
use humhub\modules\cfiles\Module;
use humhub\modules\cfiles\services\BrowserPreferences;
use humhub\modules\content\components\ContentContainerModuleManager;
use humhub\modules\content\models\ContentContainerModuleState;
use humhub\modules\space\components\listing\SpaceFilter;
use humhub\modules\space\models\Membership;
use humhub\modules\space\models\Space;
use Yii;
use yii\db\ActiveQuery;

/**
 * The list of the global files page `/files`, across the spaces of the user.
 * `GET /api/v2/cfiles/items` is built with it, and the page's `FilterBar` gets its
 * {@see self::definitions()}.
 *
 * ```php
 * $builder = (new GlobalFolderList())->build(['q' => 'logo'], ListContext::forCurrentUser());
 * ```
 *
 * **The spaces** come from {@see self::scopeQuery()}, the trust boundary of the page. The
 * results list exactly these spaces, narrowed by `spaceId`. Then `readable()` decides per item.
 *
 * **Tile mode** is the list without a result filter ({@see self::$resultFilters}): one tile per
 * space. Tiles are ordered by name, `nameDesc` reverses it. The other sorts have nothing to
 * order tiles by, so they fall back to the name.
 *
 * **Results mode** starts with the first result filter: the hits across all the spaces, in the
 * whole tree of each. Each hit's path starts with its space
 * ({@see FolderListBuilder::$prefixContainer}). The order is the file browser's
 * ({@see FolderList::orderItems()}).
 *
 * `spaceId` alone is no result filter. It narrows the tiles or the results; a single tile is the
 * way into that space.
 *
 * Parameters:
 *
 * - `q`, `userId`, `type`, `modified`: the file browser's ({@see FolderList::contentFilters()}).
 * - `spaceId`: one or several spaces. A space the caller may not see is refused by
 *   {@see SpaceFilter}. A visible one outside the scope lists nothing.
 * - `topicId`: {@see FolderList::topicFilter()}, any topic the caller may see: the global ones,
 *   those of every space they may see and of their own profile.
 * - `sort`: {@see FolderList::sortLabels()}. Without one, the stored {@see self::$storedSort},
 *   else `default`.
 *
 * Paging stays with the caller. The builder is a {@see GlobalListBuilder}; its docblock says how
 * the filters reach the items and what handlers of this list's events receive.
 *
 * @since 1.0
 */
class GlobalFolderList extends FilterableList
{
    /**
     * The filters whose value switches the list into results mode: the file browser's (the
     * topic among them).
     */
    public const RESULT_FILTERS = FolderList::RESULT_FILTERS;

    /**
     * @var string[] the keys of the filters that switch the list into results mode —
     *      {@see self::RESULT_FILTERS}, plus what a module added on {@see self::EVENT_INIT}
     */
    public array $resultFilters = self::RESULT_FILTERS;

    /**
     * @var string|null the sort applied when the request names none — the user's last choice
     *      ({@see BrowserPreferences::sort()}, the same as the file browser's)
     */
    public ?string $storedSort = null;

    /**
     * The spaces of the global files page for the context's user, as a query. This is the trust
     * boundary of the page: the {@see FolderListBuilder} takes its containers as trusted, and
     * they come from here only.
     *
     * A space is in it when:
     *
     * - the user is a member of it (not an applicant, not invited);
     * - it is neither archived nor disabled;
     * - the module is enabled in it.
     *
     * "Enabled" follows the space's module manager ({@see ContentContainerModuleManager::getEnabled()}),
     * in SQL:
     *
     * - A state stored for the space (`contentcontainer_module`) wins. Enabled and always
     *   enabled count.
     * - Without a stored state, the module's default for spaces decides
     *   (`moduleManager.defaultState.Space`, set by the admin). Enabled and always enabled by
     *   default count.
     * - A module not available for spaces (`STATE_NOT_AVAILABLE`) is enabled nowhere.
     *
     * {@see ContentContainerModuleManager::getContentContainerQueryByModule()} is not used: with
     * a module enabled by default it takes every space, also those that switched it off.
     */
    public static function scopeQuery(ListContext $context): ActiveQuery
    {
        /** @var Module $module */
        $module = Yii::$app->getModule('cfiles');
        $query = Space::find()->andWhere(['space.status' => Space::STATUS_ENABLED]);

        $default = ContentContainerModuleManager::getDefaultState(Space::class, $module->id);
        if ($context->user === null
            || !$module->hasContentContainerType(Space::class)
            || $default === ContentContainerModuleState::STATE_NOT_AVAILABLE) {
            return $query->andWhere('0=1');
        }

        $enabled = [ContentContainerModuleState::STATE_ENABLED, ContentContainerModuleState::STATE_FORCE_ENABLED];

        $query
            ->innerJoin(
                ['cfiles_membership' => Membership::tableName()],
                'cfiles_membership.space_id = space.id AND cfiles_membership.user_id = :cfilesUserId AND cfiles_membership.status = :cfilesMember',
                [':cfilesUserId' => $context->user->id, ':cfilesMember' => Membership::STATUS_MEMBER],
            )
            ->leftJoin(
                ['cfiles_state' => ContentContainerModuleState::tableName()],
                'cfiles_state.contentcontainer_id = space.contentcontainer_id AND cfiles_state.module_id = :cfilesModuleId',
                [':cfilesModuleId' => $module->id],
            );

        return in_array($default, $enabled, true)
            ? $query->andWhere(['or', ['cfiles_state.module_state' => $enabled], ['cfiles_state.module_state' => null]])
            : $query->andWhere(['cfiles_state.module_state' => $enabled]);
    }

    /**
     * @inheritdoc
     */
    protected function filters(): array
    {
        $content = FolderList::contentFilters();

        // Positions in the bar: search 100, space 110, author 120, topic 125, file type 130,
        // modified 140 — the sort is at 200.
        return [
            new GlobalItemsFilter($content['q']),
            new SpaceFilter(
                'spaceId',
                apply: static function (GlobalListBuilder $list, array $spaceIds) {
                    $list->narrowToSpaces($spaceIds);
                },
                definition: [
                    'label' => Yii::t('CfilesModule.base', 'Space'),
                    'placeholder' => Yii::t('CfilesModule.base', 'Space'),
                    'props' => ['scope' => 'member'],
                    'sortOrder' => 110,
                ],
            ),
            new GlobalItemsFilter($content['userId']),
            new GlobalItemsFilter(FolderList::topicFilter()),
            new GlobalItemsFilter($content['type']),
            new GlobalItemsFilter($content['modified']),
        ];
    }

    /**
     * @inheritdoc
     */
    protected function sorts(): array
    {
        return FolderList::sortLabels();
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
     * @return GlobalListBuilder
     */
    public function build(array $params, ListContext $context): GlobalListBuilder
    {
        /** @var GlobalListBuilder $builder */
        $builder = parent::build($params, $context);

        return $builder;
    }

    /**
     * @inheritdoc
     */
    protected function createBuilder(ListContext $context): ListBuilder
    {
        return new GlobalListBuilder(self::scopeQuery($context), $context);
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
     * The mode, then the order. In results mode the order of the file browser is queued for
     * the items ({@see GlobalListBuilder::onItems()}); they are built later, on first use. Else
     * the tiles are ordered by name.
     *
     * @inheritdoc
     * @param GlobalListBuilder $builder
     */
    protected function finalize(ListBuilder $builder, array $values, ListContext $context): void
    {
        $sort = $this->value($values, self::SORT_PARAM)
            ?? FolderList::storedOrDefault($this->storedSort, $this->getSorts());
        $builder->sort = $sort;

        if ($this->isResultsMode($values)) {
            $builder->resultsMode = true;
            // Queued, as the filters: the items are built after EVENT_BUILD.
            $custom = $this->applySortCallback($builder, $sort, $context);
            $builder->onItems(static function (FolderListBuilder $items) use ($sort, $custom) {
                $items->sort = $sort;
                FolderList::orderItems($items, $custom ? null : $sort);
            });

            return;
        }

        if (!$this->applySortCallback($builder, $sort, $context)) {
            $builder->spaceQuery()->orderBy(['space.name' => $sort === 'nameDesc' ? SORT_DESC : SORT_ASC]);
        }
        $builder->spaceQuery()->addOrderBy(['space.id' => SORT_ASC]);
    }
}
