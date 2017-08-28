<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2017 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 *
 */

use humhub\modules\cfiles\models\File;
use humhub\modules\cfiles\models\Folder;
use humhub\modules\space\models\Space;
use tests\codeception\_support\HumHubDbTestCase;
use yii\web\UploadedFile;

/**
 * Created by PhpStorm.
 * User: buddha
 * Date: 20.08.2017
 * Time: 19:10
 */

class MoveTest extends HumHubDbTestCase
{
    public function testSimpleFileMove()
    {
        $this->becomeUser('Admin');
        $space1 = Space::findOne(1);
        $root = Folder::initRoot($space1);

        // Create a file within root
        $fileA = new File($space1);
        $fileA->setUploadedFile(new UploadedFile([
            'name' => 'fileA.txt',
            'size' => 1024,
            'type' => 'text/plain'
        ]));

        $this->assertTrue($root->moveItem($fileA));

        $this->assertTrue($fileA->save());


        // Check children of root
        $children = $root->getChildren();
        $this->assertEquals(count($children), 1);
        $this->assertEquals($fileA->id, $children[0]->id );
        $this->assertEquals('fileA.txt', $children[0]->getTitle());
        $this->assertEquals($root->id, $fileA->getParentFolder()->one()->id);

        $folderA = $root->newFolder('FolderA', 'FolderA description');
        $this->assertTrue($folderA->save());

        $folders = $root->folders;
        $this->assertEquals(1, count($folders));
        $this->assertEquals('FolderA', $folders[0]->getTitle());

        // Move fileA from root to folderA
        $folderA->moveItem($fileA);
        $children = $root->getChildren();
        $this->assertEquals(1, count($children));
        $this->assertEquals('FolderA', $children[0]->getTitle());
        $this->assertEquals('FolderA', $children[0]->getTitle());
        $this->assertEquals($folderA->id, $fileA->getParentFolder()->one()->id);
    }

    public function testSimpleFolderMove()
    {
        $this->becomeUser('Admin');
        $space1 = Space::findOne(1);
        $root = Folder::initRoot($space1);

        $folderA = $root->newFolder('FolderA', 'FolderA description');
        $this->assertTrue($folderA->save());

        $folderB = $root->newFolder('FolderB', 'FolderB description');
        $this->assertTrue($folderB->save());

        // Create a file within root
        $fileA = new File($space1);
        $fileA->setUploadedFile(new UploadedFile([
            'name' => 'fileA.txt',
            'size' => 1024,
            'type' => 'text/plain'
        ]));

        $folderB->moveItem($fileA);

        // prevent move to own content
        $this->assertFalse($folderA->moveItem($folderA));
        $this->assertTrue($folderA->moveItem($folderB));

        $searchFolderB = $folderA->findFolderByName('FolderB');
        $this->assertNotNull($searchFolderB);
        $this->assertEquals($folderB->id, $searchFolderB->id);
        $this->assertEquals($folderA->id, $searchFolderB->parentFolder->id);
    }


