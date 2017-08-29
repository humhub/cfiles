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

    public function actionImport($fid)
    {
        //Todo: check if this guid is already taken

        $file = new File(['parent_folder_id' => $fid]);
        $file->content->container = $this->contentContainer;

        $guids = Yii::$app->request->post('guids');

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
