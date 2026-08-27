<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2017 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\cfiles\tests\codeception\unit;

use humhub\modules\cfiles\models\Folder;
use humhub\modules\cfiles\services\FolderContentService;
use humhub\modules\space\models\Space;
use tests\codeception\_support\HumHubDbTestCase;

/**
 * The folder model's own rules.
 *
 * There is no root folder anymore — the top level of a container is `parent_folder_id IS
 * NULL` — so what is left to test here is what a folder record itself enforces.
 */
class FolderTest extends HumHubDbTestCase
{
    private Space $space;

    public function _before()
    {
        parent::_before();
        $this->becomeUser('Admin');
        $this->space = Space::findOne(1);
    }

    public function testAFolderIsCreatedAtTheTopLevelWithoutAParent()
    {
        $folder = (new FolderContentService($this->space))->newFolder('Projects', '');

        $this->assertTrue($folder->save(), implode(' ', $folder->getFirstErrors()));
        $this->assertNull($folder->parent_folder_id);
    }

    public function testASubfolderKeepsItsParent()
    {
        $parent = $this->addFolder('Projects');
        $child = (new FolderContentService($this->space, $parent))->newFolder('Drafts', '');

        $this->assertTrue($child->save(), implode(' ', $child->getFirstErrors()));
        $this->assertEquals($parent->id, $child->parent_folder_id);
    }

    public function testTwoFoldersOfTheSameNameCannotShareALevel()
    {
        $this->addFolder('Projects');

        $duplicate = (new FolderContentService($this->space))->newFolder('Projects', '');

        $this->assertFalse($duplicate->save());
        $this->assertArrayHasKey('title', $duplicate->getErrors());
    }

    public function testTheSameNameIsFineOnDifferentLevels()
    {
        $parent = $this->addFolder('Projects');
        $child = (new FolderContentService($this->space, $parent))->newFolder('Projects', '');

        $this->assertTrue($child->save(), implode(' ', $child->getFirstErrors()));
    }

    /**
     * A folder that is its own ancestor would make the tree unwalkable — every recursive
     * operation (visibility, delete, the breadcrumb) would spin.
     */
    public function testAFolderCannotBecomeItsOwnAncestor()
    {
        $parent = $this->addFolder('Projects');
        $child = $this->addFolder('Drafts', $parent);

        $parent->parent_folder_id = $child->id;

        $this->assertFalse($parent->save());
        $this->assertArrayHasKey('parent_folder_id', $parent->getErrors());
    }

    public function testDeletingAFolderTakesItsSubfoldersWithIt()
    {
        $parent = $this->addFolder('Projects');
        $child = $this->addFolder('Drafts', $parent);

        $this->assertTrue((bool)$parent->delete());
        $this->assertNull(Folder::findOne(['id' => $child->id]));
    }

    private function addFolder(string $title, ?Folder $parent = null): Folder
    {
        $folder = (new FolderContentService($this->space, $parent))->newFolder($title, '');

        $this->assertTrue($folder->save(), implode(' ', $folder->getFirstErrors()));

        return $folder;
    }
}
