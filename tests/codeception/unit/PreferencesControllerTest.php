<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2026 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\cfiles\tests\codeception\unit;

use humhub\modules\cfiles\controllers\api\PreferencesController;
use humhub\modules\cfiles\services\BrowserPreferences;
use tests\codeception\_support\HumHubDbTestCase;
use Yii;

/**
 * `PATCH api/v2/cfiles/preferences` ({@see PreferencesController::actionUpdate()}) — the
 * action itself; the module's API suite is not wired to `/api/v2`, so routing, the verb filter
 * and authentication are the core's and not exercised here.
 */
class PreferencesControllerTest extends HumHubDbTestCase
{
    public function _before()
    {
        parent::_before();
        $this->becomeUser('Admin');
        Yii::$app->response->statusCode = 200;
    }

    public function testTheViewIsStoredAndAnswered()
    {
        $this->assertSame(['view' => 'tiles'], $this->patch(['view' => 'tiles']));
        $this->assertSame(200, Yii::$app->response->statusCode);

        $this->assertSame('tiles', (new BrowserPreferences(Yii::$app->user->getIdentity()))->view());
    }

    public function testAnUnknownViewIsRefused()
    {
        $answer = $this->patch(['view' => 'gallery']);

        $this->assertSame(422, Yii::$app->response->statusCode);
        $this->assertArrayHasKey('view', $answer['errors']);
        $this->assertSame('list', (new BrowserPreferences(Yii::$app->user->getIdentity()))->view());
    }

    public function testAMissingViewIsRefused()
    {
        $answer = $this->patch([]);

        $this->assertSame(422, Yii::$app->response->statusCode);
        $this->assertArrayHasKey('view', $answer['errors']);
    }

    private function patch(array $body): array
    {
        Yii::$app->request->setBodyParams($body);

        return (new PreferencesController('preferences', Yii::$app->getModule('cfiles')))->actionUpdate();
    }
}
