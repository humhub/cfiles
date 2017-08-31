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
 * Date: 30.08.2017
 * Time: 17:49
 */

class MigrateFromOldTest extends HumHubDbTestCase
{
    public function testMigrateStructureFromOld()
    {
        $this->becomeUser('Admin');
        $space1 = Space::findOne(1);

        $this->assertTrue($this->createFile($space1, 'f1.txt'));
        $this->assertTrue($this->createFile($space1, 'f2.txt'));
        $this->assertTrue($this->createFile($space1, 'f3.txt'));
        $this->assertTrue($this->createFile($space1, 'f4.txt'));
        $this->assertTrue($this->createFile($space1, 'f5.txt'));

        // Add one folder manually with a file
        $folder = new Folder($space1);
        $folder->title = 'fo1';
        $this->assertTrue($folder->save(false));

        $fileA = new File($space1);
        $fileA->setUploadedFile(new UploadedFile([
            'name' => 'fileA.txt',
            'size' => 1024,
            'type' => 'text/plain'
        ]));

        $folder->moveItem($fileA);

        $this->assertTrue($this->createFolder($space1, 'fo2'));
        $this->assertTrue($this->createFolder($space1, 'fo3'));
        $this->assertTrue($this->createFolder($space1, 'fo4'));
        $this->assertTrue($this->createFolder($space1, 'fo5'));

        $root = Folder::initRoot($space1);
        $root->migrateFromOldStructure();

        $children = $root->getChildren();

        $this->assertEquals(10, count($children));
        $this->assertNotNull($root->findFileByName('f1.txt'));
        $this->assertNotNull($root->findFileByName('f2.txt'));
        $this->assertNotNull($root->findFileByName('f3.txt'));
        $this->assertNotNull($root->findFileByName('f4.txt'));
        $this->assertNotNull($root->findFileByName('f5.txt'));

        $this->assertNotNull($root->findFolderByName('fo1'));
        $this->assertNotNull($root->findFolderByName('fo2'));
        $this->assertNotNull($root->findFolderByName('fo3'));
        $this->assertNotNull($root->findFolderByName('fo4'));
        $this->assertNotNull($root->findFolderByName('fo5'));

        $root->refresh();

        $this->assertNull($root->parent_folder_id);

    }

    public function createFile($space, $name)
    {
        $file = new File($space);
        $file->setUploadedFile(new UploadedFile([
            'name' => $name,
            'size' => 1024,
            'type' => 'text/plain'
        ]));

        //Save without parent_id;
        return $file->save(false);
    }

    public function createFolder($space, $title)
    {
        $folder = new Folder($space);
        $folder->title = $title;
        return $folder->save(false);
    }

}