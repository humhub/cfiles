<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\cfiles\tests\codeception\unit;

use humhub\modules\cfiles\models\File;
use humhub\modules\cfiles\services\FolderContentService;
use humhub\modules\space\models\Space;
use tests\codeception\_support\HumHubDbTestCase;
use Yii;

/**
 * The name-collision checks behind folder creation and file storage
 * ({@see FolderContentService::fileExists()}, {@see FolderContentService::folderExists()}):
 * a name is only a collision within the container the service was built for, never across
 * containers.
 */
class FolderContentServiceTest extends HumHubDbTestCase
{
    private Space $spaceA;
    private Space $spaceB;

    public function _before()
    {
        parent::_before();
        $this->becomeUser('Admin');
        $this->spaceA = Space::findOne(1);
        $this->spaceB = Space::findOne(2);
    }

    public function testStoringAFileIsNotRenamedForANameUsedAtTheTopLevelOfAnotherContainer()
    {
        $this->addFile($this->spaceA, 'report.txt');

        $inOtherSpace = $this->addFile($this->spaceB, 'report.txt');

        $this->assertSame('report.txt', $inOtherSpace->getTitle());
    }

    public function testStoringAFileIsStillRenamedForANameUsedInTheSameContainer()
    {
        $this->addFile($this->spaceA, 'report.txt');

        $again = $this->addFile($this->spaceA, 'report.txt');

        $this->assertSame('report(1).txt', $again->getTitle());
    }

    public function testCreatingAFolderIsNotBlockedByANameUsedAtTheTopLevelOfAnotherContainer()
    {
        $this->addFolder($this->spaceA, 'Projects');

        $folder = (new FolderContentService($this->spaceB))->newFolder('Projects', '');

        $this->assertTrue($folder->save(), implode(' ', $folder->getFirstErrors()));
    }

    public function testCreatingAFolderIsStillBlockedByANameUsedInTheSameContainer()
    {
        $this->addFolder($this->spaceA, 'Projects');

        $duplicate = (new FolderContentService($this->spaceA))->newFolder('Projects', '');

        $this->assertFalse($duplicate->save());
        $this->assertArrayHasKey('title', $duplicate->getErrors());
    }

    private function addFile(Space $space, string $name): File
    {
        $path = Yii::getAlias('@runtime') . '/' . uniqid() . '-' . $name;
        file_put_contents($path, 'test');

        $file = (new FolderContentService($space))->addFileFromPath($name, $path);

        $this->assertFalse($file->hasErrors(), implode(' ', $file->getFirstErrors()));

        return $file;
    }

    private function addFolder(Space $space, string $title): void
    {
        $folder = (new FolderContentService($space))->newFolder($title, '');

        $this->assertTrue($folder->save(), implode(' ', $folder->getFirstErrors()));
    }
}
