<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2026 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\cfiles\tests\codeception\unit;

use humhub\modules\cfiles\controllers\api\FileController;
use humhub\modules\cfiles\controllers\api\FolderController;
use humhub\modules\cfiles\controllers\api\GlobalController;
use humhub\modules\cfiles\controllers\api\ItemController;
use humhub\modules\cfiles\controllers\api\PreferencesController;
use humhub\modules\cfiles\models\File;
use humhub\modules\cfiles\models\Folder;
use humhub\modules\cfiles\serializers\FileSerializer;
use humhub\modules\cfiles\serializers\FolderSerializer;
use humhub\modules\cfiles\services\FolderContentService;
use humhub\modules\content\models\Content;
use humhub\modules\oauth\components\ScopeFilter;
use humhub\modules\oauth\components\ScopeRegistry;
use humhub\modules\space\models\Space;
use tests\codeception\_support\HumHubDbTestCase;
use Yii;

/**
 * The cfiles API follows the conventions of `/api/v2` (core `docs/develop/concept-api.md`):
 * named enums, one name per reference, the per-item answer of a batch and token scopes.
 */
class ApiConventionsTest extends HumHubDbTestCase
{
    private Space $space;

    public function _before()
    {
        parent::_before();
        $this->becomeUser('Admin');
        Yii::$app->response->statusCode = 200;

        $this->space = Space::findOne(1);
        if (!$this->space->moduleManager->isEnabled('cfiles')) {
            $this->space->moduleManager->enable('cfiles');
        }
    }

    public function testVisibilityIsANamedValue()
    {
        $folder = $this->folder('Shared');
        $file = $this->file('plan.txt');
        Content::updateAll(['visibility' => Content::VISIBILITY_PUBLIC], ['id' => $folder->content->id]);
        Content::updateAll(['visibility' => Content::VISIBILITY_PRIVATE], ['id' => $file->content->id]);

        $this->assertSame('public', FolderSerializer::folder(Folder::findOne($folder->id))['visibility']);
        $this->assertSame('private', FileSerializer::file(File::findOne($file->id))['visibility']);
    }

    public function testAFolderIsCreatedWithANamedVisibility()
    {
        Yii::$app->request->setBodyParams(['title' => 'Internal', 'visibility' => 'private']);

        $answer = $this->folderController()->actionCreate($this->space->contentcontainer_id);

        $this->assertSame(201, Yii::$app->response->statusCode);
        $this->assertSame('private', $answer['visibility']);
        $this->assertTrue(Folder::findOne($answer['id'])->content->isPrivate());
    }

    public function testAFileTakesANamedVisibility()
    {
        $file = $this->file('plan.txt');

        Yii::$app->request->setBodyParams(['visibility' => 'private']);
        $answer = (new FileController('file', Yii::$app->getModule('cfiles')))->actionUpdate($file->id);

        $this->assertSame('private', $answer['visibility']);
        $this->assertTrue(File::findOne($file->id)->content->isPrivate());
    }

    public function testAVisibilityThatIsNoNameIsA422()
    {
        $folder = $this->folder('Shared');
        $file = $this->file('plan.txt');
        Content::updateAll(['visibility' => Content::VISIBILITY_PUBLIC], ['id' => $folder->content->id]);

        // The stored integer is no name.
        Yii::$app->request->setBodyParams(['visibility' => '0']);
        $answer = $this->folderController()->actionUpdate($folder->id);
        $this->assertSame(422, Yii::$app->response->statusCode);
        $this->assertArrayHasKey('visibility', $answer['errors']);
        $this->assertTrue(Folder::findOne($folder->id)->content->isPublic());

        Yii::$app->response->statusCode = 200;
        Yii::$app->request->setBodyParams(['visibility' => 'secret', 'description' => 'changed']);
        $answer = (new FileController('file', Yii::$app->getModule('cfiles')))->actionUpdate($file->id);
        $this->assertSame(422, Yii::$app->response->statusCode);
        $this->assertArrayHasKey('visibility', $answer['errors']);
        $this->assertNotSame('changed', File::findOne($file->id)->description);
    }

    public function testAFolderIsCreatedInTheFolderNamedByParentFolderId()
    {
        $parent = $this->folder('Outer');

        Yii::$app->request->setBodyParams(['title' => 'Inner', 'parentFolderId' => $parent->id]);
        $answer = $this->folderController()->actionCreate($this->space->contentcontainer_id);

        $this->assertSame((int)$parent->id, $answer['parentFolderId']);
    }

