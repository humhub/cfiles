<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2026 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\cfiles\tests\codeception\unit;

use humhub\helpers\Html;
use humhub\modules\cfiles\services\FolderContentService;
use humhub\modules\cfiles\services\ItemMoveService;
use humhub\modules\cfiles\jobs\SendFileUploadNotification;
use humhub\modules\cfiles\libs\FileUploadBatch;
use humhub\modules\cfiles\models\File;
use humhub\modules\cfiles\models\Folder;
use humhub\modules\cfiles\Module;
use humhub\modules\cfiles\notifications\FilesUploaded;
use humhub\modules\content\models\Content;
use humhub\modules\content\notifications\ContentCreated;
use humhub\modules\notification\models\Notification;
use humhub\modules\queue\driver\Instant;
use humhub\modules\queue\driver\MySQL;
use humhub\modules\space\models\Space;
use humhub\modules\user\models\User;
use tests\codeception\_support\HumHubDbTestCase;
use Yii;
use yii\web\UploadedFile;

/**
 * Uploading a set of files must not create one notification (and one e-mail) per file, but a
 * single one for the whole upload.
 *
 * Most tests upload to the container's top level, where there is no folder record: a batch
 * there is keyed by the container, and announced about one of its files.
 *
 * @see FileUploadBatch
 */
class FileUploadBatchTest extends HumHubDbTestCase
{
    /**
     * Admin follows space 2 with notification settings, so public uploads of User2 have exactly
     * one recipient. See the user_follow fixture.
     */
    private const SPACE_ID = 2;
    private const UPLOADER = 'User2';

    private Space $space;

    private int $uploaderId;

    protected function setUp(): void
    {
        parent::setUp();

        Yii::$app->db->createCommand()->truncateTable('queue')->execute();

        // The folders are created while nothing is executed, so that their own content created
        // notifications cannot interfere with the assertions below.
        $this->useDelayingQueue();

        // New items take the visibility of the level they land in, at the top level the space's
        // default. Admin only follows the space, so the uploads have to be public to reach him.
        // The space fixture is reloaded before every test, which resets this again.
        $this->space = Space::findOne(self::SPACE_ID);
        $this->space->updateAttributes(['default_content_visibility' => Content::VISIBILITY_PUBLIC]);

        $this->uploaderId = $this->becomeUser(self::UPLOADER)->id;
    }

    /**
     * The default test driver is Instant, which runs jobs right away and ignores delay(). The
     * MySQL driver stores them instead, which is what a real installation uses and what lets a
     * whole upload be collected before the announcement job runs.
     */
    private function useDelayingQueue(): void
    {
        Yii::$app->set('queue', ['class' => MySQL::class]);
    }

    private function useInstantQueue(): void
    {
        Yii::$app->set('queue', ['class' => Instant::class]);
    }

    /**
     * @param Folder|null $folder null = the container's top level
     * @return File[]
     */
    private function upload(int $count, ?Folder $folder = null): array
    {
        $files = [];

        for ($i = 1; $i <= $count; $i++) {
            // The same entry point the upload action uses
            $file = (new FolderContentService($this->space, $folder))->addUploadedFile(new UploadedFile([
                'name' => 'batch-test-' . $i . '-' . uniqid('', true) . '.txt',
                'size' => 1024,
                'type' => 'text/plain',
            ]));

            $this->assertFalse($file->hasErrors(), 'File ' . $i . ' could not be saved');
            $this->assertFalse($file->isNewRecord, 'File ' . $i . ' was not persisted');

            $files[] = $file;
        }

        return $files;
    }

    /**
     * @param Folder|null $folder null = the container's top level
     */
    private function batch(?Folder $folder = null, ?int $userId = null): FileUploadBatch
    {
        return FileUploadBatch::load($this->space->contentcontainer_id, $folder?->id, $userId ?? $this->uploaderId);
    }

    private function newFolder(string $title): Folder
    {
        $folder = (new FolderContentService($this->space))->newFolder($title, $title . ' folder');
        $this->assertTrue($folder->save(), implode(' ', $folder->getFirstErrors()));

        return $folder;
    }

