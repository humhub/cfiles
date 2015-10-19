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

/**
 * Description of BrowseController
 *
 * @author luke
 */
class BrowseController extends \humhub\modules\content\components\ContentContainerController
{

    private $_currentFolder = false;

    public $hideSidebar = true;

    public $files = array();

    public $errorMessages = array();

    public function actionIndex()
    {
        $folder = $this->getCurrentFolder();
        $folderId = 0;
        if ($folder !== null) {
            $folderId = $folder->id;
        }
        
        return $this->render('index', [
            'contentContainer' => $this->contentContainer,
            'folderId' => $folderId,
            'fileList' => $this->renderFileList()
        ]);
    }

    public function actionUpload()
    {
        Yii::$app->response->format = 'json';
        
        $response = [];
        
        foreach (UploadedFile::getInstancesByName('files') as $cFile) {
            
            $folder = $this->getCurrentFolder();
            $currentFolderId = empty($folder) ? 0 : $folder->id;
            
            // check if the file already exists in this dir
            $filesQuery = File::find()->joinWith('baseFile')
                ->readable()
                ->andWhere([
                'title' => $cFile->name,
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
                $response['logmessages'][] = Yii::t('CfilesModule.views_browse_index', 'Info: %title% was replaced by a newer version.', [
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
                    $response['logmessages'][] = Yii::t('CfilesModule.views_browse_index', 'Error: Could not save file %title%. ', [
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
                $response['logmessages'][] = Yii::t('CfilesModule.views_browse_index', 'Error: Could not save file %title%. ', [
                    '%title%' => $humhubFile->filename
                ]) . $messages;
                $response['log'] = true;
            }
        }
        
        $response['files'] = $this->files;
        return $response;
    }

    public function actionEditFolder()
    {
        // fid indicates the current parent folder id
        $currentFolderId = 0;
        $currentFolder = $this->getCurrentFolder();
        if ($currentFolder !== null) {
            $currentFolderId = $currentFolder->id;
        }
        // id is set if a folder should be edited
        $id = (int) Yii::$app->request->get('id');
        // the parend folder id
        $pid = (int) Yii::$app->request->get('pid');
        // the new / edited folders title
        $title = trim(Yii::$app->request->post('Folder')['title']);
        Yii::$app->request->post('Folder')['title'] = $title;
        // if the folder exists, should it be overwritten?
        $mode = (string) Yii::$app->request->get('mode');
        
        // check if a folder for the given id exists
        $query = Folder::find()->contentContainer($this->contentContainer)
            ->readable()
            ->where([
            'cfiles_folder.id' => $id
        ]);
        $folder = $query->one();
        
        // if not a folder has to be created
        if (empty($folder)) {
            // create a new folder
            $folder = new Folder();
            $folder->content->container = $this->contentContainer;
            $folder->parent_folder_id = $currentFolderId;
        }
        
        // try to save the current folder
        if ($folder->load(Yii::$app->request->post()) && $folder->validate() && $folder->save()) {
            return $this->htmlRedirect($this->contentContainer->createUrl('index', [
                'fid' => $folder->id
            ]));
        }
        
        // if it could not be saved successfully, or the formular was empty, render the edit folder modal
        return $this->renderAjax('editFolder', [
            'folder' => $folder,
            'contentContainer' => $this->contentContainer,
            'currentFolderId' => $currentFolderId
        ]);
    }

    public function actionMoveFiles()
    {
        $folder = $this->getCurrentFolder();
        $currentFolderId = empty($folder) ? 0 : $folder->id;
        
        $selectedItems = Yii::$app->request->post('selected');
        
        if (empty(Yii::$app->request->post('destfid'))) {
            // render modal if no destination folder is specified
            return $this->renderAjax('moveFiles', [
                'folders' => $this->getFolderList(),
                'contentContainer' => $this->contentContainer,
                'currentFolderId' => $currentFolderId
            ]);
        }
        
        if (is_array($selectedItems)) {
            foreach ($selectedItems as $itemId) {
                $item = $this->module->getItemById($itemId);
                if ($item !== null) {
                    $item->delete();
                }
            }
        }
        return $this->renderFileList();
    }

    public function actionDelete()
    {
        $selectedItems = Yii::$app->request->post('selected');
        if (is_array($selectedItems)) {
            foreach ($selectedItems as $itemId) {
                $item = $this->module->getItemById($itemId);
                if ($item !== null) {
                    $item->delete();
                }
            }
        }
        return $this->renderFileList();
    }

    /**
     * Returns file list
     *
     * @return type
     */
    protected function renderFileList()
    {
        $folder = $this->getCurrentFolder();
        $filesQuery = File::find()->joinWith('baseFile')
            ->contentContainer($this->contentContainer)
            ->readable();
        $foldersQuery = Folder::find()->contentContainer($this->contentContainer)->readable();
        
        if ($folder === null) {
            $filesQuery->andWhere([
                'cfiles_file.parent_folder_id' => 0
            ]);
            $foldersQuery->andWhere([
                'cfiles_folder.parent_folder_id' => 0
            ]);
        } else {
            $filesQuery->andWhere([
                'cfiles_file.parent_folder_id' => $folder->id
            ]);
            $foldersQuery->andWhere([
                'cfiles_folder.parent_folder_id' => $folder->id
            ]);
        }
        
        return $this->renderAjax('fileList', [
            'items' => array_merge($foldersQuery->all(), $filesQuery->all()),
            'contentContainer' => $this->contentContainer,
            'crumb' => $this->generateCrumb(),
            'errorMessages' => $this->errorMessages
        ]);
    }

    /**
     * Returns current folder by given fid get parameter.
     * If no or invalid folderId (fid) is given, null is returned.
     *
     * @return Folder
     */
    private function getCurrentFolder()
    {
        if ($this->_currentFolder !== false) {
            return $this->_currentFolder;
        }
        
        $folder = null;
        $folderId = (int) Yii::$app->request->get('fid', 0);
        if ($folderId !== 0) {
            $folder = Folder::find()->contentContainer($this->contentContainer)
                ->readable()
                ->where([
                'cfiles_folder.id' => $folderId
            ])
                ->one();
        }
        
        $this->_currentFolder = $folder;
        return $folder;
    }

    /**
     * Returns all parent folders as array
     *
     * @return array of parent folders
     */
    private function generateCrumb()
    {
        $crumb = [];
        $currentFolder = $this->getCurrentFolder();
        
        if ($currentFolder !== null) {
            $folder = clone $currentFolder;
            while ($folder->parentFolder != null) {
                $crumb[] = $folder->parentFolder;
                $folder = $folder->parentFolder;
            }
            
            $crumb[] = $currentFolder;
        }
        
        return $crumb;
    }

    private function getFolderList($parentId = 0)
    {
        $dirstruc = [];
        $folders = Folder::find()->contentContainer($this->contentContainer)
            ->readable()
            ->where([
            'cfiles_folder.parent_folder_id' => $parentId
        ])
            ->all();
        foreach ($folders as $folder) {
//             echo '<pre>';
//             print_r($folder->id);
//             print_r($folder->title);
//             echo '</pre>';
            $dirstruc[] = [
                'folder' => $folder,
                'subfolders' => $this->getFolderlist($folder->id)
            ];
        }
        
        return $dirstruc;
    }
}
