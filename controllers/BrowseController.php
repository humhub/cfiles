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
class BrowseController extends \humhub\modules\content\components\ContentContainerController
{

    const ROOT_ID = 0;
    const All_POSTED_FILES_ID = - 1;

    private $_currentFolder = false;
    protected $virtualRootFolder;
    protected $virtualAllPostedFilesFolder;
    public $hideSidebar = true;
    public $files = array();
    public $errorMessages = array();

    public function init()
    {
        $this->virtualRootFolder = new Folder();
        $this->virtualRootFolder->id = self::ROOT_ID;
        $this->virtualRootFolder->title = Yii::t('CfilesModule.base', 'root');
        $this->virtualAllPostedFilesFolder = new Folder();
        $this->virtualAllPostedFilesFolder->id = self::All_POSTED_FILES_ID;
        $this->virtualAllPostedFilesFolder->title = Yii::t('CfilesModule.base', 'All posted files');

        return parent::init();
    }

    public function actionIndex()
    {
        $folder = $this->getCurrentFolder();
        $currentFolderId = empty($folder) ? self::ROOT_ID : $folder->id;

        return $this->render('index', [
                    'contentContainer' => $this->contentContainer,
                    'folderId' => $currentFolderId,
                    'fileList' => $this->renderFileList()
        ]);
    }