    /**
     * FolderA
     *     FolderB
     *         FolderC
     *             fileA.txt
     * FolderB
     *     FolderC
     *        fileA.txt
     *        fileB.txt
     *
     * --> Move FolderB from root to FolderA
     *
     * FolderA
     *     FolderB
     *         FolderC
     *             fileA.txt
     *             fileA(1).txt
     *             fileB.txt
     */
    public function testMoveNestedFolders()
    {

        $this->becomeUser('Admin');
        $space1 = Space::findOne(1);
        $root = Folder::initRoot($space1);

        # /A
        $folderA = $root->newFolder('FolderA', 'FolderA description');
        $this->assertTrue($folderA->save());

        # /A/B
        $folderBinA = $folderA->newFolder('FolderB', 'FolderB description');
        $this->assertTrue($folderBinA->save());

        # /A/B/C
        $folderCinBinA = $folderBinA->newFolder('FolderC', 'FolderC description');
        $this->assertTrue($folderCinBinA->save());

        # /A/B/C/fileA.txt
        $originalFileA = new File($space1);
        $originalFileA->setUploadedFile(new UploadedFile([
            'name' => 'fileA.txt',
            'size' => 1024,
            'type' => 'text/plain'
        ]));
        $this->assertTrue($folderCinBinA->moveItem($originalFileA));

        # /B
        $folderB = $root->newFolder('FolderB', 'FolderB description');
        $this->assertTrue($folderB->save());

        # /B/C
        $folderCinB = $folderB->newFolder('FolderC', 'FolderC description');
        $this->assertTrue($folderCinB->save());

        # /B/C/fileA.txt
        $otherFileA = new File($space1);
        $otherFileA->setUploadedFile(new UploadedFile([
            'name' => 'fileA.txt',
            'size' => 1024,
            'type' => 'text/plain'
        ]));

        $this->assertTrue($folderCinB->moveItem($otherFileA));

        # /B/C/other.txt
        $fileB = new File($space1);
        $fileB->setUploadedFile(new UploadedFile([
            'name' => 'fileB.txt',
            'size' => 1024,
            'type' => 'text/plain'
        ]));

        $this->assertTrue($folderCinB->moveItem($fileB));

        // Move roots B to folder A
        $this->assertTrue($folderA->moveItem($folderB));

        $childrenC = $folderCinBinA->getChildren();

        $this->assertEquals(3, count($childrenC));

        $searchFileA = $folderCinBinA->findFileByName('fileA.txt');
        $this->assertNotNull($searchFileA);
        $this->assertEquals($originalFileA->id, $searchFileA->id);

        $searchOtherFileA = $folderCinBinA->findFileByName('fileA(1).txt');
        $this->assertNotNull($searchOtherFileA);
        $this->assertEquals($otherFileA->id, $searchOtherFileA->id);

        $searchOtherFileB = $folderCinBinA->findFileByName('fileB.txt');
        $this->assertNotNull($searchOtherFileB);
        $this->assertEquals($fileB->id, $searchOtherFileB->id);

        // Check if old (now empty) B folder is removed
        $this->assertNull(Folder::findOne($folderB->id));
    }

    /**
     * FolderA
     *     FolderB
     * FolderB
     *     FolderX --> triggers error when moved
     *     fileA.txt
     *
     * --> Move FolderB from root to FolderA
     *
     * FolderA
     *     FolderB
     *         fileA.txt
     * FolderB
     *     FolderX
     */
    public function testMoveItemError()
    {
        $this->becomeUser('Admin');
        $space1 = Space::findOne(1);
        $root = Folder::initRoot($space1);

        # /A
        $folderA = $root->newFolder('FolderA', 'FolderA description');
        $this->assertTrue($folderA->save());

        # /A/B
        $folderBinA = $folderA->newFolder('FolderB', 'FolderB description');
        $this->assertTrue($folderBinA->save());

        # /B
        $folderB = $root->newFolder('FolderB', 'FolderB description');
        $this->assertTrue($folderB->save());

        # /B/fileA.txt
        $fileA = new File($space1);
        $fileA->setUploadedFile(new UploadedFile([
            'name' => 'fileA.txt',
            'size' => 1024,
            'type' => 'text/plain'
        ]));
        $this->assertTrue($folderB->moveItem($fileA));

        # /B/X
        $folderX = $folderB->newFolder('FolderX', 'FolderB description');
        $this->assertTrue($folderX->save());

        //Invalidate FolderX
        $folderX->title = null;
        $folderX->update(false, ['title']);

        // Move of some files failed
        $this->assertFalse($folderA->moveItem($folderB));

        // Original B was not totally moved so its not deleted
        $this->assertNotNull(Folder::findOne($folderB->id));

        // Folderx was not moved
        $folderX->refresh();
        $this->assertNotNull($folderX->parent_folder_id === $folderB->id);

        // File A was successfully moved to B in A
        $fileA->refresh();
        $this->assertNotNull($fileA->parent_folder_id === $folderBinA->id);
    }

}