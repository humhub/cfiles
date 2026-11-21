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
use humhub\modules\cfiles\services\ItemMoveService;
use humhub\modules\content\models\Content;
use humhub\modules\space\models\Space;
use humhub\modules\user\models\User;
use tests\codeception\_support\HumHubDbTestCase;
use Yii;

/**
 * What happens to a folder happens to everything inside it — also to what the user acting on
 * it cannot see: the draft of someone else, an item in the trash.
 */
class HiddenItemsTest extends HumHubDbTestCase
{
    private Space $space;

    public function _before()
    {
        parent::_before();
        $this->becomeUser('Admin');
        $this->space = Space::findOne(1);
    }

    /**
     * Nothing may stay more visible than the folder holding it: a draft would be published
     * public, an item restored from the trash would come back public.
     */
    public function testMakingAFolderPrivateReachesWhatTheChangerCannotSee()
    {
        $folder = $this->folder('Shared', null, Content::VISIBILITY_PUBLIC);
        $draft = $this->hide($this->file('draft.txt', $folder), Content::STATE_DRAFT);
        $trashed = $this->hide($this->file('trashed.txt', $folder), Content::STATE_DELETED);

        $this->changeVisibility($folder, Content::VISIBILITY_PRIVATE);

        $this->assertSame(Content::VISIBILITY_PRIVATE, $this->visibility($draft));
        $this->assertSame(Content::VISIBILITY_PRIVATE, $this->visibility($trashed));
    }

    /**
     * What the user cannot see, they cannot decide to publish.
     */
    public function testMakingAFolderPublicLeavesWhatTheChangerCannotSeeAsItIs()
    {
        $folder = $this->folder('Shared', null, Content::VISIBILITY_PRIVATE);
        $draft = $this->hide($this->file('draft.txt', $folder), Content::STATE_DRAFT);

        $this->changeVisibility($folder, Content::VISIBILITY_PUBLIC);

        $this->assertSame(Content::VISIBILITY_PRIVATE, $this->visibility($draft));
    }

    /**
     * A folder merged into one of the same name is deleted afterwards — with whatever was left
     * in it, unless everything moves.
     */
    public function testMergingAFolderTakesAlongWhatTheMoverCannotSee()
    {
        $existing = $this->folder('Docs');
        $outer = $this->folder('Outer');
        $merged = $this->folder('Docs', $outer);
        $draft = $this->hide($this->file('draft.txt', $merged), Content::STATE_DRAFT);
        $trashed = $this->hide($this->file('trashed.txt', $merged), Content::STATE_DELETED);

        $this->assertTrue(ItemMoveService::moveInto($this->space, null, Folder::findOne($merged->id)));

        foreach ([[$draft, Content::STATE_DRAFT], [$trashed, Content::STATE_DELETED]] as [$file, $state]) {
            $content = Content::findOne($file->content->id);
            $this->assertSame((int)$existing->id, (int)File::findOne($file->id)->parent_folder_id, $file->getTitle());
            $this->assertSame($state, (int)$content->state, $file->getTitle());
        }
    }

    private function changeVisibility(Folder $folder, int $visibility): void
    {
        $folder = Folder::findOne($folder->id);
        $folder->visibility = $visibility;
        $this->assertTrue($folder->save(), implode(' ', $folder->getFirstErrors()));
    }

    /**
     * Puts a file out of the Admin's sight: a draft of another user, or in the trash.
     */
    private function hide(File $file, int $state): File
    {
        Content::updateAll([
            'state' => $state,
            'created_by' => User::findOne(['username' => 'User2'])->id,
        ], ['id' => $file->content->id]);

        $this->assertNull(File::find()->readable()->andWhere(['cfiles_file.id' => $file->id])->one());

        return $file;
    }

    private function visibility(File $file): int
    {
        return (int)Content::findOne($file->content->id)->visibility;
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
