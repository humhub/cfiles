<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2026 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\cfiles\controllers;

use humhub\components\access\ControllerAccess;
use humhub\components\Controller;
use humhub\components\listing\ListContext;
use humhub\components\listing\ListValidationException;
use humhub\modules\cfiles\components\FolderList;
use humhub\modules\cfiles\components\FolderParentFilter;
use humhub\modules\cfiles\components\GlobalFolderList;
use humhub\modules\cfiles\models\Folder;
use humhub\modules\cfiles\Module;
use humhub\modules\cfiles\services\BrowserPreferences;
use humhub\modules\cfiles\services\FirstListing;
use humhub\modules\cfiles\services\FolderListingService;
use humhub\modules\cfiles\services\GlobalListingService;
use humhub\modules\space\models\Space;
use humhub\modules\space\serializers\SpaceSerializer;
use humhub\modules\topic\components\TopicList;
use Yii;
use yii\helpers\Url;
use yii\web\NotFoundHttpException;

/**
 * The global files page `/files`: the files of all spaces the user is a member of, in one
 * browser. It exists only while the admin enabled it ({@see Module::getShowGlobalMenuItem()}),
 * and only for logged-in users.
 *
 * @property Module $module
 */
class GlobalController extends Controller
{
    /**
     * The Topic filter's parameter ({@see FolderList::topicFilter()}).
     */
    private const TOPIC_PARAM = 'topicId';

    /**
     * @inheritdoc
     */
    public function init()
    {
        $this->setActionTitles([
            'index' => Yii::t('CfilesModule.base', 'Files'),
        ]);

        parent::init();
    }

    /**
     * @inheritdoc
     */
    protected function getAccessRules()
    {
        return [
            [ControllerAccess::RULE_LOGGED_IN_ONLY],
        ];
    }

    /**
     * @inheritdoc
     */
    public function beforeAction($action)
    {
        // Switched off, the page does not exist — for guests as well.
        if (!$this->module->getShowGlobalMenuItem()) {
            throw new NotFoundHttpException();
        }

        return parent::beforeAction($action);
    }

    /**
     * The URL (`?space=<space id>&fid=<folder id>` and the filters, see the island) is the
     * level the page opens on: without `space` the top level — a tile per space, or the hits
     * across them —, with it that space's file browser.
     */
    public function actionIndex()
    {
        // A level the page cannot open is not in the URL, or the island would ask for it again.
        $canonical = $this->canonicalQuery(Yii::$app->request->getQueryParams());
        if ($canonical !== null) {
            return $this->redirect(['/cfiles/global/index'] + $canonical);
        }

        return $this->render('index', ['props' => $this->pageProps()]);
    }

    /**
     * The query of the URL the page should have been opened with, or null when it was: a
     * `space` outside the page's spaces is dropped with its `fid`, an `fid` its space does not
     * hold (or without a space, where it names nothing) on its own. Every other parameter stays.
     *
     * @return array|null
     */
    private function canonicalQuery(array $query): ?array
    {
        // The route itself, without pretty URLs.
        unset($query['r']);
        $space = isset($query['space']) ? $this->resolveSpace($query['space'], ListContext::forCurrentUser()) : null;

        if (isset($query['space']) && $space === null) {
            unset($query['space'], $query['fid']);
            return $query;
        }

        if (!isset($query['fid'])) {
            return null;
        }

        if ($space === null) {
            unset($query['fid']);
            return $query;
        }

        $fid = $query['fid'];
        if (in_array($fid, ['', '0'], true) || (is_string($fid) && ctype_digit($fid) && $this->resolveFolder($space, (int)$fid) !== null)) {
            return null;
        }

        unset($query['fid']);
        return $query;
    }

