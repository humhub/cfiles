<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2017 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\cfiles\controllers;

use humhub\modules\cfiles\models\Folder;
use humhub\modules\cfiles\services\FolderTreeService;
use humhub\modules\cfiles\permissions\WriteAccess;
use humhub\modules\cfiles\services\FolderListingService;
use humhub\modules\content\components\ContentContainerController;
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
     * @param int $fid the folder to open; 0 (or absent) is the container's root folder. The
     *        parameter name is unchanged from the server-rendered browser, so every existing
     *        permalink, notification link and search result still opens the right folder.
     */
    public function actionIndex($fid = 0)
    {
        FolderTreeService::ensureRootStructure($this->contentContainer);

        $folder = $this->resolveFolder((int)$fid);

        if (!$folder->content->canView()) {
            throw new HttpException(403);
        }

        return $this->render('index', [
            'contentContainer' => $this->contentContainer,
            'folder' => $folder,
            // The first page travels with the HTML, so the island paints without a request.
            'listing' => (new FolderListingService($folder))->payload(),
            // Container-wide, not per folder, so the island can keep it across navigation.
            'canWrite' => $this->contentContainer->permissionManager->can(WriteAccess::class),
        ]);
    }

    private function resolveFolder(int $fid): Folder
    {
        if ($fid === 0) {
            $root = FolderTreeService::getRoot($this->contentContainer);

            if (!$root instanceof Folder) {
                throw new HttpException(404);
            }

            return $root;
        }

        $folder = Folder::find()
            ->contentContainer($this->contentContainer)
            ->readable()
            ->where(['cfiles_folder.id' => $fid])
            ->one();

        if (!$folder instanceof Folder) {
            throw new HttpException(404);
        }

        return $folder;
    }
}
