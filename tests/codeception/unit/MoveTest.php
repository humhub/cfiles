<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2017 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 *
 */

use humhub\modules\cfiles\services\FolderTreeService;
use humhub\modules\cfiles\services\FolderContentService;
use humhub\modules\cfiles\services\ItemMoveService;
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
        $root = FolderTreeService::initRoot($space1);

        // Create a file within root
        $fileA = new File($space1);
        $fileA->setUploadedFile(new UploadedFile([
            'name' => 'fileA.txt',
            'size' => 1024,
            'type' => 'text/plain',
        ]));

        $this->assertTrue(ItemMoveService::moveInto($root, $fileA));

        $this->assertTrue($fileA->save());


        // Check children of root
        $children = (new FolderContentService($root))->children();
        $this->assertEquals(count($children), 1);
        $this->assertEquals($fileA->id, $children[0]->id);
        $this->assertEquals('fileA.txt', $children[0]->getTitle());
        $this->assertEquals($root->id, $fileA->getParentFolder()->one()->id);

        $folderA = (new FolderContentService($root))->newFolder('FolderA', 'FolderA description');
        $this->assertTrue($folderA->save());

        $folders = $root->folders;
        $this->assertEquals(1, count($folders));
        $this->assertEquals('FolderA', $folders[0]->getTitle());

        // Move fileA from root to folderA
        ItemMoveService::moveInto($folderA, $fileA);
        $children = (new FolderContentService($root))->children();
        $this->assertEquals(1, count($children));
        $this->assertEquals('FolderA', $children[0]->getTitle());
        $this->assertEquals('FolderA', $children[0]->getTitle());
        $this->assertEquals($folderA->id, $fileA->getParentFolder()->one()->id);
    }

    public function testSimpleFolderMove()
    {
        $this->becomeUser('Admin');
        $space1 = Space::findOne(1);
        $root = FolderTreeService::initRoot($space1);

        $folderA = (new FolderContentService($root))->newFolder('FolderA', 'FolderA description');
        $this->assertTrue($folderA->save());

        $folderB = (new FolderContentService($root))->newFolder('FolderB', 'FolderB description');
        $this->assertTrue($folderB->save());

        // Create a file within root
        $fileA = new File($space1);
        $fileA->setUploadedFile(new UploadedFile([
            'name' => 'fileA.txt',
            'size' => 1024,
            'type' => 'text/plain',
        ]));

        ItemMoveService::moveInto($folderB, $fileA);

        // prevent move to own content
        $this->assertFalse(ItemMoveService::moveInto($folderA, $folderA));
        $this->assertTrue(ItemMoveService::moveInto($folderA, $folderB));

        $searchFolderB = (new FolderContentService($folderA))->findFolder('FolderB');
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
        $root = FolderTreeService::initRoot($space1);

        # /A
        $folderA = (new FolderContentService($root))->newFolder('FolderA', 'FolderA description');
        $this->assertTrue($folderA->save());

        # /A/B
        $folderBinA = (new FolderContentService($folderA))->newFolder('FolderB', 'FolderB description');
        $this->assertTrue($folderBinA->save());

        # /A/B/C
        $folderCinBinA = (new FolderContentService($folderBinA))->newFolder('FolderC', 'FolderC description');
        $this->assertTrue($folderCinBinA->save());

        # /A/B/C/fileA.txt
        $originalFileA = new File($space1);
        $originalFileA->setUploadedFile(new UploadedFile([
            'name' => 'fileA.txt',
            'size' => 1024,
            'type' => 'text/plain',
        ]));
        $this->assertTrue(ItemMoveService::moveInto($folderCinBinA, $originalFileA));

        # /B
        $folderB = (new FolderContentService($root))->newFolder('FolderB', 'FolderB description');
        $this->assertTrue($folderB->save());

        # /B/C
        $folderCinB = (new FolderContentService($folderB))->newFolder('FolderC', 'FolderC description');
        $this->assertTrue($folderCinB->save());

        # /B/C/fileA.txt
        $otherFileA = new File($space1);
        $otherFileA->setUploadedFile(new UploadedFile([
            'name' => 'fileA.txt',
            'size' => 1024,
            'type' => 'text/plain',
        ]));

        $this->assertTrue(ItemMoveService::moveInto($folderCinB, $otherFileA));

        # /B/C/other.txt
        $fileB = new File($space1);
        $fileB->setUploadedFile(new UploadedFile([
            'name' => 'fileB.txt',
            'size' => 1024,
            'type' => 'text/plain',
        ]));

        $this->assertTrue(ItemMoveService::moveInto($folderCinB, $fileB));

        // Move roots B to folder A
        $this->assertTrue(ItemMoveService::moveInto($folderA, $folderB));

        $childrenC = (new FolderContentService($folderCinBinA))->children();

        $this->assertEquals(3, count($childrenC));

        $searchFileA = (new FolderContentService($folderCinBinA))->findFile('fileA.txt');
        $this->assertNotNull($searchFileA);
        $this->assertEquals($originalFileA->id, $searchFileA->id);

        $searchOtherFileA = (new FolderContentService($folderCinBinA))->findFile('fileA(1).txt');
        $this->assertNotNull($searchOtherFileA);
        $this->assertEquals($otherFileA->id, $searchOtherFileA->id);

        $searchOtherFileB = (new FolderContentService($folderCinBinA))->findFile('fileB.txt');
        $this->assertNotNull($searchOtherFileB);
        $this->assertEquals($fileB->id, $searchOtherFileB->id);

        // Check if old (now empty) B folder is removed
        $this->assertNull(Folder::find()->readable()->andWhere(['cfiles_folder.id' => $folderB->id])->one());
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
        $root = FolderTreeService::initRoot($space1);

        # /A
        $folderA = (new FolderContentService($root))->newFolder('FolderA', 'FolderA description');
        $this->assertTrue($folderA->save());

        # /A/B
        $folderBinA = (new FolderContentService($folderA))->newFolder('FolderB', 'FolderB description');
        $this->assertTrue($folderBinA->save());

        # /B
        $folderB = (new FolderContentService($root))->newFolder('FolderB', 'FolderB description');
        $this->assertTrue($folderB->save());

        # /B/fileA.txt
        $fileA = new File($space1);
        $fileA->setUploadedFile(new UploadedFile([
            'name' => 'fileA.txt',
            'size' => 1024,
            'type' => 'text/plain',
        ]));
        $this->assertTrue(ItemMoveService::moveInto($folderB, $fileA));

        # /B/X
        $folderX = (new FolderContentService($folderB))->newFolder('FolderX', 'FolderB description');
        $this->assertTrue($folderX->save());

        //Invalidate FolderX
        $folderX->title = null;
        $folderX->update(false, ['title']);

        // Move of some files failed
        $this->assertFalse(ItemMoveService::moveInto($folderA, $folderB));

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
