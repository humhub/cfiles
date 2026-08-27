<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\cfiles\controllers\api;

use humhub\modules\cfiles\models\File;
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
    public function behaviors()
    {
        return ArrayHelper::merge(parent::behaviors(), [
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'update' => ['PATCH', 'PUT'],
                ],
            ],
        ]);
    }

    /**
     * Renames a file, changes its description or its visibility.
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

        if ($request->getBodyParam('visibility') !== null) {
            $file->visibility = (int)$request->getBodyParam('visibility');
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

        // An orphan (its folder was hard-deleted underneath it) has no siblings to collide
        // with; the integrity check is what cleans those up.
        $duplicate = $file->parentFolder === null
            ? null
            : (new FolderContentService($file->parentFolder))->findFile($file->baseFile->file_name);

        if ($duplicate && !$duplicate->is($file)) {
            return Yii::t('CfilesModule.base', 'A file with that name already exists in this folder.');
        }

        return null;
    }
}
