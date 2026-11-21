<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\cfiles\controllers\api;

use humhub\modules\cfiles\models\File;
use humhub\modules\cfiles\Module;
use humhub\modules\cfiles\services\FolderContentService;
use humhub\modules\cfiles\serializers\FileSerializer;
use Yii;
use yii\filters\VerbFilter;
use yii\helpers\ArrayHelper;

/**
 * Changing a single file's own attributes. Creating one is an upload into a folder
 * ({@see FolderController::actionUpload()}); moving and deleting are bulk operations
 * ({@see ItemController}).
 *
 * @since 1.0
 */
class FileController extends BaseController
{
    /**
     * @inheritdoc
     */
    protected array $scopes = ['*' => Module::SCOPE_WRITE];

    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return ArrayHelper::merge(parent::behaviors(), [
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'update' => ['PATCH'],
                ],
            ],
        ]);
    }

    /**
     * Renames a file, changes its description, its visibility (`private` or `public`) or its
     * topics (`topics`, see {@see self::topicsParam()}).
     *
     * Renaming means renaming the STORED file: a cfiles file has no title of its own, its
     * name is the platform file's `file_name` (see {@see File::getTitle()}). The two records
     * are saved together — {@see File::afterSave()} notices the changed name and persists the
     * platform file with it.
     */
    public function actionUpdate($id)
    {
        $file = $this->findFile((int)$id);
        $this->assertCanEdit($file);

        $topics = $this->topicsParam($file);
        if ($topics !== null && !$topics->isValid()) {
            return $this->validationErrors($topics->errors);
        }

        $visibility = $this->visibilityParam();
        if ($visibility !== null && !$visibility->isValid()) {
            return $this->validationErrors($visibility->errors);
        }

        $request = Yii::$app->request;

        if ($request->getBodyParam('title') !== null) {
            $error = $this->rename($file, (string)$request->getBodyParam('title'));

            if ($error !== null) {
                Yii::$app->response->statusCode = 422;
                return ['errors' => ['title' => [$error]]];
            }
        }

        if ($request->getBodyParam('description') !== null) {
            $file->description = (string)$request->getBodyParam('description');
        }

        if ($visibility !== null) {
            $file->visibility = $visibility->value;
        }

        if ($topics !== null) {
            $file->topics = $topics->present ? $topics->value : [];
        }

        if (!$file->save()) {
            return $this->validationErrors($file);
        }

        return FileSerializer::file($file);
    }

    /**
     * Applies a new name to the file, or returns why it cannot be applied.
     *
     * The uniqueness check is not a model rule (it spans two records and a folder), so it
     * lives here the way it lived in the edit controller before.
     */
    private function rename(File $file, string $title): ?string
    {
        $file->baseFile->file_name = $title;

        if (!$file->baseFile->validate(['file_name'])) {
            return $file->baseFile->getFirstError('file_name');
        }

        $level = new FolderContentService($file->content->container, $file->parentFolder);

        if ($level->fileExists($file->baseFile->file_name, $file)) {
            return Yii::t('CfilesModule.base', 'A file with that name already exists in this folder.');
        }

        if ($level->folderExists($file->baseFile->file_name)) {
            return Yii::t('CfilesModule.base', 'A folder with this name already exists.');
        }

        return null;
    }
}
