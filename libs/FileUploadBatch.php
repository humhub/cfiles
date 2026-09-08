<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2026 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\cfiles\libs;

use humhub\modules\cfiles\jobs\SendFileUploadNotification;
use humhub\modules\cfiles\models\File;
use humhub\modules\cfiles\models\Folder;
use humhub\modules\cfiles\Module;
use humhub\modules\cfiles\notifications\FilesUploaded;
use humhub\modules\user\models\User;
use Yii;

/**
 * Collects files a user uploaded into a folder but which have not been announced yet.
 *
 * Uploading a set of files used to create one content created notification (and one e-mail)
 * per file. [[File::$silentContentCreation]] suppresses those and every uploaded file is
 * counted here instead. Once the user stopped uploading into the same folder for
 * [[Module::$uploadNotificationDelay]] minutes, a single [[FilesUploaded]] notification
 * announces the whole batch.
 *
 * The batch is kept in `Yii::$app->cache`, which the web request and the queue worker share.
 * A batch lost through a cache flush simply means that upload is not announced.
 *
 * @since 0.19
 */
final class FileUploadBatch
{
    /**
     * @var int how many quiet periods ongoing uploads may postpone a batch, counted from the
     *      first uploaded file. Prevents a continuously uploading user (or a large archive
     *      import) from deferring the notification indefinitely.
     */
    public const MAX_POSTPONE_FACTOR = 6;

    private const CACHE_KEY_PREFIX = 'cfiles.fileUploadBatch.';

    /**
     * @var int uploaded files collected so far
     */
    public int $count = 0;

    /**
     * @var int timestamp of the first uploaded file
     */
    public int $firstAt = 0;

    /**
     * @var int timestamp of the most recently uploaded file
     */
    public int $lastAt = 0;

    public function __construct(
        public readonly int $folderId,
        public readonly int $userId,
    ) {
    }

    /**
     * Counts the given file into the open batch of its folder and uploader.
     *
     * The first file of a batch also schedules the delayed notification job. Every following
     * file only bumps the counter and restarts the quiet period, so a single job (which
     * re-queues itself while uploads keep coming in) is enough for the whole batch.
     */
    public static function add(File $file): void
    {
        $content = $file->content;

        if ($content === null || !$content->getStateService()->isPublished()) {
            // Not published content is announced by Content::processNewContent() once it gets published
            return;
        }

        $folderId = (int)$file->parent_folder_id;
        $userId = (int)$content->created_by;

        if ($folderId === 0 || $userId === 0) {
            return;
        }

        $batch = static::load($folderId, $userId);
        $isFirstFile = $batch->isEmpty();
        $now = time();

        $batch->count++;
        $batch->lastAt = $now;

        if ($isFirstFile) {
            $batch->firstAt = $now;
        }

        $batch->save();

        if ($isFirstFile) {
            Yii::$app->queue->delay(static::getDelay())->push(new SendFileUploadNotification([
                'folderId' => $folderId,
                'userId' => $userId,
            ]));
        }
    }

    /**
     * Returns the open batch of the given folder and uploader, or an empty one.
     */
    public static function load(int $folderId, int $userId): self
    {
        $batch = new self($folderId, $userId);
        $cached = Yii::$app->cache->get($batch->getCacheKey());

        if (is_array($cached)) {
            $batch->count = (int)($cached['count'] ?? 0);
            $batch->firstAt = (int)($cached['firstAt'] ?? 0);
            $batch->lastAt = (int)($cached['lastAt'] ?? 0);
        }

        return $batch;
    }

    public function isEmpty(): bool
    {
        return $this->count < 1;
    }

    public function save(): void
    {
        // The batch must outlive the longest possible postponing
        $duration = max(3600, static::getDelay() * (self::MAX_POSTPONE_FACTOR + 1));

        Yii::$app->cache->set($this->getCacheKey(), [
            'count' => $this->count,
            'firstAt' => $this->firstAt,
            'lastAt' => $this->lastAt,
        ], $duration);
    }

    public function forget(): void
    {
        Yii::$app->cache->delete($this->getCacheKey());
    }

    /**
     * @return int seconds left until this batch may be announced, 0 if it is due
     */
    public function getRemainingDelay(): int
    {
        $delay = static::getDelay();

        $due = min(
            // The quiet period restarts with every uploaded file...
            $this->lastAt + $delay,
            // ...but a user uploading continuously must not defer the notification forever.
            $this->firstAt + $delay * self::MAX_POSTPONE_FACTOR,
        );

        return max(0, $due - time());
    }

    /**
     * Announces this batch with a single notification and closes it.
     *
     * The batch is always dropped, even when nothing could be sent, so a broken batch cannot
     * block notifications for later uploads into the same folder.
     */
    public function notify(): void
    {
        $fileCount = $this->count;

        $this->forget();

        if ($fileCount < 1) {
            return;
        }

        $folder = Folder::findOne(['id' => $this->folderId]);
        $user = User::findOne(['id' => $this->userId]);

        if ($folder === null || $user === null) {
            return;
        }

        $content = $folder->content;

        if ($content === null || !$content->getStateService()->isPublished()) {
            return;
        }

        FilesUploaded::instance()
            ->from($user)
            ->about($folder)
            ->fileCount($fileCount)
            ->sendBulk(Yii::$app->notification->getFollowers($content));
    }

    /**
     * @return int seconds of upload inactivity before a batch is announced
     */
    public static function getDelay(): int
    {
        /** @var Module $module */
        $module = Yii::$app->getModule('cfiles');

        return max(0, $module->uploadNotificationDelay) * 60;
    }

    private function getCacheKey(): string
    {
        return self::CACHE_KEY_PREFIX . $this->folderId . '.' . $this->userId;
    }
}
