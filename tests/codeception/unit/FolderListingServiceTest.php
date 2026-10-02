<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\cfiles\tests\codeception\unit;

use humhub\components\listing\ListContext;
use humhub\modules\cfiles\components\FolderList;
use humhub\modules\cfiles\components\FolderListBuilder;
use humhub\modules\cfiles\services\FolderContentService;
use humhub\modules\cfiles\models\Folder;
use humhub\modules\cfiles\services\FolderListingService;
use humhub\modules\space\models\Space;
use tests\codeception\_support\HumHubDbTestCase;
use Yii;

/**
 * The listing the file browser and its API endpoint both read
 * ({@see FolderListingService}).
 */
class FolderListingServiceTest extends HumHubDbTestCase
{
    private Space $space;

    public function _before()
    {
        parent::_before();
        $this->becomeUser('Admin');
        $this->space = Space::findOne(1);
    }

    public function testEmptyFolderListsNothingButStillDescribesItself()
    {
        $folder = $this->addFolder('Empty');

        $payload = $this->payload(['parent' => $folder->id]);

        $this->assertSame([], $payload['results']);
        $this->assertSame(0, $payload['total']);
        $this->assertSame('folder', $payload['folder']['type']);
        $this->assertSame($folder->id, $payload['folder']['id']);
        // The path of a folder always contains at least the folder itself.
        $this->assertSame([$folder->id], array_column($payload['path'], 'id'));
    }

    public function testEmptyTopLevelListsNothingAndHasNoFolderRecord()
    {
        $payload = $this->payload();

        $this->assertSame([], $payload['results']);
        $this->assertSame(0, $payload['total']);
        // There is no folder record standing in for the top level, so nothing to describe.
        $this->assertNull($payload['folder']);
        $this->assertSame([], $payload['path']);
    }

    public function testFoldersAreListedBeforeFiles()
    {
        $this->addFolder('Zebra');
        $this->addFile('alpha.txt');

        $payload = $this->payload();

        $this->assertSame(['folder', 'file'], array_column($payload['results'], 'type'));
        $this->assertSame('Zebra', $payload['results'][0]['title']);
        $this->assertSame('alpha.txt', $payload['results'][1]['title']);
    }

    /**
     * The page window is cut across the folder/file seam — the reason the listing runs two
     * queries instead of one. A second page that starts inside the files has to skip exactly
     * the folders that came before it, not restart at the first file.
     */
    public function testPaginationCutsAcrossTheFolderFileSeam()
    {
        $this->addFolder('A folder');
        $this->addFolder('B folder');
        $this->addFile('c.txt');
        $this->addFile('d.txt');

        $first = $this->payload([], 1, 3);
        $second = $this->payload([], 2, 3);

        $this->assertSame(4, $first['total']);
        $this->assertSame(2, $first['pages']);
        $this->assertSame(
            ['A folder', 'B folder', 'c.txt'],
            array_column($first['results'], 'title'),
        );
        $this->assertSame(['d.txt'], array_column($second['results'], 'title'));
    }

    public function testSortReversesAndIsReportedAsItsKey()
    {
        $this->addFolder('A folder');
        $this->addFolder('B folder');

        $descending = $this->payload(['sort' => 'nameDesc']);
        $this->assertSame(
            ['B folder', 'A folder'],
            array_column($descending['results'], 'title'),
        );
        // The key is reported, there is no separate direction any more.
        $this->assertSame('nameDesc', $descending['sort']);
        $this->assertArrayNotHasKey('order', $descending);
    }

    public function testTheDefaultOrderIsReportedAsDefault()
    {
        $this->assertSame('default', $this->payload()['sort']);
    }

    /**
     * The view is a preference of its own (`PATCH preferences`), not a parameter of the list:
     * the payload reports the stored one, and without a `pageSize` the page is that view's.
     */
    public function testThePageSizeFollowsTheStoredView()
    {
        $this->assertSame('list', $this->payload()['view']);
        $this->assertSame(FolderListingService::VIEWS['list'], $this->payload()['pageSize']);

        Yii::$app->getModule('cfiles')->settings->user(Yii::$app->user->getIdentity())->set('defaultView', 'tiles');

        $this->assertSame('tiles', $this->payload()['view']);
        $this->assertSame(FolderListingService::VIEWS['tiles'], $this->payload()['pageSize']);
        // An explicit page size wins.
        $this->assertSame(10, $this->payload([], 1, 10)['pageSize']);
    }

    public function testAFolderReportsHowManyItemsItHolds()
    {
        $child = $this->addFolder('With children');
        $this->addFile('inside.txt', $child);

        $payload = $this->payload();

        $this->assertSame(1, $payload['results'][0]['itemCount']);
    }

    public function testALevelIsNoResultListAndItsItemsHaveNoPath()
    {
        $this->addFolder('Brand');
        $this->addFile('top.txt');

        $payload = $this->payload();

        $this->assertFalse($payload['resultsMode']);
        $this->assertSame([[], []], array_column($payload['results'], 'path'));
    }

