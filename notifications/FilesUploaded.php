<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2026 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\cfiles\notifications;

use humhub\helpers\Html;
use humhub\modules\cfiles\libs\FileUploadBatch;
use humhub\modules\cfiles\models\File;
use humhub\modules\content\notifications\ContentCreated;
use Yii;

/**
 * Announces all files a user uploaded into a folder, or into the top level of a container, as
 * one notification.
 *
 * Its source is the folder, or at the top level, which has no folder record, the most recently
 * uploaded file ({@see FileUploadBatch}). Either way the notification links to the level the
 * files were uploaded into ({@see \humhub\modules\cfiles\models\File::getUrl()}).
 *
 * Replaces the per file content created notification, which is suppressed by
 * [[\humhub\modules\cfiles\models\File::$silentContentCreation]].
 *
 * Extending [[ContentCreated]] keeps the notification in the existing "New content" category,
 * so users and administrators do not have to configure a new notification type, and reuses its
 * `canView()` check for the announced folder or file.
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
     * Whether the files were uploaded into the container's top level. It has no folder record,
     * so the notification is about one of the files instead ({@see FileUploadBatch}).
     */
    protected function isTopLevel(): bool
    {
        return $this->source instanceof File;
    }

    /**
     * @inheritdoc
     */
    public function html()
    {
        $displayName = Html::tag('strong', Html::encode($this->originator->displayName));
        $n = $this->getFileCount();

        if (!$this->isTopLevel()) {
            return Yii::t('CfilesModule.base', '{displayName} added {n,plural,=1{a file} other{# files}} to the folder "{folderTitle}".', [
                'displayName' => $displayName,
                'folderTitle' => Html::encode($this->source->getTitle()),
                'n' => $n,
            ]);
        }

        $space = $this->getSpace();

        if ($space) {
            return Yii::t('CfilesModule.base', '{displayName} added {n,plural,=1{a file} other{# files}} to the files of Space {space}.', [
                'displayName' => $displayName,
                'space' => Html::encode($space->displayName),
                'n' => $n,
            ]);
        }

        return Yii::t('CfilesModule.base', '{displayName} added {n,plural,=1{a file} other{# files}} to the files.', [
            'displayName' => $displayName,
            'n' => $n,
        ]);
    }

    /**
     * @inheritdoc
     */
    public function getMailSubject()
    {
        $space = $this->getSpace();
        $params = [
            'originator' => $this->originator->displayName,
            'n' => $this->getFileCount(),
        ];

        if ($space) {
            $params['space'] = $space->displayName;
        }

        if ($this->isTopLevel()) {
            return $space
                ? Yii::t('CfilesModule.base', '{originator} added {n,plural,=1{a file} other{# files}} to the files of Space {space}', $params)
                : Yii::t('CfilesModule.base', '{originator} added {n,plural,=1{a file} other{# files}} to the files', $params);
        }

        $params['folderTitle'] = $this->source->getTitle();

        return $space
            ? Yii::t('CfilesModule.base', '{originator} added {n,plural,=1{a file} other{# files}} to the folder "{folderTitle}" in Space {space}', $params)
            : Yii::t('CfilesModule.base', '{originator} added {n,plural,=1{a file} other{# files}} to the folder "{folderTitle}"', $params);
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
