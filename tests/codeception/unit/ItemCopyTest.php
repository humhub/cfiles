<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2026 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\cfiles\tests\codeception\unit;

use humhub\modules\cfiles\controllers\api\ItemController;
use humhub\modules\cfiles\models\File;
use humhub\modules\cfiles\models\Folder;
use humhub\modules\cfiles\services\FolderContentService;
use humhub\modules\cfiles\services\ItemCopyService;
use humhub\modules\content\models\Content;
use humhub\modules\space\models\Space;
use humhub\modules\topic\models\Topic;
use tests\codeception\_support\HumHubDbTestCase;
use Yii;

/**
 * Copying files and folders ({@see ItemCopyService}, `POST api/v2/cfiles/items/copy`): a copy
 * is new content of the caller — its own stored file, the visibility a new item at the target
 * gets, a free name —, a folder is copied with everything inside it the caller may read.
 */
class ItemCopyTest extends HumHubDbTestCase
{
    private Space $space;

    public function _before()
    {
        parent::_before();
        $this->becomeUser('Admin');
        Yii::$app->response->statusCode = 200;

        $this->space = Space::findOne(1);
        if (!$this->space->moduleManager->isEnabled('cfiles')) {
            $this->space->moduleManager->enable('cfiles');
        }
    }

    public function testAFileIsCopiedWithItsOwnStoredContent()
    {
        $target = $this->folder('Target');
        $file = $this->file('plan.txt', 'the plan', null, 'The description');

        $copy = ItemCopyService::copyInto($this->space, $target, $file);

        $this->assertInstanceOf(File::class, $copy);
        $copy = File::findOne($copy->id);
        $this->assertNotSame((int)$file->id, (int)$copy->id);
        $this->assertSame((int)$target->id, (int)$copy->parent_folder_id);
        $this->assertSame('plan.txt', $copy->getTitle());
        $this->assertSame('The description', $copy->description);
        $this->assertNotSame($file->baseFile->guid, $copy->baseFile->guid);
        $this->assertSame('the plan', $copy->baseFile->store->getContent());
        $this->assertSame((int)$file->baseFile->size, (int)$copy->baseFile->size);

        // The source is untouched.
        $this->assertNull(File::findOne($file->id)->parent_folder_id);
        $this->assertSame('the plan', File::findOne($file->id)->baseFile->store->getContent());
    }

    public function testACopyToTheSameLevelGetsAnIndexedName()
    {
        $file = $this->file('plan.txt');
        $folder = $this->folder('Reports');

        $this->assertSame('plan(1).txt', ItemCopyService::copyInto($this->space, null, $file)->getTitle());
        $this->assertSame('Reports(1)', ItemCopyService::copyInto($this->space, null, $folder)->getTitle());
    }

    public function testACopyIsTheCallersNewContentWithTheTargetsVisibility()
    {
        $private = $this->folder('Private', null, Content::VISIBILITY_PRIVATE);
        // Added by a member, copied by the admin.
        $this->becomeUser('User2');
        $file = $this->file('theirs.txt');
        $this->becomeUser('Admin');

        $copy = File::findOne(ItemCopyService::copyInto($this->space, $private, $file)->id);

        $this->assertSame(1, (int)$copy->content->created_by);
        $this->assertTrue($copy->content->isPrivate());
        $this->assertTrue($copy->content->getStateService()->isPublished());
    }

    public function testATopicOfTheItemIsKept()
    {
        $file = $this->file('plan.txt');
        $topic = new Topic($this->space);
        $topic->name = 'Budget';
        $this->assertTrue($topic->save());
        Topic::attach($file->content, [$topic]);

        $copy = ItemCopyService::copyInto($this->space, $this->folder('Target'), File::findOne($file->id));

        $this->assertSame(['Budget'], array_map(fn($t) => $t->name, Topic::findByContent(File::findOne($copy->id)->content)->all()));
    }