    private function countQueuedBatchJobs(): int
    {
        return (int)Yii::$app->db
            ->createCommand('SELECT COUNT(*) FROM queue WHERE job LIKE :job', [':job' => '%SendFileUploadNotification%'])
            ->queryScalar();
    }

    private function countNotifications(): int
    {
        return (int)Notification::find()->where(['class' => FilesUploaded::class])->count();
    }

    /**
     * Lets the quiet period expire without waiting for it.
     */
    private function expireQuietPeriod(?Folder $folder = null): void
    {
        $batch = $this->batch($folder);
        $batch->firstAt = time() - 100000;
        $batch->lastAt = time() - 100000;
        $batch->save();

        $this->assertSame(0, $this->batch($folder)->getRemainingDelay());
    }

    private function runBatchJob(?Folder $folder = null, int $attempt = 0): void
    {
        (new SendFileUploadNotification([
            'containerId' => $this->space->contentcontainer_id,
            'folderId' => $folder?->id,
            'userId' => $this->uploaderId,
            'attempt' => $attempt,
        ]))->run();
    }

    /**
     * Lets the quiet period of the top-level batch expire and runs its job, as a queue worker would.
     */
    private function announce(): void
    {
        $this->expireQuietPeriod();
        $this->useInstantQueue();
        $this->runBatchJob();
    }

    private function announcement(): Notification
    {
        $notification = Notification::find()->where(['class' => FilesUploaded::class])->one();
        $this->assertNotNull($notification, 'The upload was not announced');

        return $notification;
    }

    private function cfilesModule(): Module
    {
        return Yii::$app->getModule('cfiles');
    }

    public function testUploadedFileDoesNotNotifyOnItsOwn()
    {
        $files = $this->upload(3);

        foreach ($files as $file) {
            $this->assertHasNoNotification(ContentCreated::class, $file);
        }

        $this->assertSame(0, $this->countNotifications(), 'Nothing may be announced before the quiet period expired');
    }

    public function testUploadsAreCollectedInASingleBatch()
    {
        $this->upload(5);

        $batch = $this->batch();
        $this->assertFalse($batch->isEmpty());
        $this->assertSame(5, $batch->count);
        $this->assertSame($this->space->contentcontainer_id, $batch->containerId);
        $this->assertNull($batch->folderId, 'The top level has no folder');
        $this->assertSame($this->uploaderId, $batch->userId);
    }

    public function testOnlyTheFirstUploadSchedulesAJob()
    {
        $this->upload(5);

        $this->assertSame(1, $this->countQueuedBatchJobs(), '5 uploads must not queue 5 jobs');

        $delay = (int)Yii::$app->db
            ->createCommand('SELECT delay FROM queue WHERE job LIKE :job', [':job' => '%SendFileUploadNotification%'])
            ->queryScalar();
        $this->assertSame(600, $delay, 'The job must be delayed by the configured 10 minutes');
    }

    public function testWholeUploadIsAnnouncedByOneNotificationAndOneMail()
    {
        $this->upload(5);
        $this->expireQuietPeriod();

        // From here on the notification targets have to run, as they would in a queue worker
        $this->useInstantQueue();
        $this->runBatchJob();

        $this->assertSame(1, $this->countNotifications(), '5 uploaded files must result in exactly one notification');
        $this->assertMailSent(1);

        $notification = Notification::find()->where(['class' => FilesUploaded::class])->one();
        $this->assertSame('{"fileCount":5}', $notification->payload);
        $this->assertSame(User::findOne(['username' => self::UPLOADER])->id, $notification->originator_user_id);

        $this->assertTrue($this->batch()->isEmpty(), 'The batch must be closed after being announced');
    }

