<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2015 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\cfiles\controllers;

use humhub\modules\content\models\Content;
use Yii;
use yii\web\HttpException;
use humhub\components\ActiveRecord;
use humhub\modules\cfiles\models\Folder;
use humhub\modules\cfiles\actions\UploadAction;
use humhub\modules\cfiles\permissions\WriteAccess;
use humhub\modules\cfiles\models\File;
use humhub\modules\file\models\File as ModelFile;

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
            'index' => ['class' => UploadAction::class]
        ];
    }

    public function actionImport($fid)
    {
        $guids = Yii::$app->request->post('guids');
        $guids = is_string($guids) ? array_map('trim', explode(',', $guids)) : $guids;

        if (!is_array($guids)) {
            throw new HttpException(400);
        }

        $folder = Folder::findOne($fid);

        $errors = [];

        foreach ($guids as $guid) {
            $cFile = ModelFile::findOne(['guid' => $guid]);

            if (!$cFile) {
                $errors[] = Yii::t('Cfiles.base', 'Could not import file with guid {guid}. File not found', ['guid' => $guid]);
                Yii::error(Yii::t('Cfiles.base', 'Could not import file with guid {guid}. File not found', ['guid' => $guid]));
                continue;
            }

            $cFile->show_in_stream = false;

            $file = new File($this->contentContainer);
            $file->setFileContent($cFile);
            $folder->moveItem($file);

            $file->visibility = Content::VISIBILITY_PRIVATE;
            $file->save();

            if ($file->hasErrors()) {
                $errors[] = $this->actionResponseError($file);
            }

            if ($file->baseFile->hasErrors()) {
                $errors[] = $this->actionResponseError($file->baseFile);
            }
        }

        if ($errors) {
            array_unshift($errors, Yii::t('CfilesModule.base', 'Some files could not be imported: ') );
        }

        return $this->asJson(['success' => empty($errors), 'errors' => $errors]);
    }

    public function actionResponseError(ActiveRecord $record)
    {
        $errorMsg = Yii::t('CfilesModule.base', 'Some files could not be imported: ');
        foreach ($record->getErrors() as $key => $errors) {
            foreach ($errors as $error) {
                $errorMsg .= $error.' ';
            }
        }
    }
}
