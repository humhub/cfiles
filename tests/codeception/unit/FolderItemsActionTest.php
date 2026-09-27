<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2026 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\cfiles\tests\codeception\unit;

use humhub\modules\cfiles\controllers\api\FolderController;
use humhub\modules\cfiles\services\FolderContentService;
use humhub\modules\space\models\Space;
use tests\codeception\_support\HumHubDbTestCase;
use Yii;

/**
 * `GET api/v2/cfiles/<containerId>/items` ({@see FolderController::actionItems()}) — the action
 * itself, with the query parameters the URL rule leaves (the container among them).
 */
class FolderItemsActionTest extends HumHubDbTestCase
{
    public function _before()
    {
        parent::_before();
        $this->becomeUser('Admin');
        Yii::$app->response->statusCode = 200;
    }

    public function testTheContainerOfThePathIsNoListParameter()
    {
        $answer = $this->items(['sort' => 'newest', 'pageSize' => '10']);

        $this->assertSame(200, Yii::$app->response->statusCode);
        $this->assertSame('newest', $answer['sort']);
        $this->assertSame(10, $answer['pageSize']);
    }

    public function testTheSortSentIsRememberedAndDefaultForgetsIt()
    {
        $this->items(['sort' => 'nameDesc']);
        $this->assertSame('nameDesc', $this->items([])['sort']);

        $this->assertSame('default', $this->items(['sort' => 'default'])['sort']);
        $this->assertSame('default', $this->items([])['sort']);
    }

    public function testARefusedRequestRemembersNothing()
    {
        $this->items(['sort' => 'nameDesc', 'view' => 'tiles']);

        Yii::$app->response->statusCode = 200;
        $this->assertSame('default', $this->items([])['sort']);
    }

    public function testAGuestsSortIsNotRemembered()
    {
        $this->logout();

        $this->assertSame('newest', $this->items(['sort' => 'newest'])['sort']);
        $this->assertSame('default', $this->items([])['sort']);
    }

    public function testAnUnknownParameterIsA422()
    {
        $answer = $this->items(['view' => 'tiles']);

        $this->assertSame(422, Yii::$app->response->statusCode);
        $this->assertArrayHasKey('view', $answer['errors']);
    }

    public function testAParentOfAnotherContainerIsA422()
    {
        $foreign = (new FolderContentService(Space::findOne(2)))->newFolder('Elsewhere', '');
        $this->assertTrue($foreign->save());

        $answer = $this->items(['parent' => (string)$foreign->id]);

        $this->assertSame(422, Yii::$app->response->statusCode);
        $this->assertArrayHasKey('parent', $answer['errors']);
    }

    public function testAFilterValueTheListDoesNotKnowIsA422()
    {
        $answer = $this->items(['type' => 'archive', 'modified' => 'yesterday']);

        $this->assertSame(422, Yii::$app->response->statusCode);
        $this->assertArrayHasKey('type', $answer['errors']);
        $this->assertArrayHasKey('modified', $answer['errors']);
    }

    public function testTheFiltersSwitchToResultsMode()
    {
        $this->assertTrue($this->items(['q' => 'x'])['resultsMode']);
        $this->assertFalse($this->items([])['resultsMode']);
    }

    private function items(array $query): array
    {
        $space = Space::findOne(1);
        if (!$space->moduleManager->isEnabled('cfiles')) {
            $space->moduleManager->enable('cfiles');
        }
        Yii::$app->request->setQueryParams(['containerId' => (string)$space->contentcontainer_id] + $query);

        return (new FolderController('folder', Yii::$app->getModule('cfiles')))->actionItems($space->contentcontainer_id);
    }
}