    /**
     * The top level has no folder record to announce, so the notification is about a file of
     * the upload, and leads to the top level of the container's files.
     */
    public function testTopLevelUploadIsAnnouncedAboutItsFilesLevel()
    {
        $files = $this->upload(3);
        $this->expireQuietPeriod();
        $this->useInstantQueue();
        $this->runBatchJob();

        $this->assertSame(1, $this->countNotifications());
        $this->assertMailSent(1);

        $notification = $this->announcement();
        $this->assertSame(User::findOne(['username' => 'Admin'])->id, $notification->user_id, 'The follower of the space is the recipient');
        $this->assertSame(File::class, $notification->source_class);
        $this->assertSame($files[2]->id, $notification->source_pk, 'The newest file of the upload');

        $rendered = $notification->getBaseModel();
        $rendered->getViewParams();
        $uploader = Html::encode(User::findOne(['id' => $this->uploaderId])->displayName);
        $this->assertSame(
            '<strong>' . $uploader . '</strong> added 3 files to the files of Space ' . Html::encode($this->space->displayName) . '.',
            $rendered->html(),
        );
        $this->assertSame(
            User::findOne(['id' => $this->uploaderId])->displayName . ' added 3 files to the files of Space ' . $this->space->displayName,
            $rendered->getMailSubject(),
        );
        $this->assertSame($this->space->createUrl('/cfiles/browse/index', [], true), $rendered->getUrl());
    }

    public function testFilesDeletedBeforeTheAnnouncementAreNotCounted()
    {
        $files = $this->upload(3);
        $this->assertTrue((bool)$files[2]->delete());

        $this->announce();

        $this->assertSame(1, $this->countNotifications());
        $notification = $this->announcement();
        $this->assertSame('{"fileCount":2}', $notification->payload);
        $this->assertSame($files[1]->id, $notification->source_pk, 'The deleted file cannot be the source');
    }

    public function testAnUploadWithoutAnyFileLeftIsNotAnnounced()
    {
        foreach ($this->upload(2) as $file) {
            $this->assertTrue((bool)$file->delete());
        }

        $this->announce();

        $this->assertSame(0, $this->countNotifications());
        $this->assertMailSent(0);
        $this->assertTrue($this->batch()->isEmpty(), 'The batch is closed anyway');
    }

    /**
     * The announcement is about a public file where there is one, so that a follower who is
     * not a member, and could not see a private one, still hears of the upload.
     */
    public function testATopLevelUploadIsAnnouncedAboutAPublicFile()
    {
        $files = $this->upload(3);
        $files[2]->content->updateAttributes(['visibility' => Content::VISIBILITY_PRIVATE]);

        $this->announce();

        $this->assertSame(1, $this->countNotifications());
        $this->assertMailSent(1);
        $notification = $this->announcement();
        $this->assertSame($files[1]->id, $notification->source_pk, 'The newest public file');
        $this->assertSame('{"fileCount":3}', $notification->payload);
    }

    public function testFilesMovedAwayBeforeTheAnnouncementAreNotCounted()
    {
        $folder = $this->newFolder('Elsewhere');
        $files = $this->upload(3);

        // The uploader may not move files in this space, its owner may
        $this->becomeUser('User1');
        $moved = File::findOne(['id' => $files[0]->id]);
        $this->assertTrue(ItemMoveService::moveInto($this->space, $folder, $moved), implode(' ', $moved->getFirstErrors()));

        $this->announce();

        $notification = $this->announcement();
        $this->assertSame('{"fileCount":2}', $notification->payload);
        $this->assertNotSame($files[0]->id, $notification->source_pk);
    }

    public function testUploadIntoAFolderIsAnnouncedAboutTheFolder()
    {
        $folder = $this->newFolder('Reports');

        $this->upload(2, $folder);
        $this->expireQuietPeriod($folder);
        $this->useInstantQueue();
        $this->runBatchJob($folder);

        $this->assertSame(1, $this->countNotifications());
        $this->assertMailSent(1);

        $notification = $this->announcement();
        $this->assertSame(User::findOne(['username' => 'Admin'])->id, $notification->user_id, 'The follower of the space is the recipient');
        $this->assertSame(Folder::class, $notification->source_class);
        $this->assertSame($folder->id, $notification->source_pk);

        $rendered = $notification->getBaseModel();
        $rendered->getViewParams();
        $this->assertStringContainsString('2 files', $rendered->html());
        $this->assertStringContainsString('"Reports"', $rendered->html());
        $this->assertSame($folder->getUrl(true), $rendered->getUrl());
        $this->assertTrue($this->batch($folder)->isEmpty());
    }

