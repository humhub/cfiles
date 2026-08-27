<?php

namespace humhub\modules\cfiles;

use humhub\modules\cfiles\models\ConfigureContainerForm;
use humhub\modules\space\models\Space;
use humhub\modules\user\models\User;
use humhub\modules\content\components\ContentContainerModule;
use humhub\modules\content\components\ContentContainerActiveRecord;
use humhub\modules\cfiles\models\FileSystemItem;
use humhub\modules\cfiles\models\Folder;
use humhub\modules\cfiles\models\File;
use Yii;
use yii\helpers\Url;

class Module extends ContentContainerModule
{
    /**
     * @var int Files uploaded into the same folder by the same user are announced by a single
     *      notification, once no further file was uploaded for this many minutes.
     *      0 announces every upload request on its own.
     *
     * @see \humhub\modules\cfiles\libs\FileUploadBatch
     * @since 0.19
     */
    public int $uploadNotificationDelay = 10;

    /**
     * @var string the sort a folder listing falls back to, one of the keys of
     *      {@see \humhub\modules\cfiles\services\FolderListingService::SORT_COLUMNS}.
     */
    public $defaultSort = 'name';

    /**
     * @var int SORT_ASC or SORT_DESC
     */
    public $defaultOrder = SORT_ASC;

    /**
     * @inheritdoc
     */
    public function getContentContainerTypes()
    {
        return [
            Space::className(),
            User::className(),
        ];
    }

    /**
     * @inheritdoc
     */
    public function getContentClasses(): array
    {
        return [File::class];
    }

    /**
     * @inheritdoc
     */
    public function getPermissions($contentContainer = null)
    {
        if ($contentContainer instanceof Space) {
            return [
                new permissions\WriteAccess(),
                new permissions\ManageFiles(),
            ];
        }

        return [];
    }

    /**
     * @inheritdoc
     */
    public function disable()
    {
        $this->deleteAll(Folder::find()->all(), File::find()->all());

        return parent::disable();
    }

    /**
     * @inheritdoc
     */
    public function disableContentContainer(ContentContainerActiveRecord $container)
    {
        $this->deleteAll(
            Folder::find()->contentContainer($container)->all(),
            File::find()->contentContainer($container)->all(),
        );

        parent::disableContentContainer($container);
    }

    /**
     * Removes the given trees for good.
     *
     * Folders first: deleting one cascades into its children, so most files are gone before
     * the file pass runs, and the pass only has to catch what a broken parent chain left
     * behind.
     *
     * @param FileSystemItem[] $folders
     * @param FileSystemItem[] $files
     */
    private function deleteAll(array $folders, array $files): void
    {
        foreach (array_merge($folders, $files) as $item) {
            if ($item->getIsNewRecord() === false) {
                $item->hardDelete();
            }
        }
    }

    /**
     * @inheritdoc
     */
    public function getContentContainerName(ContentContainerActiveRecord $container)
    {
        return Yii::t('CfilesModule.base', 'Files');
    }

    /**
     * @inheritdoc
     */
    public function getContentContainerDescription(ContentContainerActiveRecord $container)
    {
        if ($container instanceof Space) {
            return Yii::t('CfilesModule.base', 'Adds files module to this space.');
        }

        return Yii::t('CfilesModule.base', 'Adds files module to your profile.');
    }

    public function getContentContainerConfigUrl(ContentContainerActiveRecord $container)
    {
        return $container->createUrl('/cfiles/config-container');
    }

    /**
     * @inheritdoc
     */
    public function getConfigUrl()
    {
        return Url::to(['/cfiles/config']);
    }

    /**
     * Determines a download count column is visible or not
     *
     * @return bool
     */
    public function getDisplayDownloadCount(): bool
    {
        return $this->settings->get('displayDownloadCount', false);
    }

    public function getContentHiddenGlobalDefault(): bool
    {
        return $this->settings->get('contentHiddenGlobalDefault', false);
    }

    public function getContentHiddenDefault(ContentContainerActiveRecord $contentContainer): bool
    {
        $configuration = (new ConfigureContainerForm(['contentContainer' => $contentContainer]));
        $configuration->loadBySettings();
        return (bool)$configuration->contentHiddenDefault;
    }
}
