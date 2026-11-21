<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2026 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\cfiles\tests\codeception\unit;

use humhub\modules\cfiles\controllers\api\ItemController;
use humhub\modules\cfiles\models\File;
use humhub\modules\cfiles\models\Folder;
use humhub\modules\cfiles\services\FolderContentService;
use humhub\modules\space\models\Space;
use tests\codeception\_support\HumHubDbTestCase;
use Yii;

/**
 * `POST api/v2/cfiles/items/move` and `…/items/delete` ({@see ItemController}) answer per item:
 * an item that is gone, unreadable or not the caller's to change is reported in `errors`, and
 * the others are still carried out.
 */
class ItemBatchActionTest extends HumHubDbTestCase
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

    public function testDeletingReportsAnItemThatIsGoneAndDeletesTheOthers()
    {
        $folder = $this->folder('Old');

        $answer = $this->delete([['type' => 'file', 'id' => 999999], ['type' => 'folder', 'id' => $folder->id]]);

        $this->assertSame(200, Yii::$app->response->statusCode);
        $this->assertSame([['type' => 'folder', 'id' => (int)$folder->id]], $answer['results']);
        $this->assertSame(['file', 999999], [$answer['errors'][0]['type'], $answer['errors'][0]['id']]);
        $this->assertTrue(Folder::findOne($folder->id)->content->getStateService()->isDeleted());
    }

    public function testDeletingReportsAnItemTheCallerMayNotChangeAndDeletesTheOthers()
    {
        $theirs = $this->file('theirs.txt');
        // A member: may delete what they added, not what others did.
        $this->becomeUser('User2');
        $mine = $this->file('mine.txt');

        $answer = $this->delete([['type' => 'file', 'id' => $theirs->id], ['type' => 'file', 'id' => $mine->id]]);

        $this->assertSame([['type' => 'file', 'id' => (int)$mine->id]], $answer['results']);
        $this->assertSame((int)$theirs->id, $answer['errors'][0]['id']);
        $this->assertTrue(File::findOne($theirs->id)->content->getStateService()->isPublished());
    }

    public function testMovingReportsAnItemThatIsGoneAndMovesTheOthers()
    {
        $target = $this->folder('Target');
        $folder = $this->folder('Movable');

        $answer = $this->move([['type' => 'folder', 'id' => 999999], ['type' => 'folder', 'id' => $folder->id]], $target);

        $this->assertSame([['type' => 'folder', 'id' => (int)$folder->id]], $answer['results']);
        $this->assertSame(999999, $answer['errors'][0]['id']);
        $this->assertSame((int)$target->id, (int)Folder::findOne($folder->id)->parent_folder_id);
    }

    public function testMoreItemsThanOneRequestMayCarryAreRefusedAsAWhole()
    {
        $folder = $this->folder('Untouched');
        $items = array_fill(0, ItemController::MAX_ITEMS, ['type' => 'file', 'id' => 999999]);
        $items[] = ['type' => 'folder', 'id' => $folder->id];

        $answer = $this->delete($items);

        $this->assertSame(422, Yii::$app->response->statusCode);
        $this->assertArrayHasKey('items', $answer['errors']);
        $this->assertTrue(Folder::findOne($folder->id)->content->getStateService()->isPublished());
    }

    private function delete(array $items): array
    {
        Yii::$app->request->setBodyParams(['items' => $items]);

        return $this->controller()->actionDelete();
    }

    private function move(array $items, ?Folder $target): array
    {
        Yii::$app->request->setBodyParams([
            'containerId' => $this->space->contentcontainer_id,
            'items' => $items,
            'parentFolderId' => $target?->id,
        ]);

        return $this->controller()->actionMove();
    }

    private function controller(): ItemController
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
