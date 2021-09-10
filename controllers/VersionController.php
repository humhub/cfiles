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
        $file = File::findOne(['id' => Yii::$app->request->get('id')]);

        if (!$file) {
            throw new HttpException(404, 'File not found!');
        }

        $model = new VersionForm(['file' => $file]);

        if (!$model->load(Yii::$app->request->post())) {
            return $this->renderAjax('index', [
                'model' => $model,
            ]);
        }

        if ($model->save()) {
            $this->view->success(Yii::t('CfilesModule.base','File {fileName} has been switched to version from {fileDateTime}', [
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

}
