<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\cfiles\controllers;

use humhub\modules\content\components\ContentContainerController;
use humhub\modules\file\models\File;
use yii\web\NotFoundHttpException;
use yii\web\Response;

class DownloadController extends ContentContainerController
{
    /**
     * This action is used to bypass browser cache issues for cases:
     *  - file content was changed (URL param `hash_sha1` fixes this issue, @see File::getUrl())
     *  - file was renamed (URL param `file_name` fixed this issue)
     *
     * @param string $guid
     * @return Response
     * @throws NotFoundHttpException
     */
    public function actionIndex($guid)
    {
        $file = File::findOne(['guid' => $guid]);

        if (!$file) {
            throw new NotFoundHttpException();
        }

        return $this->redirect($file->getUrl([
            'download' => 1, // Force downloading even if file can be viewable by browser
            'file_name' => $file->file_name // used to avoid browser cache when file was renamed
        ]));
    }
}
