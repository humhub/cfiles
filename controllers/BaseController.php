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

    private $_currentFolder = null;

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
        if ($this->_currentFolder === null) {
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
                    if ($this->_currentFolder === null) {
                        throw new HttpException(500, Yii::t('CfilesModule.base', 'An internal error occurred. Could not find folder with id: %id%', [
                            '%id%' => $folderId
                        ]));
                    }
            }
        }
        
        return $this->_currentFolder;
    }

    protected function getRootFolder()
    {
        if ($this->_rootFolder === null) {
            $this->_rootFolder = Folder::find()->contentContainer($this->contentContainer)
                ->readable()
                ->where([
                'type' => Folder::TYPE_FOLDER_ROOT
            ])
                ->one();
        }
        if ($this->_rootFolder === null) {
            throw new HttpException(500, Yii::t('CfilesModule.base', 'An internal error occurred. Could not load root folder, database not properly initialized.'));
        }
        return $this->_rootFolder;
    }

    protected function getAllPostedFilesFolder()
    {
        if ($this->_allPostedFilesFolder === null) {
            $this->_allPostedFilesFolder = Folder::find()->contentContainer($this->contentContainer)
                ->readable()
                ->where([
                'type' => Folder::TYPE_FOLDER_POSTED,
                'parent_folder_id' => $this->getRootFolder()->id
            ])
                ->one();
        }
        if ($this->_allPostedFilesFolder === null) {
            throw new HttpException(500, Yii::t('CfilesModule.base', 'An internal error occurred. Could not load default folder containing all posted files, database not properly initialized.'));
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
        $parent = $this->getCurrentFolder();
        do {
            $crumb[] = $parent;
            $parent = $parent->parentFolder;
        } while ($parent != null);
        return array_reverse($crumb);
    }

    /**
     * Generate the directory structure originating from a given folder id.
     *
     * @param int $parentId            
     * @return array [['folder' => --current folder--, 'subfolders' => [['folder' => --current folder--, 'subfolders' => []], ...], ['folder' => --current folder--, 'subfolders' => [['folder' => --current folder--, 'subfolders' => []], ...], ...]
     */
    protected function getFolderList($parentId = self::ROOT_ID, $orderBy = ['title' => SORT_ASC])
    {
        // map 0 to this containers root folder id
        if ($parentId === self::ROOT_ID) {
            $parentId = $this->getRootFolder()->id;
        }
        $dirstruc = [];
        $foldersQuery = Folder::find()->contentContainer($this->contentContainer)->readable();
        $foldersQuery->andWhere([
            'cfiles_folder.parent_folder_id' => $parentId
        ]);
        // do not return any subfolders here that are root or allpostedfiles
        $foldersQuery->andWhere([
            'or',
            [
                'cfiles_folder.type' => null
            ],
            [
                'and',
                [
                    '<>',
                    'cfiles_folder.type',
                    Folder::TYPE_FOLDER_POSTED
                ],
                [
                    '<>',
                    'cfiles_folder.type',
                    Folder::TYPE_FOLDER_ROOT
                ]
            ]
        ]);
        $foldersQuery->orderBy($orderBy);
        $folders = $foldersQuery->all();
        foreach ($folders as $folder) {
            $dirstruc[] = [
                'folder' => $folder,
                'subfolders' => $this->getFolderlist($folder->id)
            ];
        }
        
        return $dirstruc;
    }

    /**
     * Load all files and folders of the current folder from the database and get an array of them.
     *
     * @param array $orderBy
     *            orderBy array appended to the query
     * @return Ambigous <multitype:, multitype:\yii\db\ActiveRecord >
     */
    protected function getItemsList($orderBy = ['title' => SORT_ASC])
    {
        $filesQuery = File::find()->joinWith('baseFile')
            ->contentContainer($this->contentContainer)
            ->readable();
        $foldersQuery = Folder::find()->contentContainer($this->contentContainer)->readable();
        $specialFoldersQuery = Folder::find()->contentContainer($this->contentContainer)->readable();
        $filesQuery->andWhere([
            'cfiles_file.parent_folder_id' => $this->getCurrentFolder()->id
        ]);
        // user maintained folders
        $foldersQuery->andWhere([
            'cfiles_folder.parent_folder_id' => $this->getCurrentFolder()->id
        ]);
        // do not return any folders here that are root or allpostedfiles
        $foldersQuery->andWhere([
            'or',
            [
                'cfiles_folder.type' => null
            ],
            [
                'and',
                [
                    '<>',
                    'cfiles_folder.type',
                    Folder::TYPE_FOLDER_POSTED
                ],
                [
                    '<>',
                    'cfiles_folder.type',
                    Folder::TYPE_FOLDER_ROOT
                ]
            ]
        ]);
        // special default folders like the allposted files folder
        $specialFoldersQuery->andWhere([
            'cfiles_folder.parent_folder_id' => $this->getCurrentFolder()->id
        ]);
        $specialFoldersQuery->andWhere([
            'is not',
            'cfiles_folder.type',
            null
        ]);
        
        $filesQuery->orderBy($orderBy);
        $foldersQuery->orderBy($orderBy);
        return [
            'specialFolders' => $specialFoldersQuery->all(),
            'folders' => $foldersQuery->all(),
            'files' => $filesQuery->all()
        ];
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
