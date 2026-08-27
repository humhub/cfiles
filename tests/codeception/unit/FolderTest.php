<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2017 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 *
 */

namespace humhub\modules\cfiles\tests\codeception\unit;

use humhub\modules\cfiles\services\FolderTreeService;
use humhub\modules\cfiles\services\FolderContentService;
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
        $rootFolder = FolderTreeService::initRoot($space);

        $this->assertTrue($rootFolder instanceof Folder);
        $this->assertEquals($space->created_by, $rootFolder->content->created_by);
        // Prevent double root initialization
        $this->assertNull(FolderTreeService::initRoot($space));
    }

    public function testEnsureRootFolderOwner()
    {
        $this->becomeUser('Admin');
        $space = Space::findOne(2);
        $rootFolder = FolderTreeService::initRoot($space);

        $rootFolder->content->created_by = 1;
        $this->assertTrue($rootFolder->content->save(false, ['created_by']));

        $this->assertTrue(FolderTreeService::ensureRootOwner($rootFolder, $space));

        $rootFolder->refresh();
        $rootFolder->content->refresh();

        $this->assertEquals($space->created_by, $rootFolder->content->created_by);
        $this->assertFalse(FolderTreeService::ensureRootOwner($rootFolder, $space));
    }

    public function testEnsureRootFolderStructure()
    {
        $this->becomeUser('Admin');
        $space = Space::findOne(2);
        $rootFolder = FolderTreeService::initRoot($space);
        $otherFolder = (new FolderContentService($rootFolder))->newFolder('Other', 'Other folder');
        $this->assertTrue($otherFolder->save());

        $rootFolder->content->created_by = 1;
        $this->assertTrue($rootFolder->content->save(false, ['created_by']));

        FolderTreeService::ensureRootStructure($space);

        $rootFolder->refresh();
        $rootFolder->content->refresh();

        $this->assertEquals($space->created_by, $rootFolder->content->created_by);
        $this->assertFalse($rootFolder->isNewRecord);
    }

    public function testEnsureRootFolderStructureCreatesTheRootWhenMissing()
    {
        $this->becomeUser('Admin');
        $space = Space::findOne(2);

        $this->assertNull(FolderTreeService::getRoot($space));

        FolderTreeService::ensureRootStructure($space);

        $root = FolderTreeService::getRoot($space);
        $this->assertInstanceOf(Folder::class, $root);
        $this->assertEquals($space->created_by, $root->content->created_by);
    }

}
