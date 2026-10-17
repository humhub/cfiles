<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\cfiles\tests\codeception\unit;

use humhub\modules\cfiles\models\File;
use humhub\modules\cfiles\services\FolderContentService;
use humhub\modules\file\models\FileContent;
use humhub\modules\post\models\Post;
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

    public function testAnExistingFileOfTheUserIsFiledIntoTheLevel()
    {
        $baseFile = $this->createBaseFile('created.docx');

        $file = (new FolderContentService($this->spaceA))->addExistingFile($baseFile);

        $this->assertNotNull($file);
        $this->assertFalse($file->isNewRecord, implode(' ', $file->getFirstErrors()));
        $baseFile->refresh();
        $this->assertSame(File::class, $baseFile->object_model);
        $this->assertEquals($file->id, $baseFile->object_id);
        $this->assertEquals(0, $baseFile->show_in_stream);
    }

    public function testAnExistingFileIsRenamedForANameUsedInTheLevel()
    {
        $this->addFile($this->spaceA, 'created.docx');

        $file = (new FolderContentService($this->spaceA))->addExistingFile($this->createBaseFile('created.docx'));

        $this->assertSame('created(1).docx', $file->getTitle());
    }

    public function testAFileThatBelongsToARecordIsNotFiled()
    {
        $baseFile = $this->createBaseFile('attached.docx');
        $baseFile->setPolymorphicRelation(Post::findOne(1));
        $baseFile->save(false);

        $this->assertNull((new FolderContentService($this->spaceA))->addExistingFile($baseFile));
    }

    public function testAFileOfAnotherUserIsNotFiled()
    {
        $baseFile = $this->createBaseFile('foreign.docx');
        $this->becomeUser('User2');

        $this->assertNull((new FolderContentService($this->spaceA))->addExistingFile($baseFile));
    }

    private function createBaseFile(string $name): FileContent
    {
        $baseFile = new FileContent(['file_name' => $name, 'mime_type' => 'application/octet-stream', 'size' => 4]);
        $baseFile->newFileContent = 'test';
        $this->assertTrue($baseFile->save(), implode(' ', $baseFile->getFirstErrors()));

        return $baseFile;
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
