<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2026 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\cfiles\tests\codeception\unit;

use humhub\modules\cfiles\Events;
use humhub\modules\cfiles\models\File;
use humhub\modules\cfiles\services\FolderContentService;
use humhub\modules\content\models\Content;
use humhub\modules\file\models\File as BaseFile;
use humhub\modules\post\models\Post;
use humhub\modules\space\models\Space;
use tests\codeception\_support\HumHubDbTestCase;
use Yii;
use yii\base\Event;

/**
 * New content stored to a platform file (an editor saving a document) marks the cfiles file
 * holding it as changed ({@see Events::onAfterNewStoredFile()}) — and no other.
 */
class StoredFileEventTest extends HumHubDbTestCase
{
    public function _before()
    {
        parent::_before();
        $this->becomeUser('Admin');
    }

    public function testTheCfilesFileHoldingThePlatformFileIsMarkedAsChanged()
    {
        $file = $this->file('doc.txt');
        $this->age($file);

        Events::onAfterNewStoredFile(new Event(['sender' => BaseFile::findOne($file->baseFile->id)]));

        $this->assertNotSame('2020-01-01 00:00:00', Content::findOne($file->content->id)->updated_at);
    }

    public function testAPlatformFileOfAnotherRecordLeavesTheCfilesFileWithTheSameIdAlone()
    {
        $file = $this->file('doc.txt');
        $this->age($file);

        // A post attachment whose record id happens to be the cfiles file's id.
        $attachment = new BaseFile([
            'file_name' => 'attachment.txt',
            'object_model' => Post::class,
            'object_id' => $file->id,
            'updated_by' => 2,
            'updated_at' => '2026-11-20 10:00:00',
        ]);

        Events::onAfterNewStoredFile(new Event(['sender' => $attachment]));

        $this->assertSame('2020-01-01 00:00:00', Content::findOne($file->content->id)->updated_at);
    }

    private function age(File $file): void
    {
        Content::updateAll(['updated_at' => '2020-01-01 00:00:00'], ['id' => $file->content->id]);
    }

    private function file(string $name): File
    {
        $path = Yii::getAlias('@runtime') . '/' . uniqid() . '-' . $name;
        file_put_contents($path, 'test');

        $file = (new FolderContentService(Space::findOne(1)))->addFileFromPath($name, $path);
        $this->assertFalse($file->hasErrors(), implode(' ', $file->getFirstErrors()));

        return $file;
    }
}
