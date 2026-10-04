<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2026 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\cfiles\tests\codeception\unit;

use humhub\modules\cfiles\jobs\SendFileUploadNotification;
use humhub\modules\cfiles\libs\FileUploadBatch;
use humhub\modules\cfiles\models\File;
use humhub\modules\cfiles\models\Folder;
use humhub\modules\cfiles\Module;
use humhub\modules\cfiles\notifications\FilesUploaded;
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
 * @see FileUploadBatch
 */
class FileUploadBatchTest extends HumHubDbTestCase
{
    /**
     * Admin follows space 2 with notification settings, so uploads of User2 have exactly one
     * recipient. See the user_follow fixture.
     */
    private const SPACE_ID = 2;
    private const UPLOADER = 'User2';

    private Folder $folder;

    private int $uploaderId;

    protected function setUp(): void
    {
        parent::setUp();

        Yii::$app->db->createCommand()->truncateTable('queue')->execute();

        // The folders are created while nothing is executed, so that their own content created
        // notifications cannot interfere with the assertions below.
        $this->useDelayingQueue();

        $space = Space::findOne(self::SPACE_ID);
        $this->folder = Folder::initRoot($space);
        $this->assertInstanceOf(Folder::class, $this->folder);

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
     * @return File[]
     */
    private function upload(int $count, ?Folder $folder = null): array
    {
        $folder ??= $this->folder;
        $files = [];

        for ($i = 1; $i <= $count; $i++) {
            // The same entry point the upload action uses
            $file = $folder->addUploadedFile(new UploadedFile([
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

    private function batch(?Folder $folder = null, ?int $userId = null): FileUploadBatch
    {
        return FileUploadBatch::load(($folder ?? $this->folder)->id, $userId ?? $this->uploaderId);
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
    private function expireQuietPeriod(): void
    {
        $batch = $this->batch();
        $batch->firstAt = time() - 100000;
        $batch->lastAt = time() - 100000;
        $batch->save();

        $this->assertSame(0, $this->batch()->getRemainingDelay());
    }

    private function runBatchJob(int $attempt = 0): void
    {
        (new SendFileUploadNotification([
            'folderId' => $this->folder->id,
            'userId' => $this->uploaderId,
            'attempt' => $attempt,
        ]))->run();
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
        $this->assertSame($this->folder->id, $batch->folderId);
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
        $other = $this->folder->newFolder('Other', 'Other folder');
        $this->assertTrue($other->save());

        $this->upload(3);
        $this->upload(2, $other);

        $this->assertSame(3, $this->batch()->count);
        $this->assertSame(2, $this->batch($other)->count);
        $this->assertSame(2, $this->countQueuedBatchJobs(), 'Each folder gets its own job');
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
