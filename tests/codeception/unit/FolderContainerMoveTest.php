<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2026 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\cfiles\tests\codeception\unit;

use humhub\modules\cfiles\models\File;
use humhub\modules\cfiles\models\Folder;
use humhub\modules\cfiles\services\FolderContentService;
use humhub\modules\space\models\Space;
use tests\codeception\_support\HumHubDbTestCase;
use Yii;

/**
 * The platform's "Move content" on a folder takes everything inside it along — or, when one
 * item may not follow, moves nothing at all. Leaving an item behind would leave it in a folder
 * of another space, where neither space's browser reaches it.
 */
class FolderContainerMoveTest extends HumHubDbTestCase
{
    private Space $source;

    public function _before()
    {
        parent::_before();
        $this->becomeUser('Admin');

        // Space 1: Admin and User2 are members. Space 3: Admin, User1 and User2 are.
        // Space 5: Admin and User1 are, User2 is not.
        $this->source = Space::findOne(1);
        foreach ([1, 3, 5] as $id) {
            $space = Space::findOne($id);
            if (!$space->moduleManager->isEnabled('cfiles')) {
                $space->moduleManager->enable('cfiles');
            }
        }
    }

    public function testAFolderTakesEverythingInsideItAlong()
    {
        $folder = $this->folder('Projects');
        $sub = $this->folder('Drafts', $folder);
        $file = $this->file('plan.txt', $sub);

        $this->assertTrue($folder->content->move(Space::findOne(3)));

        $this->assertSame(3, (int)$this->spaceOf(Folder::findOne($sub->id))->id);
        $this->assertSame(3, (int)$this->spaceOf(File::findOne($file->id))->id);
        $this->assertSame((int)$sub->id, (int)File::findOne($file->id)->parent_folder_id);
    }

    public function testAFolderStaysWhenAnItemInsideItCannotFollow()
    {
        $folder = $this->folder('Shared');
        // User2 is no member of space 5, so their file may not be moved there - the folder,
        // Admin's, may.
        $this->becomeUser('User2');
        $theirs = $this->file('notes.txt', $folder);
        $this->becomeUser('Admin');

        $moved = Folder::findOne($folder->id)->content->move(Space::findOne(5));

        $this->assertNotTrue($moved);
        $this->assertSame(1, (int)$this->spaceOf(Folder::findOne($folder->id))->id);
        $this->assertSame(1, (int)$this->spaceOf(File::findOne($theirs->id))->id);
    }

    private function spaceOf($item): Space
    {
        return $item->content->container;
    }

    private function folder(string $title, ?Folder $parent = null): Folder
    {
        $folder = (new FolderContentService($this->source, $parent))->newFolder($title, '');
        $this->assertTrue($folder->save(), implode(' ', $folder->getFirstErrors()));

        return $folder;
    }

    private function file(string $name, Folder $parent): File
    {
        $path = Yii::getAlias('@runtime') . '/' . uniqid() . '-' . $name;
        file_put_contents($path, 'test');

        $file = (new FolderContentService($this->source, $parent))->addFileFromPath($name, $path);
        $this->assertFalse($file->hasErrors(), implode(' ', $file->getFirstErrors()));

        return $file;
    }
}
