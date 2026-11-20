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
use humhub\modules\cfiles\services\ItemVisibilityService;
use humhub\modules\content\models\Content;
use humhub\modules\space\models\Space;
use tests\codeception\_support\HumHubDbTestCase;
use Yii;

/**
 * A change of a folder's visibility reaches everything inside it
 * ({@see ItemVisibilityService}) — but nothing becomes more visible than the folder holding it.
 */
class ItemVisibilityTest extends HumHubDbTestCase
{
    private Space $space;

    public function _before()
    {
        parent::_before();
        $this->becomeUser('Admin');
        $this->space = Space::findOne(1);
    }

    public function testMakingAFolderPublicMakesItsContentPublicAllTheWayDown()
    {
        $folder = $this->folder('Shared', null, Content::VISIBILITY_PRIVATE);
        $sub = $this->folder('Sub', $folder);
        $file = $this->file('plan.txt', $sub);

        $this->changeVisibility($folder, Content::VISIBILITY_PUBLIC);

        $this->assertTrue(Folder::findOne($folder->id)->content->isPublic());
        $this->assertTrue(Folder::findOne($sub->id)->content->isPublic());
        $this->assertTrue(File::findOne($file->id)->content->isPublic());
    }

    public function testAFolderInsideAPrivateFolderCannotTurnItsContentPublic()
    {
        $outer = $this->folder('Outer', null, Content::VISIBILITY_PRIVATE);
        $inner = $this->folder('Inner', $outer);
        $file = $this->file('secret.txt', $inner);

        $this->changeVisibility($inner, Content::VISIBILITY_PUBLIC);

        $this->assertTrue(Folder::findOne($inner->id)->content->isPrivate());
        $this->assertTrue(File::findOne($file->id)->content->isPrivate());
    }

    private function changeVisibility(Folder $folder, int $visibility): void
    {
        $folder = Folder::findOne($folder->id);
        $folder->visibility = $visibility;
        $this->assertTrue($folder->save(), implode(' ', $folder->getFirstErrors()));
    }

    private function folder(string $title, ?Folder $parent = null, ?int $visibility = null): Folder
    {
        $folder = (new FolderContentService($this->space, $parent))->newFolder($title, '');
        if ($visibility !== null) {
            $folder->visibility = $visibility;
        }
        $this->assertTrue($folder->save(), implode(' ', $folder->getFirstErrors()));

        return $folder;
    }

    private function file(string $name, Folder $parent): File
    {
        $path = Yii::getAlias('@runtime') . '/' . uniqid() . '-' . $name;
        file_put_contents($path, 'test');

        $file = (new FolderContentService($this->space, $parent))->addFileFromPath($name, $path);
        $this->assertFalse($file->hasErrors(), implode(' ', $file->getFirstErrors()));

        return $file;
    }
}
