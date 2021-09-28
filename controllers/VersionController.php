<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2021 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\cfiles\controllers;

use humhub\modules\cfiles\models\File;
use humhub\modules\cfiles\models\forms\VersionForm;
use humhub\modules\cfiles\permissions\ManageFiles;
use humhub\modules\cfiles\widgets\VersionsView;
use humhub\modules\file\models\File as BaseFile;
use Yii;
use yii\web\HttpException;

/**
 * VersionController to review file versions and switch between the
 *
 * @author luke
 */
class VersionController extends BaseController
{

    /**
     * @inheritdoc
     */
    public function getAccessRules()
    {
        return [
            ['permission' => [ManageFiles::class]]
        ];
    }

    /**
     * Action to view all versions of the requested File
     * @return string
     */
    public function actionIndex()
    {
        $model = new VersionForm(['file' => $this->getFile()]);

        if (!$model->load()) {
            return $this->renderAjax('index', [
                'model' => $model,
            ]);
        }

        if ($model->save()) {
            $this->view->success(Yii::t('CfilesModule.base','File {fileName} has been reverted to version from {fileDateTime}', [
                'fileName' => $model->file->baseFile->file_name,
                'fileDateTime' => Yii::$app->formatter->asDatetime($model->file->baseFile->created_at, 'short'),
            ]));
        } else {
            $errorMsg = '';
            foreach ($model->getErrors() as $errors) {
                foreach ($errors as $error) {
                    $errorMsg .= $error.' ';
                }
            }
            $this->view->error($errorMsg);
        }

        return $this->htmlRedirect($model->file->content->container->createUrl('/cfiles/browse'));
    }

    /**
     * Load file versions for the single requested page
     */
    public function actionPage()
    {
        $versionsView = new VersionsView([
            'file' => $this->getFile(),
            'page' => (int)Yii::$app->request->get('page', 2),
        ]);

        return $this->asJson([
            'html' => $versionsView->renderVersions(),
            'isLast' => $versionsView->isLastPage(),
        ]);
    }

    /**
     * Action to delete a version of the requested File
     * @throws HttpException
     */
    public function actionDelete()
    {
        $this->forcePostRequest();

        $file = $this->getFile();

        $versionFileId = (int)Yii::$app->request->get('version');
        $deletedVersionFile = BaseFile::findOne(['id' => $versionFileId]);

        if (!$deletedVersionFile || !$file->baseFile->isVersion($deletedVersionFile)) {
            throw new HttpException(404, 'Version not found!');
        }

        $deletedVersionDate = Yii::$app->formatter->asDatetime($deletedVersionFile->created_at, 'short');

        if (!$deletedVersionFile->delete()) {
            return $this->asJson([
                'error' => Yii::t('CfilesModule.user', 'The version "{versionDate}" could not be deleted!', ['versionDate' => $deletedVersionDate]),
            ]);
        }

        return $this->asJson([
            'deleted' => $versionFileId,
            'message' => Yii::t('CfilesModule.user', 'The version "{versionDate}" has been deleted.', ['versionDate' => $deletedVersionDate]),
        ]);
    }

    private function getFile(): File
    {
        /* @var File $file */
        $file = File::find()
            ->readable()
            ->andWhere(['cfiles_file.id' => Yii::$app->request->get('id')])
            ->one();

        if (!$file) {
            throw new HttpException(404, 'File not found!');
        }

        if (!$file->canEdit()) {
            throw new HttpException(403);
        }

        return $file;
    }

}
