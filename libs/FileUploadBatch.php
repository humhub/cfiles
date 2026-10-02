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
use humhub\modules\content\models\Content;
use humhub\modules\user\models\User;
use Yii;

/**
 * Collects files a user uploaded into one level of a container's file tree (a folder, or the
 * top level) but which have not been announced yet.
 *
 * Uploading a set of files used to create one content created notification (and one e-mail)
 * per file. [[File::$silentContentCreation]] suppresses those and every uploaded file is
 * counted here instead. Once the user stopped uploading into the same level for
 * [[Module::$uploadNotificationDelay]] minutes, a single [[FilesUploaded]] notification
 * announces the whole batch.
 *
 * The top level has no folder record (`parent_folder_id IS NULL`), so a batch is keyed by the
 * container as well, and a top-level batch is announced about one of its files.
 *
 * Files can be deleted or moved away before the batch is announced, so the batch keeps the ids
 * of its files and only announces those that are still published at the level they were
 * uploaded into.
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

    /**
     * @var int how many file ids a batch keeps, the most recent ones. Files beyond that are
     *      still counted, they are just assumed to be still there when the batch is announced.
     */
    public const MAX_TRACKED_FILES = 100;

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

    /**
     * @var int[] ids of the files of this batch, the most recent [[MAX_TRACKED_FILES]] ones
     */
    public array $fileIds = [];

    /**
     * @param int $containerId the `contentcontainer_id` uploaded into
     * @param int|null $folderId the folder uploaded into, null = the container's top level
     */
    public function __construct(
        public readonly int $containerId,
        public readonly ?int $folderId,
        public readonly int $userId,
    ) {
    }

    /**
     * Counts the given file into the open batch of its level and uploader.
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

        $containerId = (int)$content->contentcontainer_id;
        $folderId = $file->parent_folder_id === null ? null : (int)$file->parent_folder_id;
        $userId = (int)$content->created_by;

        if ($containerId === 0 || $userId === 0) {
            return;
        }

        $batch = static::load($containerId, $folderId, $userId);
        $isFirstFile = $batch->isEmpty();
        $now = time();

        $batch->count++;
        $batch->lastAt = $now;
        $batch->fileIds[] = (int)$file->id;
        $batch->fileIds = array_slice($batch->fileIds, -self::MAX_TRACKED_FILES);

        if ($isFirstFile) {
            $batch->firstAt = $now;
        }

        $batch->save();

        if ($isFirstFile) {
            Yii::$app->queue->delay(static::getDelay())->push(new SendFileUploadNotification([
                'containerId' => $containerId,
                'folderId' => $folderId,
                'userId' => $userId,
            ]));
        }
    }

    /**
     * Returns the open batch of the given level and uploader, or an empty one.
     *
     * @param int|null $folderId null = the container's top level
     */
    public static function load(int $containerId, ?int $folderId, int $userId): self
    {
        $batch = new self($containerId, $folderId, $userId);
        $cached = Yii::$app->cache->get($batch->getCacheKey());

        if (is_array($cached)) {
            $batch->count = (int)($cached['count'] ?? 0);
            $batch->firstAt = (int)($cached['firstAt'] ?? 0);
            $batch->lastAt = (int)($cached['lastAt'] ?? 0);
            $batch->fileIds = array_map('intval', (array)($cached['fileIds'] ?? []));
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
            'fileIds' => $this->fileIds,
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
     * block notifications for later uploads into the same level.
     */
    public function notify(): void
    {
        $this->forget();

        if ($this->isEmpty()) {
            return;
        }

        $files = $this->findRemainingFiles();
        // Files beyond the tracked ones cannot be checked and are assumed to be still there
        $fileCount = $this->count - (count($this->fileIds) - count($files));

        if ($files === [] || $fileCount < 1) {
            return;
        }

        $source = $this->folderId === null ? $this->pickFile($files) : Folder::findOne(['id' => $this->folderId]);
        $user = User::findOne(['id' => $this->userId]);

        if ($source === null || $user === null) {
            return;
        }

        $content = $source->content;

        if ($content === null
            || (int)$content->contentcontainer_id !== $this->containerId
            || !$content->getStateService()->isPublished()) {
            return;
        }

        FilesUploaded::instance()
            ->from($user)
            ->about($source)
            ->fileCount($fileCount)
            ->sendBulk(Yii::$app->notification->getFollowers($content));
    }

    /**
     * The tracked files of this batch that are still published at the level and in the
     * container they were uploaded into — not deleted, and not moved away since.
     *
     * @return File[] newest first
     */
    private function findRemainingFiles(): array
    {
        if ($this->fileIds === []) {
            return [];
        }

        return File::find()
            ->innerJoinWith('content')
            ->where([
                'cfiles_file.id' => $this->fileIds,
                // null matches IS NULL, the top level
                'cfiles_file.parent_folder_id' => $this->folderId,
                'content.contentcontainer_id' => $this->containerId,
                'content.state' => Content::STATE_PUBLISHED,
            ])
            ->orderBy(['cfiles_file.id' => SORT_DESC])
            ->all();
    }

    /**
     * What a top-level batch, which has no folder record, is announced about: its newest
     * public file, so that followers who are not members hear of the upload too, otherwise its
     * newest file.
     *
     * @param File[] $files newest first
     */
    private function pickFile(array $files): File
    {
        foreach ($files as $file) {
            if ($file->content->isPublic()) {
                return $file;
            }
        }

        return $files[0];
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
        return self::CACHE_KEY_PREFIX . $this->containerId . '.' . ($this->folderId ?? 0) . '.' . $this->userId;
    }
}
