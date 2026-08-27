<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\cfiles\tests\codeception\unit;

use humhub\modules\cfiles\services\FolderTreeService;
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
    private Folder $root;

    public function _before()
    {
        parent::_before();
        $this->becomeUser('Admin');
        $this->root = FolderTreeService::getOrInitRoot(Space::findOne(1));
    }

    public function testEmptyFolderListsNothingButStillDescribesItself()
    {
        $payload = (new FolderListingService($this->root))->payload();

        $this->assertSame([], $payload['results']);
        $this->assertSame(0, $payload['total']);
        $this->assertSame('folder', $payload['folder']['type']);
        $this->assertTrue($payload['folder']['isRoot']);
        // The path always contains at least the folder itself.
        $this->assertCount(1, $payload['path']);
    }

    public function testFoldersAreListedBeforeFiles()
    {
        $this->addFolder('Zebra');
        $this->addFile('alpha.txt');

        $payload = (new FolderListingService($this->root))->payload();

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

        $service = new FolderListingService($this->root);

        $first = $service->payload(null, null, 1, 3);
        $second = $service->payload(null, null, 2, 3);

        $this->assertSame(4, $first['total']);
        $this->assertSame(2, $first['pages']);
        $this->assertSame(
            ['A folder', 'B folder', 'c.txt'],
            array_column($first['results'], 'title'),
        );
        $this->assertSame(['d.txt'], array_column($second['results'], 'title'));
    }

    public function testSortOrderReversesAndIsRemembered()
    {
        $this->addFolder('A folder');
        $this->addFolder('B folder');

        $descending = (new FolderListingService($this->root))->payload('name', 'desc');
        $this->assertSame(
            ['B folder', 'A folder'],
            array_column($descending['results'], 'title'),
        );

        // A later request that names no sort inherits the one the user last chose.
        $remembered = (new FolderListingService($this->root))->payload();
        $this->assertSame('name', $remembered['sort']);
        $this->assertSame('desc', $remembered['order']);
    }

    public function testAnUnknownSortFallsBackInsteadOfReachingTheQuery()
    {
        $payload = (new FolderListingService($this->root))->payload('; DROP TABLE cfiles_file', 'asc');

        $this->assertSame('name', $payload['sort']);
    }

    public function testAFolderReportsHowManyItemsItHolds()
    {
        $child = $this->addFolder('With children');
        $this->addFile('inside.txt', $child);

        $payload = (new FolderListingService($this->root))->payload();

        $this->assertSame(1, $payload['results'][0]['itemCount']);
    }

    private function addFolder(string $title, ?Folder $parent = null): Folder
    {
        $parent = $parent ?? $this->root;
        $folder = (new FolderContentService($parent))->newFolder($title, '');

        $this->assertTrue($folder->save(), implode(' ', $folder->getFirstErrors()));

        return $folder;
    }

    private function addFile(string $name, ?Folder $parent = null): void
    {
        $parent = $parent ?? $this->root;
        $path = Yii::getAlias('@runtime') . '/' . $name;
        file_put_contents($path, 'test');

        $file = (new FolderContentService($parent))->addFileFromPath($name, $path);

        $this->assertFalse($file->hasErrors(), implode(' ', $file->getFirstErrors()));
    }
}