    public function testItemsAreMovedIntoTheFolderNamedByParentFolderId()
    {
        $target = $this->folder('Target');
        $folder = $this->folder('Movable');

        Yii::$app->request->setBodyParams([
            'containerId' => $this->space->contentcontainer_id,
            'items' => [['type' => 'folder', 'id' => $folder->id]],
            'parentFolderId' => $target->id,
        ]);
        $this->itemController()->actionMove();

        $this->assertSame((int)$target->id, (int)Folder::findOne($folder->id)->parent_folder_id);
    }

    public function testABatchActionThatCarriesOutNothingStillAnswersPerItem()
    {
        Yii::$app->request->setBodyParams(['items' => [['type' => 'file', 'id' => 999999]]]);

        $answer = $this->itemController()->actionDelete();

        $this->assertSame(200, Yii::$app->response->statusCode);
        $this->assertSame([], $answer['results']);
        $this->assertSame(['type', 'id', 'messages'], array_keys($answer['errors'][0]));
        $this->assertSame(['The item could not be found.'], $answer['errors'][0]['messages']);
    }

    public function testAnItemThatFailsIsReportedWithItsMessages()
    {
        $theirs = $this->file('theirs.txt');
        // A member: may delete what they added, not what others did.
        $this->becomeUser('User2');

        Yii::$app->request->setBodyParams(['items' => [['type' => 'file', 'id' => $theirs->id]]]);
        $answer = $this->itemController()->actionDelete();

        $this->assertSame(['type', 'id', 'title', 'messages'], array_keys($answer['errors'][0]));
        $this->assertSame('theirs.txt', $answer['errors'][0]['title']);
        $this->assertIsArray($answer['errors'][0]['messages']);
    }

    public function testABatchActionWithoutItemsIsA422()
    {
        Yii::$app->request->setBodyParams([]);

        $answer = $this->itemController()->actionDelete();

        $this->assertSame(422, Yii::$app->response->statusCode);
        $this->assertArrayHasKey('items', $answer['errors']);
    }

    public function testAddingOnlyFilesThatCannotBeAddedAnswersPerFile()
    {
        Yii::$app->request->setBodyParams(['guids' => ['no-such-guid']]);

        $answer = $this->folderController()->actionUpload($this->space->contentcontainer_id);

        $this->assertSame(200, Yii::$app->response->statusCode);
        $this->assertSame([], $answer['results']);
        $this->assertSame('no-such-guid', $answer['errors'][0]['fileName']);
    }

    public function testTheModuleRegistersAReadAndAWriteScope()
    {
        $scopes = ScopeRegistry::getScopes();

        $this->assertArrayHasKey('cfiles:read', $scopes);
        $this->assertArrayHasKey('cfiles:write', $scopes);
    }

    public function testReadingNeedsTheReadScopeAndChangingTheWriteScope()
    {
        $module = Yii::$app->getModule('cfiles');
        $expected = [
            [new FolderController('folder', $module), ['items', 'suggestions', 'conflicts'], ['create', 'update', 'upload']],
            [new FileController('file', $module), [], ['update']],
            [new ItemController('item', $module), [], ['move', 'copy', 'delete']],
            [new GlobalController('global', $module), ['items', 'suggestions'], []],
            [new PreferencesController('preferences', $module), [], ['update']],
        ];

        foreach ($expected as [$controller, $reads, $writes]) {
            $declared = $controller->getBehavior('scopeFilter')->scopes;
            foreach ($reads as $action) {
                $this->assertSame('cfiles:read', ScopeFilter::resolveScope($declared, $action), get_class($controller) . '::' . $action);
            }
            foreach ($writes as $action) {
                $this->assertSame('cfiles:write', ScopeFilter::resolveScope($declared, $action), get_class($controller) . '::' . $action);
            }
        }
    }

    private function folderController(): FolderController
    {
        return new FolderController('folder', Yii::$app->getModule('cfiles'));
    }

    private function itemController(): ItemController
    {
        return new ItemController('item', Yii::$app->getModule('cfiles'));
    }

    private function folder(string $title): Folder
    {
        $folder = (new FolderContentService($this->space))->newFolder($title, '');
        $this->assertTrue($folder->save(), implode(' ', $folder->getFirstErrors()));

        return $folder;
    }

    private function file(string $name): File
    {
        $path = Yii::getAlias('@runtime') . '/' . uniqid() . '-' . $name;
        file_put_contents($path, 'test');

        $file = (new FolderContentService($this->space))->addFileFromPath($name, $path);
        $this->assertFalse($file->hasErrors(), implode(' ', $file->getFirstErrors()));

        return $file;
    }
}
