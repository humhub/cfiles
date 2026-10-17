<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2026 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\cfiles\tests\codeception\unit;

use humhub\modules\cfiles\controllers\api\GlobalController;
use humhub\modules\content\models\ContentContainerModuleState;
use humhub\modules\space\models\Space;
use tests\codeception\_support\HumHubDbTestCase;
use Yii;
use yii\base\InlineAction;
use yii\web\NotFoundHttpException;
use yii\web\Request;
use yii\web\UnauthorizedHttpException;
use yii\web\UrlManager;

/**
 * `GET api/v2/cfiles/items` ({@see GlobalController::actionItems()}) — the global files page's
 * listing: switched off it does not exist, and it is for logged-in users only.
 */
class GlobalItemsActionTest extends HumHubDbTestCase
{
    public function _before()
    {
        parent::_before();
        ContentContainerModuleState::deleteAll(['module_id' => 'cfiles']);
        foreach ([1, 3] as $spaceId) {
            (new ContentContainerModuleState([
                'module_id' => 'cfiles',
                'contentcontainer_id' => Space::findOne($spaceId)->contentcontainer_id,
                'module_state' => ContentContainerModuleState::STATE_ENABLED,
            ]))->save();
        }
        $this->switchOn(true);
        $this->becomeUser('User2');
        Yii::$app->response->statusCode = 200;
    }

    public function testTheTilesAreAnswered()
    {
        $answer = $this->items([]);

        $this->assertSame(200, Yii::$app->response->statusCode);
        $this->assertTrue($answer['global']);
        $this->assertFalse($answer['resultsMode']);
        $this->assertSame([1, 3], array_column($answer['results'], 'id'));
    }

    public function testThePageSizeIsTaken()
    {
        $answer = $this->items(['pageSize' => '1', 'page' => '2']);

        $this->assertSame(1, $answer['pageSize']);
        $this->assertSame([3], array_column($answer['results'], 'id'));
    }

    public function testSwitchedOffTheEndpointIsA404()
    {
        $this->switchOn(false);
        $controller = $this->controller();

        $this->expectException(NotFoundHttpException::class);
        $controller->beforeAction(new InlineAction('items', $controller, 'actionItems'));
    }

    public function testAGuestIsRefused()
    {
        $this->logout();
        $user = Yii::$app->get('user');
        $server = [$_SERVER['REQUEST_METHOD'] ?? null, $_SERVER['REQUEST_URI'] ?? null];
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/api/v2/cfiles/items';
        Yii::$app->request->setPathInfo('api/v2/cfiles/items');
        Yii::$app->request->setQueryParams([]);

        try {
            $this->controller()->runAction('items');
            $this->fail('A guest must be refused.');
        } catch (UnauthorizedHttpException) {
            $this->assertTrue(true);
        } finally {
            Yii::$app->set('user', $user);
            Yii::$app->request->setPathInfo(null);
            foreach (['REQUEST_METHOD', 'REQUEST_URI'] as $i => $key) {
                if ($server[$i] === null) {
                    unset($_SERVER[$key]);
                } else {
                    $_SERVER[$key] = $server[$i];
                }
            }
            Yii::$app->response->format = 'html';
        }
    }

    public function testAnUnknownParameterIsA422()
    {
        $answer = $this->items(['parent' => '1']);

        $this->assertSame(422, Yii::$app->response->statusCode);
        $this->assertArrayHasKey('parent', $answer['errors']);
    }

    public function testTheSortSentIsRememberedAndDefaultForgetsIt()
    {
        $this->items(['sort' => 'nameDesc']);
        $this->assertSame('nameDesc', $this->items([])['sort']);

        $this->assertSame('default', $this->items(['sort' => 'default'])['sort']);
        $this->assertSame('default', $this->items([])['sort']);
    }

    public function testTheRouteComesBeforeTheContainerRules()
    {
        $config = require dirname(__DIR__, 3) . '/config.php';
        $manager = new UrlManager([
            'enablePrettyUrl' => true,
            'showScriptName' => false,
            'baseUrl' => '',
            'rules' => $config['urlManagerRules'],
        ]);

        $method = $_SERVER['REQUEST_METHOD'] ?? null;
        $_SERVER['REQUEST_METHOD'] = 'GET';
        try {
            $request = new Request(['pathInfo' => 'api/v2/cfiles/items']);
            $this->assertSame(['cfiles/api/global/items', []], $manager->parseRequest($request));
        } finally {
            if ($method === null) {
                unset($_SERVER['REQUEST_METHOD']);
            } else {
                $_SERVER['REQUEST_METHOD'] = $method;
            }
        }
    }

    private function items(array $query): array
    {
        Yii::$app->request->setQueryParams($query);

        return $this->controller()->actionItems();
    }

    private function controller(): GlobalController
    {
        return new GlobalController('global', Yii::$app->getModule('cfiles'));
    }

    private function switchOn(bool $on): void
    {
        Yii::$app->getModule('cfiles')->settings->set('showGlobalMenuItem', $on);
    }
}
