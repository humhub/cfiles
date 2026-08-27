<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\cfiles\controllers\api;

use humhub\modules\cfiles\serializers\FileSerializer;
use humhub\modules\cfiles\serializers\FolderSerializer;
use humhub\modules\cfiles\services\FolderContentService;
use humhub\modules\cfiles\services\FolderListingService;
use Yii;
use yii\filters\VerbFilter;
use yii\helpers\ArrayHelper;
use yii\web\UploadedFile;

/**
 * Reading and changing folders, and adding to them.
 *
 * @since 1.0
 */
class FolderController extends BaseController
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
                    'view' => ['GET', 'HEAD'],
                    'update' => ['PATCH', 'PUT'],
                    'create' => ['POST'],
                    'upload' => ['POST'],
                ],
            ],
        ]);
    }

    /**
     * One folder: the folder itself, its path from the root, and a page of its contents with
     * folders sorted ahead of files.
     */
    public function actionView($id)
    {
        $request = Yii::$app->request;

        return (new FolderListingService($this->findFolder((int)$id)))->payload(
            $request->get('sort'),
            $request->get('order'),
            (int)$request->get('page', 1),
            (int)$request->get('pageSize', FolderListingService::DEFAULT_PAGE_SIZE),
        );
    }

    /**
     * Creates a subfolder of `<id>`.
     */
    public function actionCreate($id)
    {
        $parent = $this->findFolder((int)$id);
        $this->assertCanWrite($parent->content->container);

        $folder = (new FolderContentService($parent))->newFolder();
        $folder->setAttributes($this->writableAttributes(), false);

        if (!$folder->save()) {
            return $this->validationErrors($folder);
        }

        Yii::$app->response->statusCode = 201;

        return FolderSerializer::folder($folder);
    }

    /**
     * Renames a folder, changes its description or its visibility.
     */
    public function actionUpdate($id)
    {
        $folder = $this->findFolder((int)$id);
        $this->assertCanEdit($folder);

        $folder->setAttributes($this->writableAttributes(), false);

        if (!$folder->save()) {
            return $this->validationErrors($folder);
        }

        return FolderSerializer::folder($folder);
    }

    /**
     * Uploads one or more files into `<id>`.
     *
     * Answers per file rather than failing the whole request on one bad upload: a batch in
     * which a single file is rejected should still land the others, and the client has to be
     * able to say which one did not make it.
     */
    public function actionUpload($id)
    {
        $folder = $this->findFolder((int)$id);
        $this->assertCanWrite($folder->content->container);

        $uploads = UploadedFile::getInstancesByName('files');

        if ($uploads === []) {
            Yii::$app->response->statusCode = 422;

            return ['errors' => ['files' => [Yii::t('CfilesModule.base', 'No file uploaded.')]]];
        }

        $content = new FolderContentService($folder);
        $created = [];
        $errors = [];

        foreach ($uploads as $upload) {
            $file = $content->addUploadedFile($upload);

            if ($file->hasErrors() || $file->baseFile->hasErrors()) {
                $errors[] = [
                    'fileName' => $upload->name,
                    'messages' => array_merge(
                        array_values($file->getFirstErrors()),
                        array_values($file->baseFile->getFirstErrors()),
                    ),
                ];
                continue;
            }

            $created[] = FileSerializer::file($file);
        }

        Yii::$app->response->statusCode = $created === [] ? 422 : 201;

        return ['results' => $created, 'errors' => $errors];
    }

    /**
     * The attributes a folder write accepts.
     *
     * An allowlist rather than `load()`: mass assignment would otherwise reach `type` (which
     * decides whether a folder is THE root) and `parent_folder_id` (which is what the move
     * endpoint is for, with its own permission rules).
     */
    private function writableAttributes(): array
    {
        $request = Yii::$app->request;
        $attributes = [];

        foreach (['title', 'description'] as $name) {
            if ($request->getBodyParam($name) !== null) {
                $attributes[$name] = (string)$request->getBodyParam($name);
            }
        }

        if ($request->getBodyParam('visibility') !== null) {
            $attributes['visibility'] = (int)$request->getBodyParam('visibility');
        }

        return $attributes;
    }
}
