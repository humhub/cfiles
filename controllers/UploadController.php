<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2015 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\cfiles\controllers;

use Yii;
use yii\web\HttpException;
use humhub\modules\cfiles\actions\UploadAction;
use humhub\modules\cfiles\permissions\WriteAccess;
use humhub\modules\cfiles\models\File;

/**
 * Description of BrowseController
 *
 * @author luke, Sebastian Stumpf
 */
class UploadController extends BrowseController
{
    public function getAccessRules()
    {
        return [
            ['permission' => [WriteAccess::class]]
        ];
    }

    public function actions()
    {
        return [
            'index' => [
                'class' => UploadAction::class,
            ],
        ];
    }

    public function actionImport()
    {
        if (!$this->canWrite()) {
            throw new HttpException(401, Yii::t('CfilesModule.base', 'Insufficient rights to execute this action.'));
        }

        $fid = Yii::$app->request->get('fid');

        $guids = Yii::$app->request->post('guids');

        //check if this guid is already taken

        $file = new File(['parent_folder_id' => $fid]);
        $file->content->container = $this->contentContainer;

        if ($file->save()) {
            $file->fileManager->attach($guids);

            foreach ($file->fileManager->findAll() as $baseFile) {
                $baseFile->show_in_stream = false;
                $baseFile->save();
            }

        }

        return $this->asJson(['success' => true]);
    }
}
