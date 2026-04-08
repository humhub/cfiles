<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2017 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 *
 */

namespace humhub\modules\cfiles\tests\codeception\unit;

use humhub\modules\cfiles\Events;
use humhub\modules\cfiles\models\Folder;
use humhub\modules\content\models\Content;
use humhub\modules\space\models\Space;
use tests\codeception\_support\HumHubDbTestCase;
use Yii;
use yii\db\AfterSaveEvent;

/**
 * Created by PhpStorm.
 * User: buddha
 * Date: 16.07.2017
 * Time: 20:52
 */
class FolderTest extends HumHubDbTestCase
{
    public function testCreateRoot()
    {
        $this->becomeUser('Admin');
        $space = Space::findOne(1);
        $rootFolder = Folder::initRoot($space);

        $this->assertTrue($rootFolder instanceof Folder);
        $this->assertEquals($space->created_by, $rootFolder->content->created_by);
        // Prevent double root initialization
        $this->assertFalse(Folder::initRoot($space));
    }

    public function testEnsureRootFolderOwner()
    {
        $this->becomeUser('Admin');
        $space = Space::findOne(2);
        $rootFolder = Folder::initRoot($space);

        $rootFolder->content->created_by = 1;
        $this->assertTrue($rootFolder->content->save(false, ['created_by']));

        $this->assertTrue(Folder::ensureRootFolderOwner($rootFolder, $space));

        $rootFolder->refresh();
        $rootFolder->content->refresh();

        $this->assertEquals($space->created_by, $rootFolder->content->created_by);
        $this->assertFalse(Folder::ensureRootFolderOwner($rootFolder, $space));
    }

    public function testEnsureRootFolderStructure()
    {
        $this->becomeUser('Admin');
        $space = Space::findOne(2);
        $rootFolder = Folder::initRoot($space);
        $postedFilesFolder = Folder::initPostedFilesFolder($space);
        $otherFolder = $rootFolder->newFolder('Other', 'Other folder');
        $this->assertTrue($otherFolder->save());

        $rootFolder->content->created_by = 1;
        $this->assertTrue($rootFolder->content->save(false, ['created_by']));

        $postedFilesFolder->content->created_by = 1;
        $this->assertTrue($postedFilesFolder->content->save(false, ['created_by']));

        $postedFilesFolder->parent_folder_id = $otherFolder->id;
        $this->assertTrue($postedFilesFolder->save(false, ['parent_folder_id']));

        Folder::ensureRootFolderStructure($space);

        $rootFolder->refresh();
        $rootFolder->content->refresh();
        $postedFilesFolder->refresh();
        $postedFilesFolder->content->refresh();

        $this->assertEquals($space->created_by, $rootFolder->content->created_by);
        $this->assertEquals($space->created_by, $postedFilesFolder->content->created_by);
        $this->assertEquals($rootFolder->id, $postedFilesFolder->parent_folder_id);
    }
}
