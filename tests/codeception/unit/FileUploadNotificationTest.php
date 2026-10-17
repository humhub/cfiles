<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2026 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\cfiles\tests\codeception\unit;

use humhub\modules\cfiles\models\File;
use humhub\modules\cfiles\services\FolderContentService;
use humhub\modules\content\models\Content;
use humhub\modules\content\notifications\ContentCreatedNotification;
use humhub\modules\notification\models\Notification;
use humhub\modules\space\models\Space;
use humhub\modules\user\models\User;
use tests\codeception\_support\HumHubDbTestCase;
use yii\web\UploadedFile;

/**
 * Every uploaded file dispatches the core new-content notification; the core groups those of
 * one uploader in one container into a single entry, so an upload of several files reaches a
 * follower as one notification.
 */
class FileUploadNotificationTest extends HumHubDbTestCase
{
    /**
     * Admin follows space 2 with notification settings, so public uploads of User2 reach him.
     * See the user_follow fixture.
     */
    private const SPACE_ID = 2;

    private Space $space;

    protected function setUp(): void
    {
        parent::setUp();

        // New items take the space's default visibility at the top level; Admin only follows
        // the space, so the uploads have to be public to reach him.
        $this->space = Space::findOne(self::SPACE_ID);
        $this->space->updateAttributes(['default_content_visibility' => Content::VISIBILITY_PUBLIC]);
    }

    /**
     * @return File[]
     */
    private function upload(int $count): array
    {
        $files = [];
        for ($i = 1; $i <= $count; $i++) {
            // The same entry point the upload action uses
            $file = (new FolderContentService($this->space))->addUploadedFile(new UploadedFile([
                'name' => 'upload-test-' . $i . '-' . uniqid('', true) . '.txt',
                'size' => 1024,
                'type' => 'text/plain',
            ]));

            $this->assertFalse($file->hasErrors(), 'File ' . $i . ' could not be saved');
            $files[] = $file;
        }

        return $files;
    }

    public function testUploadOfSeveralFilesIsOneGroupedNotification()
    {
        $uploader = $this->becomeUser('User2');
        $follower = User::findOne(['username' => 'Admin']);

        $files = $this->upload(3);

        foreach ($files as $file) {
            $this->assertHasNotification(ContentCreatedNotification::class, $file, $uploader->id, 'Every file is announced');
        }

        $notifications = Notification::find()
            ->where(['class' => ContentCreatedNotification::class, 'user_id' => $follower->id, 'originator_id' => $uploader->id])
            ->all();
        $this->assertCount(3, $notifications);

        $groupingKeys = array_unique(array_map(static fn(Notification $n) => (int)$n->grouping_key, $notifications));
        $this->assertCount(1, $groupingKeys, 'The files of one upload form one entry');
    }

    public function testUploaderIsNotNotified()
    {
        $uploader = $this->becomeUser('User2');

        $this->upload(2);

        $this->assertSame(0, (int)Notification::find()
            ->where(['class' => ContentCreatedNotification::class, 'user_id' => $uploader->id])
            ->count());
    }
}
