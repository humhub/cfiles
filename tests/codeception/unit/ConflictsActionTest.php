<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2026 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\cfiles\tests\codeception\unit;

use humhub\modules\cfiles\controllers\api\FolderController;
use humhub\modules\cfiles\models\File;
use humhub\modules\cfiles\models\Folder;
use humhub\modules\cfiles\services\FolderContentService;
use humhub\modules\space\models\Space;
use tests\codeception\_support\HumHubDbTestCase;
use Yii;
use yii\web\ForbiddenHttpException;

/**
 * `GET api/v2/cfiles/<containerId>/conflicts` ({@see FolderController::actionConflicts()}) —
 * which names of an upload are taken in a level, asked before uploading so the user can decide
 * whether to replace, keep both or skip.
 */
class ConflictsActionTest extends HumHubDbTestCase
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

    public function testTheTakenNamesOfTheLevelAreAnswered()
    {
        $mine = $this->file('mine.txt');
        $folder = $this->folder('Reports');

        $answer = $this->conflicts(['mine.txt', 'Reports', 'free.txt']);

        $this->assertSame([
            ['name' => 'mine.txt', 'type' => 'file', 'id' => (int)$mine->id, 'canReplace' => true],
            ['name' => 'Reports', 'type' => 'folder', 'id' => (int)$folder->id, 'canReplace' => false],
        ], $answer['conflicts']);
    }

    public function testAFileOfAnotherUserCannotBeReplaced()
    {
        $this->file('theirs.txt');
        // A member: may add files, may not change those of others.
        $this->becomeUser('User2');

        $answer = $this->conflicts(['theirs.txt']);

        $this->assertFalse($answer['conflicts'][0]['canReplace']);
    }

    public function testTheNamesAreLookedUpInTheLevelAsked()
    {
        $folder = $this->folder('Inside');
        $this->file('inner.txt', $folder);

        $this->assertSame([], $this->conflicts(['inner.txt'])['conflicts']);
        $this->assertCount(1, $this->conflicts(['inner.txt'], $folder)['conflicts']);
    }

    public function testATrashedItemTakesNoName()
    {
        $this->assertTrue((bool)$this->file('old.txt')->content->getStateService()->delete());

        $this->assertSame([], $this->conflicts(['old.txt'])['conflicts']);
    }

    public function testNamesThatAreNoListAreRefused()
    {
        $answer = $this->conflicts('mine.txt');

        $this->assertSame(422, Yii::$app->response->statusCode);
        $this->assertArrayHasKey('names', $answer['errors']);
    }

    public function testAUserWhoMayNotAddFilesIsRefused()
    {
        // No member of space 1.
        $this->becomeUser('User1');

        $this->expectException(ForbiddenHttpException::class);
        $this->conflicts(['mine.txt']);
    }

    private function conflicts($names, ?Folder $parent = null): array
    {
        Yii::$app->request->setQueryParams(['names' => $names, 'parent' => $parent?->id]);

        return (new FolderController('folder', Yii::$app->getModule('cfiles')))
            ->actionConflicts($this->space->contentcontainer_id);
    }

    private function folder(string $title): Folder
    {
        $folder = (new FolderContentService($this->space))->newFolder($title, '');
        $this->assertTrue($folder->save(), implode(' ', $folder->getFirstErrors()));

        return $folder;
    }

    private function file(string $name, ?Folder $parent = null): File
    {
        $path = Yii::getAlias('@runtime') . '/' . uniqid() . '-' . $name;
        file_put_contents($path, 'test');

        $file = (new FolderContentService($this->space, $parent))->addFileFromPath($name, $path);
        $this->assertFalse($file->hasErrors(), implode(' ', $file->getFirstErrors()));

        return $file;
    }
}
