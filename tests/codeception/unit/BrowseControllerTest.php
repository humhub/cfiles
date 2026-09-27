<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2026 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\cfiles\tests\codeception\unit;

use humhub\modules\cfiles\controllers\BrowseController;
use humhub\modules\cfiles\services\FolderContentService;
use humhub\modules\space\models\Space;
use tests\codeception\_support\HumHubDbTestCase;
use Yii;
use yii\web\HttpException;

/**
 * The file browser page ({@see BrowseController::actionIndex()}): a `fid` it cannot open is a
 * 404, whatever the reason; the first page is built with the filters and the sort of the page
 * URL, so the first paint matches it.
 */
class BrowseControllerTest extends HumHubDbTestCase
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

    public function testAFolderOfAnotherContainerIsA404()
    {
        $foreign = (new FolderContentService(Space::findOne(2)))->newFolder('Elsewhere', '');
        $this->assertTrue($foreign->save());

        try {
            $this->controller([])->actionIndex($foreign->id);
            $this->fail('A folder of another container must not open.');
        } catch (HttpException $e) {
            $this->assertSame(404, $e->statusCode);
        }
    }

    public function testTheFirstPageFollowsTheFiltersAndTheSortOfTheUrl()
    {
        $brand = (new FolderContentService($this->space))->newFolder('Brand', '');
        $this->assertTrue($brand->save());
        $this->addFile('logo-b.png', $brand);
        $this->addFile('logo-a.png');
        $this->addFile('notes.txt');

        $props = $this->props(['q' => 'logo', 'type' => 'image', 'sort' => 'nameDesc', 'fid' => '0', 'edit' => 'x']);

        $this->assertTrue($props['listing']['resultsMode']);
        $this->assertSame('nameDesc', $props['listing']['sort']);
        $this->assertSame(['logo-b.png', 'logo-a.png'], array_column($props['listing']['results'], 'title'));
        $this->assertSame(['q' => 'logo', 'userId' => '', 'type' => 'image', 'modified' => ''], $props['initialFilters']);
    }

    public function testAValueTheListRefusesIsDropped()
    {
        $this->addFile('logo.png');
        $this->addFile('notes.txt');

        $props = $this->props(['q' => 'logo', 'type' => 'archive', 'modified' => ['x'], 'sort' => 'bogus']);

        $this->assertSame(['logo.png'], array_column($props['listing']['results'], 'title'));
        $this->assertSame('default', $props['listing']['sort']);
        $this->assertSame(['q' => 'logo', 'userId' => '', 'type' => '', 'modified' => ''], $props['initialFilters']);
    }

    public function testWithoutFiltersThePageIsTheLevel()
    {
        $props = $this->props([]);

        $this->assertFalse($props['listing']['resultsMode']);
        $this->assertSame(['q' => '', 'userId' => '', 'type' => '', 'modified' => ''], $props['initialFilters']);
    }

    public function testTheAuthorOfTheUrlIsTaken()
    {
        $props = $this->props(['userId' => '1']);

        $this->assertTrue($props['listing']['resultsMode']);
        $this->assertSame('1', $props['initialFilters']['userId']);
    }

    /**
     * A guest has no Author filter: their link's `userId` is dropped, not a failing page.
     */
    public function testAGuestsAuthorIsDropped()
    {
        $this->logout();

        $props = $this->props(['userId' => '1', 'q' => 'logo']);

        // Built, with the search it could apply.
        $this->assertTrue($props['listing']['resultsMode']);
        $this->assertSame('', $props['initialFilters']['userId']);
        $this->assertSame('logo', $props['initialFilters']['q']);
        $this->assertNotContains('userId', array_column($props['filters'], 'key'));
    }

    /**
     * What the page hands the island about its list, for the top level.
     */
    private function props(array $query): array
    {
        return $this->controller($query)->firstListing(null);
    }

    private function controller(array $query): BrowseController
    {
        Yii::$app->request->setQueryParams(['cguid' => $this->space->guid] + $query);

        return new BrowseController('browse', Yii::$app->getModule('cfiles'));
    }

    private function addFile(string $name, $parent = null): void
    {
        $path = Yii::getAlias('@runtime') . '/' . $name;
        file_put_contents($path, 'test');

        $file = (new FolderContentService($this->space, $parent))->addFileFromPath($name, $path);

        $this->assertFalse($file->hasErrors(), implode(' ', $file->getFirstErrors()));
    }
}
