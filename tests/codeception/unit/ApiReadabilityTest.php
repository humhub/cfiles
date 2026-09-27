<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2026 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\cfiles\tests\codeception\unit;

use humhub\modules\cfiles\controllers\api\FolderController;
use humhub\modules\cfiles\controllers\api\ItemController;
use humhub\modules\cfiles\models\Folder;
use humhub\modules\cfiles\services\FolderContentService;
use humhub\modules\content\models\Content;
use humhub\modules\space\models\Space;
use tests\codeception\_support\HumHubDbTestCase;
use Yii;
use yii\web\NotFoundHttpException;

/**
 * A folder the caller cannot read is not there for the API — also for a caller who may write
 * to its container (`findFolder()`/`findParent()` of the API base controller).
 *
 * "Unreadable" is a folder in the trash here: `readable()` leaves out every state but
 * published (and the caller's own drafts). A private folder is readable by every member,
 * and a non-member cannot write, so the trash is how a writer meets a folder they cannot read.
 */
class ApiReadabilityTest extends HumHubDbTestCase
{
    private Space $space;

    private Folder $unreadable;

    public function _before()
    {
        parent::_before();
        $this->becomeUser('Admin');
        Yii::$app->response->statusCode = 200;

        $this->space = Space::findOne(1);
        if (!$this->space->moduleManager->isEnabled('cfiles')) {
            $this->space->moduleManager->enable('cfiles');
        }

        $this->unreadable = (new FolderContentService($this->space))->newFolder('Trashed', '');
        $this->assertTrue($this->unreadable->save());
        Content::updateAll(['state' => Content::STATE_DELETED], ['id' => $this->unreadable->content->id]);
    }

    public function testCreatingInAnUnreadableFolderIsA404()
    {
        Yii::$app->request->setBodyParams(['title' => 'New', 'parent' => $this->unreadable->id]);

        $this->expectException(NotFoundHttpException::class);
        $this->folderController()->actionCreate($this->space->contentcontainer_id);
    }

    public function testUploadingIntoAnUnreadableFolderIsA404()
    {
        Yii::$app->request->setBodyParams(['parent' => (string)$this->unreadable->id]);

        $this->expectException(NotFoundHttpException::class);
        $this->folderController()->actionUpload($this->space->contentcontainer_id);
    }

    public function testMovingIntoAnUnreadableFolderIsA404()
    {
        $folder = (new FolderContentService($this->space))->newFolder('Movable', '');
        $this->assertTrue($folder->save());

        Yii::$app->request->setBodyParams([
            'containerId' => $this->space->contentcontainer_id,
            'items' => [['type' => 'folder', 'id' => $folder->id]],
            'targetFolderId' => $this->unreadable->id,
        ]);

        $this->expectException(NotFoundHttpException::class);
        (new ItemController('item', Yii::$app->getModule('cfiles')))->actionMove();
    }

    public function testChangingAnUnreadableFolderIsA404()
    {
        Yii::$app->request->setBodyParams(['title' => 'Renamed']);

        $this->expectException(NotFoundHttpException::class);
        $this->folderController()->actionUpdate($this->unreadable->id);
    }

    private function folderController(): FolderController
    {
        return new FolderController('folder', Yii::$app->getModule('cfiles'));
    }
}
