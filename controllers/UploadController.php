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
     *
     * @return multitype:boolean multitype:
     */
    public function actionIndex()
    {
        Yii::$app->response->format = 'json';

        if (!$this->canWrite()) {
            throw new HttpException(401, Yii::t('CfilesModule.base', 'Insufficient rights to execute this action.'));
        }

        $response = [];

        foreach (UploadedFile::getInstancesByName('files') as $cFile) {

            $folder = $this->getCurrentFolder();
            $currentFolderId = empty($folder) ? self::ROOT_ID : $folder->id;

            // check if the file already exists in this dir
            $filesQuery = File::find()->contentContainer($this->contentContainer)
                    ->joinWith('baseFile')
                    ->readable()
                    ->andWhere([
                // TODO: sanitize filename??? old function wil no longer work
                'file_name' => $cFile->name,
                'parent_folder_id' => $currentFolderId
            ]);
            $file = $filesQuery->one();

            // if not, initialize new File
            if (empty($file)) {
                $file = new File();
                $humhubFile = new \humhub\modules\file\models\FileUpload();
            } else {
                $humhubFile = $file->baseFile;
                // logging file replacement
                $response['infomessages'][] = Yii::t('CfilesModule.base', '%title% was replaced by a newer version.', [
                            '%title%' => $file->title
                ]);
                $response['log'] = true;
            }

            $humhubFile->setUploadedFile($cFile);
            if ($humhubFile->validate()) {

                $file->content->container = $this->contentContainer;
                $folder = $this->getCurrentFolder();

                if ($folder !== null) {
                    $file->parent_folder_id = $folder->id;
                }

                if ($file->save()) {
                    $humhubFile->object_model = $file->className();
                    $humhubFile->object_id = $file->id;
                    $humhubFile->show_in_stream = false;

                    $humhubFile->save();
                    $searchFile = File::findOne([
                                'id' => $file->id
                    ]); // seach index update does not work if file is not loaded from db again.. Caching problem??
                    Yii::$app->search->update($searchFile); // update index with title

                    $this->files[] = $humhubFile->getInfoArray();
                } else {
                    $count = 0;
                    $messages = "";
                    // show multiple occurred errors
                    foreach ($file->errors as $key => $message) {
                        $messages .= ($count ++ ? ' | ' : '') . $message[0];
                    }
                    $response['errormessages'][] = Yii::t('CfilesModule.base', 'Could not save file %title%. ', ['%title%' => $file->title]) . $messages;
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
        $response['fileList'] = $this->renderFileList();
        return $response;
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
        
        if($file->save()) {
            $file->fileManager->attach($guids);
            
            foreach ($file->fileManager->findAll() as $baseFile) {
                $baseFile->show_in_stream = false;
                $baseFile->save();
            }
            
        }
        
        return $this->asJson([
            'success' => true
        ]);
    }

}
