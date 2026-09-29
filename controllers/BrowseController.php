<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2017 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\cfiles\controllers;

use humhub\components\listing\ListContext;
use humhub\modules\cfiles\components\FolderList;
use humhub\modules\cfiles\components\FolderParentFilter;
use humhub\modules\cfiles\models\Folder;
use humhub\modules\cfiles\permissions\WriteAccess;
use humhub\modules\cfiles\services\BrowserPreferences;
use humhub\modules\cfiles\services\FirstListing;
use humhub\modules\cfiles\services\FolderListingService;
use humhub\modules\content\components\ContentContainerController;
use humhub\modules\file\handler\FileHandlerCollection;
use humhub\modules\file\widgets\FileHandlerButtonDropdown;
use humhub\modules\space\models\Space;
use humhub\modules\user\models\User;
use Yii;
use yii\web\HttpException;

/**
 * The file browser page.
 *
 * All it does is mount the `FileBrowser` island and hand it the folder it should open,
 * already filled with its first page — everything after that runs against `/api/v2/cfiles`.
 * Folder navigation inside the browser never comes back here; the island rewrites the URL
 * itself (see `vue/FileBrowser.vue`).
 *
 * @author luke, Sebastian Stumpf
 */
class BrowseController extends ContentContainerController
{
    /**
     * @inheritdoc
     */
    public $hideSidebar = true;

    /**
     * @param int $fid the folder to open; 0 (or absent) is the container's top level. The
     *        parameter name is unchanged from the server-rendered browser, so every existing
     *        permalink, notification link and search result still opens the right folder.
     * @param string|null $edit an item to open the edit dialog for, as `file:<id>` or
     *        `folder:<id>`. This is where a stream entry's Edit control links to — the browser
     *        owns that dialog, so there is no second one to render (see `widgets\WallEntryFile`).
     */
    public function actionIndex($fid = 0, $edit = null)
    {
        $folder = $this->resolveFolder((int)$fid);

        if ($folder !== null && !$folder->content->canView()) {
            throw new HttpException(403);
        }

        return $this->render('index', $this->firstListing($folder) + [
            'contentContainer' => $this->contentContainer,
            'folder' => $folder,
            // Container-wide, not per folder, so the island can keep it across navigation.
            'canWrite' => $this->contentContainer->permissionManager->can(WriteAccess::class),
            // Passed through untouched: the island looks it up among the rows it received and
            // ignores it when there is no match, so a stale link just opens the folder.
            'editItem' => is_string($edit) && preg_match('/^(file|folder):\d+$/', $edit) ? $edit : null,
            // File handlers stay server-rendered, the way the core's own upload field does it:
            // they are menu entries a module contributed, carrying legacy `data-action-click`
            // attributes, and they build their URLs from this request — which still carries the
            // `fid` of the open folder, so a handler creates its document in the right place.
            'createHandlersHtml' => FileHandlerButtonDropdown::widget([
                'handlers' => FileHandlerCollection::getByType([
                    FileHandlerCollection::TYPE_CREATE,
                    FileHandlerCollection::TYPE_IMPORT,
                ]),
                'itemsOnly' => true,
            ]),
            // The container's module settings, for those who may change them — the same
            // group the settings controller admits (see ConfigContainerController).
            'settingsUrl' => $this->canConfigure()
                ? $this->contentContainer->createUrl('/cfiles/config-container')
                : null,
        ]);
    }

    /**
     * The list of the page: the first page of the folder, built with the filters and the sort
     * the page URL carries (the `FilterBar` mirrors them there), so the first paint matches
     * the URL and the bar has nothing to apply on mount.
     *
     * Only the parameters of the list's filters and `sort` are taken from the URL — `fid`,
     * `edit` and whatever else a link carries are the page's. A value the list refuses (a
     * stale link, a hand-edited URL) is dropped rather than failing the page
     * ({@see FirstListing}).
     *
     * @return array{listing: array, filters: array, initialFilters: array<string, string>} the
     *         first page (embedded, so the island paints without a request), the `FilterBar`
     *         definitions and the filter values the page was built with (`''` = not set)
     */
    public function firstListing(?Folder $folder): array
    {
        $context = ListContext::forCurrentUser(null, $this->contentContainer);
        $list = new FolderList(['storedSort' => (new BrowserPreferences($context->user))->sort()]);

        ['builder' => $builder, 'initialFilters' => $initialFilters] = FirstListing::build(
            $list,
            $context,
            Yii::$app->request->getQueryParams(),
            ['parent' => $folder?->id],
            // The folder is `fid` here.
            [FolderParentFilter::KEY],
        );

        return [
            // Without a sort: the user's last choice, which the payload reports.
            'listing' => (new FolderListingService($builder))->payload(),
            'filters' => $list->definitions($context),
            'initialFilters' => $initialFilters,
        ];
    }

    private function canConfigure(): bool
    {
        $container = $this->contentContainer;

        return ($container instanceof Space && $container->isAdmin())
            || ($container instanceof User && $container->is(Yii::$app->user->getIdentity()));
    }

    /**
     * @return Folder|null null for the container's top level, which has no folder record.
     */
    private function resolveFolder(int $fid): ?Folder
    {
        if ($fid === 0) {
            return null;
        }

        $folder = Folder::find()
            ->contentContainer($this->contentContainer)
            ->readable()
            ->andWhere(['cfiles_folder.id' => $fid])
            ->one();

        if (!$folder instanceof Folder) {
            throw new HttpException(404);
        }

        return $folder;
    }
}