    /**
     * The island's props: the first page of the level the URL names, the filter definitions
     * of both kinds of level and the filter values the page was built with.
     *
     * A `space` outside the page's spaces ({@see GlobalFolderList::scopeQuery()}: not a member,
     * the module off, archived, disabled, unknown) is the top level; an `fid` the space does not
     * hold (or the user may not read) its top level — the page itself redirects such a URL
     * ({@see self::canonicalQuery()}). A filter value a list refuses is dropped
     * ({@see FirstListing}).
     *
     * `containerFilters` are a space's definitions for no space in particular: the Topic's
     * container is set by the island for the space it opens.
     *
     * `initialFilters` are the global page's — inside a space with the values the space's list
     * was built with; the global page's own (the space, the topic) are kept from the URL, for
     * the way back to the top level.
     */
    public function pageProps(): array
    {
        $query = Yii::$app->request->getQueryParams();
        $context = ListContext::forCurrentUser();
        $storedSort = (new BrowserPreferences($context->user))->sort();

        $globalList = new GlobalFolderList(['storedSort' => $storedSort]);
        ['builder' => $globalBuilder, 'initialFilters' => $initialFilters] = FirstListing::build($globalList, $context, $query);

        $space = $this->resolveSpace($query['space'] ?? null, $context);
        $spaceContext = ListContext::forCurrentUser(null, $space);
        $containerList = new FolderList(['storedSort' => $storedSort]);

        if ($space === null) {
            $listing = (new GlobalListingService($globalBuilder))->payload();
        } else {
            $folder = $this->resolveFolder($space, (int)($query['fid'] ?? 0));
            ['builder' => $builder, 'initialFilters' => $spaceFilters] = FirstListing::build(
                $containerList,
                $spaceContext,
                $this->withTopicsOf($space, $query),
                ['parent' => $folder?->id],
                [FolderParentFilter::KEY],
            );
            $listing = (new FolderListingService($builder))->payload();
            // The topics as the global page has them: those of other spaces too, for the way
            // back — the island narrows them to the space's as the server did.
            unset($spaceFilters[self::TOPIC_PARAM]);
            $initialFilters = array_merge($initialFilters, $spaceFilters);
        }

        return [
            'listing' => $listing,
            'global' => true,
            'globalUrl' => Url::to(['/cfiles/global/index']),
            'space' => SpaceSerializer::short($space),
            'contentContainerId' => $space === null ? null : (int)$space->contentcontainer_id,
            'filters' => $globalList->definitions($context),
            // Of no one space: the island opens any space of the page, and scopes the container's
            // controls (the Topic's `props.containerId`) to the one open itself.
            'containerFilters' => $containerList->definitions($context),
            'initialFilters' => $initialFilters,
            // The module's settings, where the page is switched on, for the admins.
            'settingsUrl' => Yii::$app->user->isAdmin() ? $this->module->getConfigUrl() : null,
        ];
    }

    /**
     * The URL's topics as a space's list takes them: those of the space and the global ones —
     * the rule of its Topic filter ({@see TopicList} with the space's container), so a topic of
     * another space chosen at the top level leaves the space's other topics applied instead of
     * the list refusing them all. None of them the space's: no topic. A value that is no list
     * of ids stays, for the list to refuse.
     */
    private function withTopicsOf(Space $space, array $query): array
    {
        $raw = $query[self::TOPIC_PARAM] ?? null;
        $ids = is_array($raw) ? array_values($raw) : (is_string($raw) ? explode(',', $raw) : []);
        $ids = array_values(array_filter(array_map('trim', $ids), static fn($id) => $id !== ''));
        if ($ids === [] || array_filter($ids, static fn($id) => !is_string($id) || !ctype_digit($id)) !== []) {
            return $query;
        }

        try {
            $taken = (new TopicList())
                ->build(['ids' => implode(',', $ids), 'containerId' => (int)$space->contentcontainer_id], ListContext::forCurrentUser())
                ->query()
                ->select('content_tag.id')
                ->column();
        } catch (ListValidationException) {
            return $query;
        }

        $taken = array_map('strval', $taken);
        $query[self::TOPIC_PARAM] = implode(',', array_values(array_filter($ids, static fn($id) => in_array($id, $taken, true))));

        return $query;
    }

    /**
     * The space of the URL, when it is one of the page's.
     */
    private function resolveSpace($id, ListContext $context): ?Space
    {
        if (!is_string($id) || !ctype_digit($id)) {
            return null;
        }

        $space = GlobalFolderList::scopeQuery($context)->andWhere(['space.id' => (int)$id])->one();

        return $space instanceof Space ? $space : null;
    }

    /**
     * @return Folder|null null for the space's top level — also for a folder it does not hold
     */
    private function resolveFolder(Space $space, int $fid): ?Folder
    {
        if ($fid <= 0) {
            return null;
        }

        $folder = Folder::find()
            ->contentContainer($space)
            ->readable()
            ->andWhere(['cfiles_folder.id' => $fid])
            ->one();

        return $folder instanceof Folder ? $folder : null;
    }
}