    public function testAnnouncedCountIsKeptWhenTheNotificationIsRenderedAgain()
    {
        $this->upload(4);
        $this->expireQuietPeriod();
        $this->useInstantQueue();
        $this->runBatchJob();

        // The count only survives in the stored payload, the notification list re-renders from it
        $notification = Notification::find()->where(['class' => FilesUploaded::class])->one();
        $rendered = $notification->getBaseModel();
        $rendered->getViewParams();

        $this->assertSame(4, $rendered->getFileCount());
        $this->assertStringContainsString('4 files', $rendered->html());
    }

    public function testASingleUploadIsAnnouncedInSingular()
    {
        $this->upload(1);
        $this->expireQuietPeriod();
        $this->useInstantQueue();
        $this->runBatchJob();

        $notification = Notification::find()->where(['class' => FilesUploaded::class])->one();
        $rendered = $notification->getBaseModel();
        $rendered->getViewParams();

        $html = $rendered->html();
        $this->assertStringContainsString('a file', $html);
        $this->assertStringNotContainsString('1 files', $html);
    }

    public function testEveryUploadRestartsTheQuietPeriod()
    {
        $this->upload(1);

        $batch = $this->batch();
        $batch->lastAt = time() - 550;
        $batch->save();
        $this->assertLessThanOrEqual(50, $this->batch()->getRemainingDelay());

        $this->upload(1);

        $this->assertGreaterThan(500, $this->batch()->getRemainingDelay(), 'A further upload must restart the quiet period');
    }

    public function testOngoingUploadsCannotPostponeTheNotificationForever()
    {
        $this->upload(1);

        $batch = $this->batch();
        // Still being uploaded into, but running since longer than the hard limit
        $batch->firstAt = time() - (FileUploadBatch::getDelay() * FileUploadBatch::MAX_POSTPONE_FACTOR) - 10;
        $batch->lastAt = time();
        $batch->save();

        $this->assertSame(0, $this->batch()->getRemainingDelay());
    }

    public function testJobRequeuesItselfWhileTheBatchIsNotDue()
    {
        $this->upload(2);
        $this->assertSame(1, $this->countQueuedBatchJobs());

        $this->runBatchJob();

        $this->assertSame(2, $this->countQueuedBatchJobs(), 'A job running too early must queue a new one');
        $this->assertSame(0, $this->countNotifications());
        $this->assertFalse($this->batch()->isEmpty(), 'The batch must stay open');
    }

    public function testJobDoesNothingWithoutAnOpenBatch()
    {
        $this->assertTrue($this->batch()->isEmpty());

        $this->useInstantQueue();
        $this->runBatchJob();

        $this->assertSame(0, $this->countNotifications());
        $this->assertMailSent(0);
    }

    public function testQuietPeriodIsTakenFromTheModuleConfiguration()
    {
        $this->assertSame(10, $this->cfilesModule()->uploadNotificationDelay);
        $this->assertSame(600, FileUploadBatch::getDelay());

        $this->cfilesModule()->uploadNotificationDelay = 30;
        $this->assertSame(1800, FileUploadBatch::getDelay());

        $this->cfilesModule()->uploadNotificationDelay = 0;
        $this->assertSame(0, FileUploadBatch::getDelay());

        $this->cfilesModule()->uploadNotificationDelay = 10;
    }

    public function testBatchesOfDifferentFoldersAreIndependent()
    {
        $other = $this->newFolder('Other');

        $this->upload(3);
        $this->upload(2, $other);

        $this->assertSame(3, $this->batch()->count);
        $this->assertSame(2, $this->batch($other)->count);
        $this->assertSame(2, $this->countQueuedBatchJobs(), 'Each folder gets its own job');
    }

    public function testTopLevelBatchesOfDifferentContainersAreIndependent()
    {
        $this->upload(2);

        $mySpace = $this->space;
        $this->space = Space::findOne(3);
        $this->assertTrue($this->batch()->isEmpty(), 'Another container\'s top level is another batch');
        $this->space = $mySpace;

        $this->assertSame(2, $this->batch()->count);
    }

    public function testBatchesOfDifferentUsersAreIndependent()
    {
        $this->upload(3);

        $otherId = $this->becomeUser('Admin')->id;
        $this->upload(1);

        $this->assertSame(3, $this->batch(null, $this->uploaderId)->count);
        $this->assertSame(1, $this->batch(null, $otherId)->count);
    }
}