    public function testAResultListSaysSoAndPlacesEachHit()
    {
        $brand = $this->addFolder('Brand');
        $logos = $this->addFolder('Logos', $brand);
        $this->addFile('logo-top.png');
        $this->addFile('logo-brand.png', $brand);
        $this->addFile('logo-deep.png', $logos);

        $payload = $this->payload(['q' => 'logo', 'sort' => 'name']);

        $this->assertTrue($payload['resultsMode']);
        $this->assertSame(4, $payload['total']);
        $this->assertSame(
            ['Logos' => [['type' => 'folder', 'id' => $brand->id, 'title' => 'Brand']], 'logo-brand.png' => [['type' => 'folder', 'id' => $brand->id, 'title' => 'Brand']],
                'logo-deep.png' => [['type' => 'folder', 'id' => $brand->id, 'title' => 'Brand'], ['type' => 'folder', 'id' => $logos->id, 'title' => 'Logos']], 'logo-top.png' => []],
            array_column($payload['results'], 'path', 'title'),
        );

        // Relative to the open folder, which is not part of it.
        $inBrand = $this->payload(['parent' => $brand->id, 'q' => 'logo', 'sort' => 'name']);
        $this->assertSame(
            ['Logos' => [], 'logo-brand.png' => [], 'logo-deep.png' => [['type' => 'folder', 'id' => $logos->id, 'title' => 'Logos']]],
            array_column($inBrand['results'], 'path', 'title'),
        );
    }

    public function testAResultListOverSeveralSpacesPlacesEachHitInItsSpace()
    {
        $other = Space::findOne(3);
        $brand = $this->addFolder('Brand', null, $other);
        $this->addFile('logo-top.png');
        $this->addFile('logo-brand.png', $brand, $other);

        $builder = new FolderListBuilder([$this->space, $other], ListContext::forCurrentUser());
        $builder->prefixContainer = true;
        $builder->scopeToSubtree();
        $builder->folderQuery()->orderBy(['cfiles_folder.title' => SORT_ASC]);
        $builder->fileQuery()->orderBy(['file.file_name' => SORT_ASC]);
        $payload = (new FolderListingService($builder))->payload();

        $space1 = ['type' => 'space', 'id' => $this->space->id, 'contentContainerId' => $this->space->contentcontainer_id, 'guid' => $this->space->guid, 'title' => $this->space->name];
        $space3 = ['type' => 'space', 'id' => $other->id, 'contentContainerId' => $other->contentcontainer_id, 'guid' => $other->guid, 'title' => $other->name];
        $this->assertSame(
            ['Brand' => [$space3], 'logo-brand.png' => [$space3, ['type' => 'folder', 'id' => $brand->id, 'title' => 'Brand']], 'logo-top.png' => [$space1]],
            array_column($payload['results'], 'path', 'title'),
        );
    }

    /**
     * The file browser of the global page takes the right to write from each level it opens.
     */
    public function testALevelSaysWhetherTheUserMayWriteInItsContainer()
    {
        $this->assertTrue($this->payload()['canWrite']);

        // User1 is no member of space 1: they may read its public files, not add any.
        $this->becomeUser('User1');
        // Fresh, as in a request of theirs: the record caches the permissions of its user.
        $this->space = Space::findOne(1);
        $this->assertFalse($this->payload()['canWrite']);
    }

    public function testAListOverSeveralContainersHasNoRightToWrite()
    {
        $builder = new FolderListBuilder([$this->space, Space::findOne(3)], ListContext::forCurrentUser());
        $builder->prefixContainer = true;
        $builder->scopeToSubtree();

        $this->assertFalse((new FolderListingService($builder))->payload()['canWrite']);
    }

    /**
     * The rows reuse the listed container records rather than looking up each row's own: a
     * change the record has only in memory shows in the row.
     */
    public function testTheRowsUseTheListedContainers()
    {
        $this->addFile('logo.png');
        $space = Space::findOne($this->space->id);
        $space->guid = 'in-memory-only';

        $builder = new FolderListBuilder([$space], ListContext::forCurrentUser());
        $payload = (new FolderListingService($builder))->payload();

        $this->assertStringContainsString('in-memory-only', $payload['results'][0]['downloadUrl']);
    }

    private function payload(array $params = [], int $page = 1, ?int $pageSize = null): array
    {
        $builder = (new FolderList())->build($params, ListContext::forCurrentUser(null, $this->space));

        return (new FolderListingService($builder))->payload($page, $pageSize);
    }

    private function addFolder(string $title, ?Folder $parent = null, ?Space $space = null): Folder
    {
        // null is the top level
        $folder = (new FolderContentService($space ?? $this->space, $parent))->newFolder($title, '');

        $this->assertTrue($folder->save(), implode(' ', $folder->getFirstErrors()));

        return $folder;
    }

    private function addFile(string $name, ?Folder $parent = null, ?Space $space = null): void
    {
        // null is the top level
        $path = Yii::getAlias('@runtime') . '/' . $name;
        file_put_contents($path, 'test');

        $file = (new FolderContentService($space ?? $this->space, $parent))->addFileFromPath($name, $path);

        $this->assertFalse($file->hasErrors(), implode(' ', $file->getFirstErrors()));
    }
}
