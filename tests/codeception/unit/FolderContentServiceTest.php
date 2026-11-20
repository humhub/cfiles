<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\cfiles\tests\codeception\unit;

use humhub\modules\cfiles\controllers\api\FileController;
use humhub\modules\cfiles\models\File;
use humhub\modules\cfiles\models\FileSystemItem;
use humhub\modules\cfiles\models\Folder;
use humhub\modules\cfiles\services\FolderContentService;
use humhub\modules\cfiles\services\ItemMoveService;
use humhub\modules\file\models\FileContent;
use humhub\modules\post\models\Post;
use humhub\modules\space\models\Space;
use tests\codeception\_support\HumHubDbTestCase;
use Yii;
use yii\web\UploadedFile;

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

    // --- uploading over an existing name ---------------------------------------------

    public function testUploadingOverOwnFileToReplaceItStoresANewVersion()
    {
        $mine = $this->upload($this->spaceA, 'notes.txt');

        $again = $this->upload($this->spaceA, 'notes.txt', true);

        $this->assertSame((int)$mine->id, (int)$again->id);
    }

    public function testUploadingOverATakenNameWithoutReplacingKeepsBoth()
    {
        $mine = $this->upload($this->spaceA, 'notes.txt');

        $again = $this->upload($this->spaceA, 'notes.txt');

        $this->assertNotSame((int)$mine->id, (int)$again->id);
        $this->assertSame('notes(1).txt', $again->getTitle());
    }

    public function testUploadingOverAFileOfAnotherUserStoresTheUploadUnderANewName()
    {
        $theirs = $this->upload($this->spaceA, 'report.txt');
        // A member: may add files, may not change those of others.
        $this->becomeUser('User2');

        $mine = $this->upload($this->spaceA, 'report.txt', true);

        $this->assertNotSame((int)$theirs->id, (int)$mine->id);
        $this->assertSame('report(1).txt', $mine->getTitle());
        $this->assertSame('report.txt', File::findOne($theirs->id)->getTitle());
    }

    // --- names of trashed items --------------------------------------------------------

    public function testATrashedFolderDoesNotBlockItsName()
    {
        $this->trash($this->addFolder($this->spaceA, 'Projects'));

        $again = (new FolderContentService($this->spaceA))->newFolder('Projects', '');

        $this->assertTrue($again->save(), implode(' ', $again->getFirstErrors()));
    }

    public function testATrashedFileDoesNotBlockItsName()
    {
        $this->trash($this->addFile($this->spaceA, 'report.txt'));

        $this->assertSame('report.txt', $this->addFile($this->spaceA, 'report.txt')->getTitle());
    }

    public function testAFolderMovedNextToATrashedNamesakeKeepsItsContent()
    {
        $this->trash($this->addFolder($this->spaceA, 'Reports'));
        $archive = $this->addFolder($this->spaceA, 'Archive');
        $reports = (new FolderContentService($this->spaceA, $archive))->newFolder('Reports', '');
        $this->assertTrue($reports->save());
        $inside = $this->addFile($this->spaceA, 'q1.txt', $reports);

        $this->assertTrue(ItemMoveService::moveInto($this->spaceA, null, $reports));
        $this->assertTrue($reports->save(), implode(' ', $reports->getFirstErrors()));

        $moved = Folder::findOne($reports->id);
        $this->assertTrue($moved->content->getStateService()->isPublished());
        $this->assertNull($moved->parent_folder_id);
        $this->assertSame((int)$moved->id, (int)File::findOne($inside->id)->parent_folder_id);
    }

    public function testARestoredFileIsRenamedWhenItsNameWasTakenMeanwhile()
    {
        $first = $this->addFile($this->spaceA, 'report.txt');
        $this->trash($first);
        $this->addFile($this->spaceA, 'report.txt');

        $restored = File::findOne($first->id);
        $this->assertTrue($restored->content->getStateService()->publish());

        $this->assertSame('report(1).txt', File::findOne($first->id)->getTitle());
    }

    public function testARestoredFolderIsRenamedWhenItsNameWasTakenMeanwhile()
    {
        $first = $this->addFolder($this->spaceA, 'Projects');
        $this->trash($first);
        $this->addFolder($this->spaceA, 'Projects');

        $restored = Folder::findOne($first->id);
        $this->assertTrue($restored->content->getStateService()->publish());

        $this->assertSame('Projects(1)', Folder::findOne($first->id)->title);
    }

    // --- a file and a folder never share a name ---------------------------------------

    public function testAFileIsRenamedForTheNameOfAFolderAtItsLevel()
    {
        $this->addFolder($this->spaceA, 'Reports');

        $this->assertSame('Reports(1)', $this->addFile($this->spaceA, 'Reports')->getTitle());
    }

    public function testAFolderCannotTakeTheNameOfAFileAtItsLevel()
    {
        $this->addFile($this->spaceA, 'notes');

        $folder = (new FolderContentService($this->spaceA))->newFolder('notes', '');

        $this->assertFalse($folder->save());
        $this->assertArrayHasKey('title', $folder->getErrors());
    }

    public function testAFileCannotBeRenamedToTheNameOfAFolderAtItsLevel()
    {
        $this->addFolder($this->spaceA, 'Reports');
        $file = $this->addFile($this->spaceA, 'draft.txt');

        Yii::$app->request->setBodyParams(['title' => 'Reports']);
        $answer = (new FileController('file', Yii::$app->getModule('cfiles')))->actionUpdate($file->id);

        $this->assertArrayHasKey('title', $answer['errors'] ?? []);
        $this->assertSame('draft.txt', File::findOne($file->id)->getTitle());
    }

    public function testAFolderMovedNextToAFileOfItsNameIsRenamed()
    {
        $this->addFile($this->spaceA, 'Reports');
        $archive = $this->addFolder($this->spaceA, 'Archive');
        $reports = (new FolderContentService($this->spaceA, $archive))->newFolder('Reports', '');
        $this->assertTrue($reports->save());

        $this->assertTrue(ItemMoveService::moveInto($this->spaceA, null, $reports));
        $this->assertTrue($reports->save(), implode(' ', $reports->getFirstErrors()));

        $this->assertSame('Reports(1)', Folder::findOne($reports->id)->title);
    }

    private function createBaseFile(string $name): FileContent
    {
        $baseFile = new FileContent(['file_name' => $name, 'mime_type' => 'application/octet-stream', 'size' => 4]);
        $baseFile->newFileContent = 'test';
        $this->assertTrue($baseFile->save(), implode(' ', $baseFile->getFirstErrors()));

        return $baseFile;
    }

    private function addFile(Space $space, string $name, ?Folder $folder = null): File
    {
        $path = Yii::getAlias('@runtime') . '/' . uniqid() . '-' . $name;
        file_put_contents($path, 'test');

        $file = (new FolderContentService($space, $folder))->addFileFromPath($name, $path);

        $this->assertFalse($file->hasErrors(), implode(' ', $file->getFirstErrors()));

        return $file;
    }

    private function addFolder(Space $space, string $title): Folder
    {
        $folder = (new FolderContentService($space))->newFolder($title, '');

        $this->assertTrue($folder->save(), implode(' ', $folder->getFirstErrors()));

        return $folder;
    }

    /**
     * What the browser's upload does: a form upload of `$name` into the top level.
     */
    private function upload(Space $space, string $name, bool $replace = false): File
    {
        $path = Yii::getAlias('@runtime') . '/' . uniqid() . '-' . $name;
        file_put_contents($path, 'test');

        $file = (new FolderContentService($space))->addUploadedFile(new UploadedFile([
            'name' => $name,
            'tempName' => $path,
            'type' => 'text/plain',
            'size' => 4,
        ]), $replace);

        $this->assertFalse($file->hasErrors(), implode(' ', $file->getFirstErrors()));

        return $file;
    }

    private function trash(FileSystemItem $item): void
    {
        $this->assertTrue((bool)$item->content->getStateService()->delete());
    }
}