    /**
     * Action to upload multiple files.
     * @return multitype:boolean multitype:
     */
    public function actionUpload()
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
            $filesQuery = File::find()->joinWith('baseFile')
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
                $response['errormessages'][] = Yii::t('CfilesModule.views_browse_index', 'Could not save file %title%. ', [
                            '%title%' => $humhubFile->filename
                        ]) . $messages;
                $response['log'] = true;
            }
        }

        $response['files'] = $this->files;
        return $response;
    }

    /**
     * Action to edit a given folder (the folders name).
     * @return string
     */
    public function actionEditFolder()
    {
        if(!$this->canWrite()) {
            throw new HttpException(401, Yii::t('CfilesModule.base', 'Insufficient rights to execute this action.'));
        }
        // fid indicates the current parent folder id
        $folder = $this->getCurrentFolder();
        $currentFolderId = empty($folder) ? self::ROOT_ID : $folder->id;
        // id is set if a folder should be edited
        $id = (int) Yii::$app->request->get('id');
        // the new / edited folders title
        $title = trim(Yii::$app->request->post('Folder')['title']);
        Yii::$app->request->post('Folder')['title'] = $title;

        // check if a folder with the given id exists.
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

        // check if a folder with the given parent id and title exists
        $query = Folder::find()->contentContainer($this->contentContainer)
                ->readable()
                ->where([
            'cfiles_folder.title' => $title,
            'cfiles_folder.parent_folder_id' => $currentFolderId
        ]);
        $similarFolder = $query->one();

        // if there is no folder with the same name, try to save the current folder
        if (empty($similarFolder) && $folder->load(Yii::$app->request->post()) && $folder->validate() && $folder->save()) {
            return $this->htmlRedirect($this->contentContainer->createUrl('index', [
                                'fid' => $folder->id
            ]));
        }

        // if a similar folder exists, add an error to the model. Must be done here, cause we need access to the content container
        if (!empty($similarFolder)) {
            $folder->title = $title;
            $folder->addError('title', \Yii::t('CfilesModule.base', 'A folder with this name already exists'));
        }

        // if it could not be saved successfully, or the formular was empty, render the edit folder modal
        return $this->renderAjax('editFolder', [
                    'folder' => $folder,
                    'contentContainer' => $this->contentContainer,
                    'currentFolderId' => $currentFolderId
        ]);
    }

    /**
     * Action to move files and folders from the current, to another folder.
     * @return string
     */
    public function actionMoveFiles()
    {
        if(!$this->canWrite()) {
            throw new HttpException(401, Yii::t('CfilesModule.base', 'Insufficient rights to execute this action.'));
        }
        $folder = $this->getCurrentFolder();
        $currentFolderId = empty($folder) ? self::ROOT_ID : $folder->id;
        $selectedItems = Yii::$app->request->post('selected');
        $selectedDatabaseItems = [];
        $destFolderId = Yii::$app->request->post('destfid');
        $init = Yii::$app->request->get('init');
        $errorMsgs = [];

        if ($init) {
            // render modal if no destination folder is specified
            return $this->renderAjax('moveFiles', [
                        'folders' => $this->getFolderList(),
                        'contentContainer' => $this->contentContainer,
                        'selectedItems' => $selectedItems,
                        'selectedFolderId' => $currentFolderId
            ]);
        }

        if (is_array($selectedItems) && !empty($selectedItems)) {
            foreach ($selectedItems as $itemId) {
                $item = $this->module->getItemById($itemId);
                if ($item !== null) {
                    if ($item->parent_folder_id == $destFolderId) {
                        $errorMsgs[] = Yii::t('CfilesModule.base', 'Moving to the same folder is not valid. Choose a valid parent folder for %title%.', [
                                    '%title%' => $item->title
                        ]);
                        continue;
                    }
                    $selectedDatabaseItems[] = $item;
                    $item->setAttribute('parent_folder_id', $destFolderId);
                    $item->validate();
                    if (!empty($item->errors)) {
                        foreach ($item->errors as $key => $error) {
                            $errorMsgs[] = $item->errors[$key][0];
                        }
                    }
                }
            }
        } else {
            $errorMsgs[] = Yii::t('CfilesModule.base', 'No valid items were selected to move.');
        }

        // render modal if errors occurred
        if (!empty($errorMsgs)) {
            return $this->renderAjax('moveFiles', [
                        'errorMsgs' => $errorMsgs,
                        'folders' => $this->getFolderList(),
                        'contentContainer' => $this->contentContainer,
                        'selectedItems' => $selectedItems,
                        'selectedFolderId' => $destFolderId
            ]);
        } else {
            // items are only then saved, if no error occurred. Else, the move transaction is canceled.
            foreach ($selectedDatabaseItems as $item) {
                $item->save();
            }
            return $this->htmlRedirect($this->contentContainer->createUrl('index', [
                                'fid' => $destFolderId
            ]));
        }
    }

    /**
     * Action to delete a file or folder.
     * @return Ambigous <\humhub\modules\cfiles\controllers\type, string>
     */
    public function actionDelete()
    {
        if(!$this->canWrite()) {
            throw new HttpException(401, Yii::t('CfilesModule.base', 'Insufficient rights to execute this action.'));
        }
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
     * Action to list all posted files from the content container.
     * @return string
     */
    public function actionAllPostedFiles()
    {
        $items = $this->getAllPostedFiles();

        $content_file_wrapper = [];

        foreach ($items as $file) {

            $searchItem = $file;
            // if the item is connected to a Comment, we have to search for the corresponding Post
            if ($file->object_model === Comment::className()) {
                $searchItem = Comment::findOne([
                            'id' => $file->object_id
                ]);
            }
            $query = Content::find();
            $query->andWhere([
                'content.object_id' => $searchItem->object_id,
                'content.object_model' => $searchItem->object_model
            ]);
            $content_file_wrapper[] = [
                'file' => $file,
                'content' => $query->one()
            ];
        }

        return $this->render('allPostedFiles', [
                    'contentContainer' => $this->contentContainer,
                    'items' => $content_file_wrapper
        ]);
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
                'cfiles_file.parent_folder_id' => self::ROOT_ID
            ]);
            $foldersQuery->andWhere([
                'cfiles_folder.parent_folder_id' => self::ROOT_ID
            ]);
        } else {
            $filesQuery->andWhere([
                'cfiles_file.parent_folder_id' => $folder->id
            ]);
            $foldersQuery->andWhere([
                'cfiles_folder.parent_folder_id' => $folder->id
            ]);
        }

        return $this->renderAjax('@humhub/modules/cfiles/views/browse/fileList', [
                    'items' => array_merge($foldersQuery->all(), $filesQuery->all()),
                    'contentContainer' => $this->contentContainer,
                    'crumb' => $this->generateCrumb(),
                    'errorMessages' => $this->errorMessages,
                    'folderId' => $folder === null ? self::ROOT_ID : $folder->id
        ]);
    }

    /**
     * Returns current folder by given fid get parameter.
     * If no or invalid folderId (fid) is given, null is returned.
     *
     * @return Folder
     */
    protected function getCurrentFolder()
    {
        if ($this->_currentFolder !== false) {
            return $this->_currentFolder;
        }

        $folder = null;
        $folderId = (int) Yii::$app->request->get('fid', self::ROOT_ID);
        if ($folderId !== self::ROOT_ID) {
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
    protected function generateCrumb()
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

    protected function getFolderList($parentId = self::ROOT_ID)
    {
        $dirstruc = [];
        $folders = Folder::find()->contentContainer($this->contentContainer)
                ->readable()
                ->where([
                    'cfiles_folder.parent_folder_id' => $parentId
                ])
                ->all();
        foreach ($folders as $folder) {
            $dirstruc[] = [
                'folder' => $folder,
                'subfolders' => $this->getFolderlist($folder->id)
            ];
        }

        return $dirstruc;
    }

    /**
     * Load all posted files from the database and get an array of them.
     * @return Ambigous <multitype:, multitype:\yii\db\ActiveRecord >
     */
    protected function getAllPostedFiles()
    {
        // Get Posted Files
        $query = \humhub\modules\file\models\File::find();
        $query->join('LEFT JOIN', 'comment', '(file.object_id=comment.id)');
        $query->join('LEFT JOIN', 'content', '(comment.object_model=content.object_model AND comment.object_id=content.object_id) OR (file.object_model=content.object_model AND file.object_id=content.object_id)');
        if (version_compare(Yii::$app->version, '1.1', 'lt')) {
            if ($this->contentContainer instanceof \humhub\modules\user\models\User) {
                $query->andWhere(['content.user_id' => $this->contentContainer->id]);
                $query->andWhere(['IS', 'content.space_id', new \yii\db\Expression('NULL')]);
            } else {
                $query->andWhere(['content.space_id' => $this->contentContainer->id]);
            }
        } else {
            $query->andWhere(['content.contentcontainer_id' => $this->contentContainer->contentContainerRecord->id]);
        }
        $query->andWhere([
            '<>',
            'file.object_model',
            File::className()
        ]);
        $query->orderBy([
            'file.updated_at' => SORT_DESC
        ]);
        // Get Files from comments
        return $query->all();
    }

    /**
     * Checks if user can write
     * 
     * @return boolean current user can write/upload/delete files
     */
    public function canWrite()
    {
        if (version_compare(Yii::$app->version, '1.1', 'lt')) {
            if ($this->contentContainer instanceof \humhub\modules\user\models\User) {
                if ($this->contentContainer->id === Yii::$app->user->getIdentity()->id) {
                    return true;
                }
            }
        }

        return $this->contentContainer->permissionManager->can(new \humhub\modules\cfiles\permissions\WriteAccess());

        return false;
    }

}
