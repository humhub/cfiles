<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2026 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\cfiles\tests\codeception\unit;

use humhub\modules\cfiles\controllers\DownloadController;
use humhub\modules\cfiles\services\FolderContentService;
use humhub\modules\file\models\File as BaseFile;
use humhub\modules\space\models\Space;
use tests\codeception\_support\HumHubDbTestCase;
use Yii;
use yii\web\NotFoundHttpException;

/**
 * The download link of the browser redirects to the file — of a file the user may read.
 */
class DownloadControllerTest extends HumHubDbTestCase
{
    private Space $space;

    public function _before()
    {
        parent::_before();
        $this->becomeUser('Admin');
        $this->space = Space::findOne(1);
        if (!$this->space->moduleManager->isEnabled('cfiles')) {
            $this->space->moduleManager->enable('cfiles');
        }
    }

    public function testAFileTheUserMayReadIsRedirectedToForDownload()
    {
        $path = Yii::getAlias('@runtime') . '/' . uniqid() . '-plan.txt';
        file_put_contents($path, 'test');
        $file = (new FolderContentService($this->space))->addFileFromPath('plan.txt', $path);
        $this->assertFalse($file->hasErrors(), implode(' ', $file->getFirstErrors()));

        $response = $this->controller()->actionIndex($file->baseFile->guid);

        $this->assertSame(302, $response->statusCode);
        $this->assertStringContainsString('download=1', $response->headers->get('Location'));
    }

    /**
     * Not even its name: the redirect would carry it.
     */
    public function testAFileTheUserMayNotReadIsA404()
    {
        $this->becomeUser('User2');
        $theirs = new BaseFile(['file_name' => 'salaries.xlsx', 'mime_type' => 'text/plain', 'size' => 4]);
        $this->assertTrue($theirs->save(), implode(' ', $theirs->getFirstErrors()));
        $this->becomeUser('Admin');

        $this->expectException(NotFoundHttpException::class);

        $this->controller()->actionIndex($theirs->guid);
    }

    private function controller(): DownloadController
    {
        Yii::$app->request->setQueryParams(['cguid' => $this->space->guid]);

        return new DownloadController('download', Yii::$app->getModule('cfiles'));
    }
}
