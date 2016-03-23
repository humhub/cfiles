<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2015 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\cfiles\controllers;

use Yii;
use yii\web\HttpException;
use yii\web\UploadedFile;
use humhub\modules\cfiles\models\File;
use humhub\modules\cfiles\models\Folder;
use humhub\modules\content\models\Content;
use humhub\modules\content\components\ContentActiveRecord;
use humhub\modules\comment\models\Comment;
use yii\helpers\FileHelper;
use humhub\models\Setting;

/**
 * Description of BrowseController
 *
 * @author luke, Sebastian Stumpf
 */
class UploadController extends BrowseController
{

    public $files = array();

    /**
     * Action to upload multiple files.
     * @return multitype:boolean multitype:
     */
    public function actionIndex()
    {
        Yii::$app->response->format = 'json';
        
        if(!$this->canWrite()) {
            throw new HttpException(401, Yii::t('CfilesModule.base', 'Insufficient rights to execute this action.'));
        }

        $response = [];

        foreach (UploadedFile::getInstancesByName('files') as $cFile) {

            $folder = $this->getCurrentFolder();
            $currentFolderId = empty($folder) ? self::ROOT_ID : $folder->id;

            // check if the file already exists in this dir
            $filesQuery = File::find()->contentContainer($this->contentContainer)->joinWith('baseFile')
                    ->readable()
                    ->andWhere([
                'title' => File::sanitizeFilename($cFile->name),
                'parent_folder_id' => $currentFolderId
            ]);
            $file = $filesQuery->one();

            // if not, initialize new File
            if (empty($file)) {
                $file = new File();
                $humhubFile = new \humhub\modules\file\models\File();
            }             // else replace the existing file
            else {
                $humhubFile = $file->baseFile;
                // logging file replacement
                $response['infomessages'][] = Yii::t('CfilesModule.base', '%title% was replaced by a newer version.', [
                            '%title%' => $file->title
                ]);
                $response['log'] = true;
            }

            $humhubFile->setUploadedFile($cFile);
            if ($humhubFile->save()) {

                $file->content->container = $this->contentContainer;
                $folder = $this->getCurrentFolder();

                if ($folder !== null) {
                    $file->parent_folder_id = $folder->id;
                }

                if ($file->save()) {
                    $humhubFile->object_model = $file->className();
                    $humhubFile->object_id = $file->id;
                    $humhubFile->save();
                    $this->files[] = array_merge($humhubFile->getInfoArray(), [
                        'fileList' => $this->renderFileList()
                    ]);
                } else {
                    $count = 0;
                    $messages = "";
                    // show multiple occurred errors
                    foreach ($file->errors as $key => $message) {
                        $messages .= ($count ++ ? ' | ' : '') . $message[0];
                    }
                    $response['errormessages'][] = Yii::t('CfilesModule.base', 'Could not save file %title%. ', [
                                '%title%' => $file->title
                            ]) . $messages;
                    $response['log'] = true;
                }
            } else {
                $count = 0;
                $messages = "";
                // show multiple occurred errors
                foreach ($humhubFile->errors as $key => $message) {
                    $messages .= ($count ++ ? ' | ' : '') . $message[0];
                }
                $response['errormessages'][] = Yii::t('CfilesModule.base', 'Could not save file %title%. ', [
                            '%title%' => $humhubFile->filename
                        ]) . $messages;
                $response['log'] = true;
            }
        }

        $response['files'] = $this->files;
        return $response;
    }
}
