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
use humhub\modules\content\components\ContentActiveRecord;
use humhub\modules\content\models\Content;
use humhub\modules\space\models\Space;
use humhub\modules\user\models\User;
use RuntimeException;
use tests\codeception\_support\HumHubDbTestCase;
use Yii;
use yii\base\Event;
use yii\db\BaseActiveRecord;
use yii\log\Logger;

/**
 * Writes that reach beyond the item: the folders above it are marked as changed, with one
 * statement rather than a save per level; a change that cascades into a folder (its visibility,
 * its deletion) is applied completely or not at all.
 */
class FolderTreeWritesTest extends HumHubDbTestCase
{
    private Space $space;

    public function _before()
    {
        parent::_before();
        $this->becomeUser('Admin');
        $this->space = Space::findOne(1);
        if (!$this->space->moduleManager->isEnabled('cfiles')) {
            $this->space->moduleManager->enable('cfiles');
        }
    }

    public function testAChangeInsideMarksEveryFolderAboveAsChanged()
    {
        $a = $this->folder('A');
        $b = $this->folder('B', $a);
        $c = $this->folder('C', $b);
        Content::updateAll(['updated_at' => '2020-01-01 00:00:00', 'updated_by' => null], ['object_model' => Folder::class, 'object_id' => [$a->id, $b->id, $c->id]]);

        $this->file('note.txt', $c);

        foreach ([$a, $b, $c] as $folder) {
            $content = Content::findOne(['object_model' => Folder::class, 'object_id' => $folder->id]);
            $this->assertNotSame('2020-01-01 00:00:00', $content->updated_at, $folder->title);
            $this->assertSame((int)User::findOne(['username' => 'Admin'])->id, (int)$content->updated_by, $folder->title);
        }
    }

    public function testAChangeInADeepFolderCostsOneSmallQueryPerLevelMore()
    {
        $shallow = $this->folder('Alone');
        $deep = $this->folder('One');
        foreach (['Two', 'Three', 'Four', 'Five'] as $title) {
            $deep = $this->folder($title, $deep);
        }

        $atTheTop = $this->queriesOf(fn() => $this->file(uniqid() . '.txt', $shallow));
        $fourBelow = $this->queriesOf(fn() => $this->file(uniqid() . '.txt', $deep));

        $this->assertLessThanOrEqual($atTheTop + 4, $fourBelow, 'At the top: ' . $atTheTop . ', four levels below: ' . $fourBelow);
    }

    public function testAVisibilityChangeThatFailsHalfwayChangesNothing()
    {
        $folder = $this->folder('Shared');
        $this->file('a.txt', $folder);
        $this->file('b.txt', $folder);
        $this->assertSame([Content::VISIBILITY_PUBLIC], $this->visibilitiesIn($folder));

        $updates = 0;
        $failSecond = static function (Event $event) use (&$updates) {
            if ($event->sender->object_model === File::class && ++$updates === 2) {
                throw new RuntimeException('The disk is full.');
            }
        };
        Event::on(Content::class, BaseActiveRecord::EVENT_BEFORE_UPDATE, $failSecond);
        try {
            $folder->visibility = Content::VISIBILITY_PRIVATE;
            $folder->save();
            $this->fail('The cascade should have failed.');
        } catch (RuntimeException) {
        } finally {
            Event::off(Content::class, BaseActiveRecord::EVENT_BEFORE_UPDATE, $failSecond);
        }

        $this->assertSame([Content::VISIBILITY_PUBLIC], $this->visibilitiesIn($folder));
    }

    public function testADeletionThatFailsHalfwayDeletesNothing()
    {
        $folder = $this->folder('Old');
        $this->file('a.txt', $folder);
        $this->file('b.txt', $folder);

        $deletions = 0;
        $failSecond = static function () use (&$deletions) {
            if (++$deletions === 2) {
                throw new RuntimeException('The disk is full.');
            }
        };
        Event::on(File::class, ContentActiveRecord::EVENT_BEFORE_SOFT_DELETE, $failSecond);
        try {
            Folder::findOne($folder->id)->delete();
            $this->fail('The cascade should have failed.');
        } catch (RuntimeException) {
        } finally {
            Event::off(File::class, ContentActiveRecord::EVENT_BEFORE_SOFT_DELETE, $failSecond);
        }

        $states = Content::find()
            ->select('state')
            ->where(['or', ['object_model' => Folder::class, 'object_id' => $folder->id], ['object_model' => File::class, 'object_id' => $this->fileIdsIn($folder)]])
            ->column();
        $this->assertCount(3, $states);
        $this->assertSame([Content::STATE_PUBLISHED], array_values(array_unique(array_map('intval', $states))));
    }

    /**
     * @return int[] the distinct visibilities of the folder and what it holds
     */
    private function visibilitiesIn(Folder $folder): array
    {
        $visibilities = Content::find()
            ->select('visibility')
            ->where(['or', ['object_model' => Folder::class, 'object_id' => $folder->id], ['object_model' => File::class, 'object_id' => $this->fileIdsIn($folder)]])
            ->column();

        return array_values(array_unique(array_map('intval', $visibilities)));
    }

    private function fileIdsIn(Folder $folder): array
    {
        return File::find()->select('id')->where(['parent_folder_id' => $folder->id])->column();
    }

    private function folder(string $title, ?Folder $parent = null): Folder
    {
        $folder = (new FolderContentService($this->space, $parent))->newFolder($title, '');
        $folder->visibility = Content::VISIBILITY_PUBLIC;
        $this->assertTrue($folder->save(), implode(' ', $folder->getFirstErrors()));

        return $folder;
    }

    private function file(string $name, Folder $folder): File
    {
        $path = Yii::getAlias('@runtime') . '/' . uniqid() . '-' . $name;
        file_put_contents($path, 'test');
        $file = (new FolderContentService($this->space, $folder))->addFileFromPath($name, $path);
        unlink($path);
        $this->assertFalse($file->hasErrors(), implode(' ', $file->getFirstErrors()));

        return $file;
    }

    private function queriesOf(callable $call): int
    {
        $logger = Yii::getLogger();
        Yii::setLogger(new Logger(['flushInterval' => 0]));
        try {
            $call();
            return count(Yii::getLogger()->getProfiling(['yii\db\Command::query', 'yii\db\Command::execute']));
        } finally {
            Yii::setLogger($logger);
        }
    }
}
