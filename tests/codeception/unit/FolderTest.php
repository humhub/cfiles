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
use Yii;

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

    /**
     * `delete()` of content is a soft delete: the records stay, marked deleted, until they are
     * hard deleted (from the trash, or by the content cleanup job).
     */
    public function testDeletingAFolderTakesItsSubfoldersWithIt()
    {
        $parent = $this->addFolder('Projects');
        $child = $this->addFolder('Drafts', $parent);

        $this->assertTrue((bool)$parent->delete());
        $this->assertTrue(Folder::findOne(['id' => $child->id])->content->getStateService()->isDeleted());
    }

    public function testHardDeletingAFolderTakesItsSubfoldersWithIt()
    {
        $parent = $this->addFolder('Projects');
        $child = $this->addFolder('Drafts', $parent);

        $this->assertTrue($parent->hardDelete());
        $this->assertNull(Folder::findOne(['id' => $parent->id]));
        $this->assertNull(Folder::findOne(['id' => $child->id]));
    }

    /**
     * `files` is the relation every content record has: the files attached to it. The files
     * inside a folder are its `subFiles`.
     */
    public function testTheFilesOfAFolderAreThoseAttachedToItNotThoseInsideIt()
    {
        $folder = $this->addFolder('Projects');
        $path = Yii::getAlias('@runtime') . '/' . uniqid() . '-plan.txt';
        file_put_contents($path, 'test');
        $file = (new FolderContentService($this->space, $folder))->addFileFromPath('plan.txt', $path);
        $this->assertFalse($file->hasErrors(), implode(' ', $file->getFirstErrors()));

        $folder = Folder::findOne($folder->id);

        $this->assertSame([], $folder->files);
        $this->assertSame([(int)$file->id], array_map(static fn($sub) => (int)$sub->id, $folder->subFiles));
    }

    private function addFolder(string $title, ?Folder $parent = null): Folder
    {
        $folder = (new FolderContentService($this->space, $parent))->newFolder($title, '');

        $this->assertTrue($folder->save(), implode(' ', $folder->getFirstErrors()));

        return $folder;
    }
}