    public function testAFolderIsCopiedWithEverythingInside()
    {
        $folder = $this->folder('Brand');
        $sub = $this->folder('Logos', $folder);
        $this->file('logo.svg', '<svg/>', $sub);
        $this->file('guide.pdf', 'guide', $folder);
        $target = $this->folder('Archive');

        $copy = ItemCopyService::copyInto($this->space, $target, $folder);

        $this->assertInstanceOf(Folder::class, $copy);
        $level = new FolderContentService($this->space, Folder::findOne($copy->id));
        $this->assertSame(['Logos'], array_map(fn($f) => $f->title, $level->subFolders()));
        $this->assertSame(['guide.pdf'], array_map(fn($f) => $f->getTitle(), $level->subFiles()));
        $logos = new FolderContentService($this->space, $level->subFolders()[0]);
        $this->assertSame('<svg/>', $logos->subFiles()[0]->baseFile->store->getContent());

        // The original tree is where it was.
        $this->assertSame(['logo.svg'], array_map(fn($f) => $f->getTitle(), (new FolderContentService($this->space, $sub))->subFiles()));
    }

    public function testWhatIsInTheTrashIsNotCopied()
    {
        $folder = $this->folder('Brand');
        $this->assertTrue((bool)$this->file('old.txt', 'x', $folder)->content->getStateService()->delete());

        $copy = ItemCopyService::copyInto($this->space, null, Folder::findOne($folder->id));

        $this->assertSame([], (new FolderContentService($this->space, Folder::findOne($copy->id)))->subFiles());
    }

    public function testAFolderIsNotCopiedIntoItself()
    {
        $folder = $this->folder('Brand');
        $sub = $this->folder('Logos', $folder);

        $this->assertNull(ItemCopyService::copyInto($this->space, $folder, $folder));
        $this->assertNull(ItemCopyService::copyInto($this->space, $sub, Folder::findOne($folder->id)));
        $this->assertNotEmpty(Folder::findOne($folder->id)->getErrors() + $folder->getErrors());
        $this->assertSame(1, (int)Folder::find()->where(['title' => 'Logos'])->count());
    }

    public function testTheEndpointCopiesPerItemAndAnswersTheCopies()
    {
        $target = $this->folder('Target');
        $file = $this->file('plan.txt');

        Yii::$app->request->setBodyParams([
            'containerId' => $this->space->contentcontainer_id,
            'items' => [['type' => 'file', 'id' => $file->id], ['type' => 'file', 'id' => 999999]],
            'parentFolderId' => $target->id,
        ]);
        $answer = (new ItemController('item', Yii::$app->getModule('cfiles')))->actionCopy();

        $this->assertSame(200, Yii::$app->response->statusCode);
        $this->assertCount(1, $answer['results']);
        $this->assertSame('file', $answer['results'][0]['type']);
        $this->assertNotSame((int)$file->id, $answer['results'][0]['id']);
        $this->assertSame((int)$target->id, (int)File::findOne($answer['results'][0]['id'])->parent_folder_id);
        $this->assertSame(999999, $answer['errors'][0]['id']);
    }

    public function testAMemberMayCopyWhatTheyMayRead()
    {
        // Added by the admin; a member may not move it, but may copy it.
        $file = $this->file('theirs.txt');
        $this->becomeUser('User2');

        $copy = ItemCopyService::copyInto($this->space, null, File::findOne($file->id));

        $this->assertInstanceOf(File::class, $copy);
        $this->assertSame(3, (int)File::findOne($copy->id)->content->created_by);
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

    private function file(string $name, string $content = 'test', ?Folder $parent = null, string $description = ''): File
    {
        $path = Yii::getAlias('@runtime') . '/' . uniqid() . '-' . $name;
        file_put_contents($path, $content);

        $file = (new FolderContentService($this->space, $parent))->addFileFromPath($name, $path);
        $this->assertFalse($file->hasErrors(), implode(' ', $file->getFirstErrors()));

        if ($description !== '') {
            $file->description = $description;
            $this->assertTrue($file->save());
        }

        return $file;
    }
}
