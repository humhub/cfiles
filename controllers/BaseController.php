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
 * Description of a Base Controller for the files module.
 *
 * @author Sebastian Stumpf
 */
abstract class BaseController extends \humhub\modules\content\components\ContentContainerController
{

    const ROOT_ID = 0;

    const All_POSTED_FILES_ID = - 1;

    private $_currentFolder = false;
    
    private $_rootFolder = null;
    
    private $_allPostedFilesFolder = null;

    public $hideSidebar = true;

    public $errorMessages = array();

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
        
        $this->_currentFolder = null;
        $folderId = (int) Yii::$app->request->get('fid', self::ROOT_ID);
               
        switch ($folderId) {
            case self::ROOT_ID:
                $this->_currentFolder = $this->getRootFolder();
                break;
            case self::All_POSTED_FILES_ID:
                $this->_currentFolder = $this->getAllPostedFilesFolder();
                break;
            default:
                $this->_currentFolder = Folder::find()->contentContainer($this->contentContainer)
                    ->readable()
                    ->where([
                    'cfiles_folder.id' => $folderId
                ])
                    ->one();
        }
        
        return $this->_currentFolder;
    }

    protected function getRootFolder() {
        if (empty($this->_rootFolder)) {
            $this->_rootFolder = new Folder();
            $this->_rootFolder->id = self::ROOT_ID;
            $this->_rootFolder->title = Yii::t('CfilesModule.base', 'root');
        }
        return $this->_rootFolder;
        
    }
    
    protected function getAllPostedFilesFolder() {
        if (empty($this->_allPostedFilesFolder)) {
            $this->_allPostedFilesFolder = new Folder();
            $this->_allPostedFilesFolder->id = self::All_POSTED_FILES_ID;
            $this->_allPostedFilesFolder->title = Yii::t('CfilesModule.base', 'Files from the stream');
            $this->_allPostedFilesFolder->parent_folder_id = $this->getRootFolder()->id;
        }
        return $this->_allPostedFilesFolder;
    }
    
    /**
     * Returns all parent folders as array
     *
     * @return array of parent folders
     */
    protected function generateCrumb()
    {
        $crumb = [];
        
        $crumb[] = $this->getRootFolder();
        if ($this->getCurrentFolder()->id == self::All_POSTED_FILES_ID) {
            $crumb[] = $this->getAllPostedFilesFolder();
        } elseif ($this->getCurrentFolder()->id != self::ROOT_ID) {
            if ($this->getCurrentFolder() !== null) {
                $temp = [];
                $temp[] = $this->getCurrentFolder();
                $parent = $this->getCurrentFolder()->parentFolder;
                while ($parent != null) {
                    $temp[] = $parent;
                    $parent = $parent->parentFolder;
                }
                $crumb = array_merge($crumb, array_reverse($temp));
            }       
        }        
        return $crumb;
    }

    /**
     * Generate the sirectory structure originating from a given folder id.
     *
     * @param int $parentId            
     * @return array [['folder' => --current folder--, 'subfolders' => [['folder' => --current folder--, 'subfolders' => []], ...], ['folder' => --current folder--, 'subfolders' => [['folder' => --current folder--, 'subfolders' => []], ...], ...]
     */
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
     * Get the post the file is connected to.
     */
    public function getBasePost($file = null) {
        if($file === null) {
            return null;
        }
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
        return $query->one();
    }
    
    /**
     * Checks if user can write
     *
     * @return boolean current user can write/upload/delete files
     */
    public function canWrite()
    {
        if ($this->contentContainer instanceof \humhub\modules\user\models\User) {
            if ($this->contentContainer->id === Yii::$app->user->getIdentity()->id) {
                return true;
            }
        }
        
        return $this->contentContainer->permissionManager->can(new \humhub\modules\cfiles\permissions\WriteAccess());
    }
}
