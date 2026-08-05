<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2026 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\cfiles\notifications;

use humhub\helpers\Html;
use humhub\modules\cfiles\libs\FileUploadBatch;
use humhub\modules\cfiles\models\Folder;
use humhub\modules\content\notifications\ContentCreated;
use Yii;

/**
 * Announces all files a user uploaded into a folder as one notification.
 *
 * Replaces the per file content created notification, which is suppressed by
 * [[\humhub\modules\cfiles\models\File::$silentContentCreation]].
 *
 * Extending [[ContentCreated]] keeps the notification in the existing "New content" category,
 * so users and administrators do not have to configure a new notification type, and reuses its
 * `canView()` check for the announced folder.
 *
 * @see FileUploadBatch
 * @since 0.19
 */
class FilesUploaded extends ContentCreated
{
    /**
     * @inheritdoc
     *
     * No view file is needed: no `filesUploaded.php` exists in `notifications/views` nor in
     * `@notification/views`, so the renderer falls back to `@notification/views/default.php`
     * (and `mails/default.php`), which renders [[html()]] plus a "View Online" button.
     * Adding `notifications/views/filesUploaded.php` or `notifications/views/mails/filesUploaded.php`
     * later overrides that without any code change.
     *
     * @see \humhub\components\rendering\DefaultViewPathRenderer::getViewFile()
     */
    public $viewName = 'filesUploaded';

    /**
     * @inheritdoc
     */
    public $moduleId = 'cfiles';

    /**
     * @param int $fileCount number of uploaded files this notification announces
     * @return $this
     */
    public function fileCount(int $fileCount)
    {
        return $this->payload(['fileCount' => $fileCount]);
    }

    /**
     * @return int number of uploaded files this notification announces
     */
    public function getFileCount(): int
    {
        return max(1, (int)($this->payload['fileCount'] ?? 1));
    }

    /**
     * The folder title instead of `getContentInfo()`, since `Folder::getContentDescription()`
     * returns the raw title, which is an untranslated placeholder for the root and the posted
     * files folder.
     */
    protected function getFolderTitle(): string
    {
        return $this->source instanceof Folder ? $this->source->getTitle() : '';
    }

    /**
     * @inheritdoc
     */
    public function html()
    {
        return Yii::t('CfilesModule.base', '{displayName} added {n,plural,=1{a file} other{# files}} to the folder "{folderTitle}".', [
            'displayName' => Html::tag('strong', Html::encode($this->originator->displayName)),
            'folderTitle' => Html::encode($this->getFolderTitle()),
            'n' => $this->getFileCount(),
        ]);
    }

    /**
     * @inheritdoc
     */
    public function getMailSubject()
    {
        $space = $this->getSpace();

        if ($space) {
            return Yii::t('CfilesModule.base', '{originator} added {n,plural,=1{a file} other{# files}} to the folder "{folderTitle}" in Space {space}', [
                'originator' => $this->originator->displayName,
                'folderTitle' => $this->getFolderTitle(),
                'space' => $space->displayName,
                'n' => $this->getFileCount(),
            ]);
        }

        return Yii::t('CfilesModule.base', '{originator} added {n,plural,=1{a file} other{# files}} to the folder "{folderTitle}"', [
            'originator' => $this->originator->displayName,
            'folderTitle' => $this->getFolderTitle(),
            'n' => $this->getFileCount(),
        ]);
    }

    /**
     * @inheritdoc
     *
     * The base implementation only keeps source and originator, but the file count has to
     * survive the queue, since it is only persisted when the notification records are created.
     */
    public function __serialize(): array
    {
        $data = parent::__serialize();
        $data['fileCount'] = $this->getFileCount();

        return $data;
    }

    /**
     * @inheritdoc
     */
    public function __unserialize($unserializedArr)
    {
        parent::__unserialize($unserializedArr);

        if (isset($unserializedArr['fileCount'])) {
            $this->fileCount((int)$unserializedArr['fileCount']);
        }
    }
}
