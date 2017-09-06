<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2015 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\cfiles\controllers;

use humhub\modules\content\models\Content;
use Yii;
use yii\db\Expression;
use yii\web\HttpException;
use humhub\modules\cfiles\permissions\WriteAccess;
use humhub\modules\content\components\ContentContainerController;
use humhub\modules\cfiles\models\File;
use humhub\modules\cfiles\models\Folder;

/**
 * Description of a Base Controller for the files module.
 *
 * @author Sebastian Stumpf
 */
abstract class BaseController extends ContentContainerController
{

    const ROOT_ID = 0;
    const All_POSTED_FILES_ID = -1;

    private $_currentFolder = null;
    private $_rootFolder = null;
    private $_allPostedFilesFolder = null;
    public $hideSidebar = true;
    public $errorMessages = [];

    public function beforeAction($action)
    {
        if (!parent::beforeAction($action)) {
            return false;
        }

        $newRoot = false;

        // create default folders
        if (!$this->getRootFolder()) {
            $this->_rootFolder = Folder::initRoot($this->contentContainer);
            $newRoot = true;
        } else if($this->getRootFolder()->content->isPrivate()) {
            // Make sure older root folders are public by default.
            $this->getRootFolder()->content->visibility = Content::VISIBILITY_PUBLIC;
            $this->getRootFolder()->content->save();
        }

        if ($this->getAllPostedFilesFolder() == null) {
            $this->_allPostedFilesFolder = Folder::initPostedFilesFolder($this->contentContainer);
        } else if($this->getAllPostedFilesFolder()->content->isPrivate()) {
            $this->getAllPostedFilesFolder()->content->visibility = Content::VISIBILITY_PUBLIC;
            $this->getAllPostedFilesFolder()->content->save();
        }

        // TODO: In a future version, we should handle this within a migration and remove the line
        // next step is to shift all former root subfiles which have parent_folder_id == 0 (up to module version v.9.7) to the generated root folder
        // this should not be a problem if the migration was broken, because it only affects entries with parent_folder_id==0
        if ($newRoot) {
            $this->_rootFolder->migrateFromOldStructure();
        }

        return true;
    }

    /**
     * Returns current folder by given fid get parameter.
     * If no or invalid folderId (fid) is given, null is returned.
     *
     * @return Folder
     */
    public function getCurrentFolder()
    {
        if ($this->_currentFolder === null) {
            $folderId = (int) Yii::$app->request->get('fid', self::ROOT_ID);

            switch ($folderId) {
                case self::ROOT_ID:
                    return $this->_currentFolder = $this->getRootFolder();
                case self::All_POSTED_FILES_ID:
                    return $this->_currentFolder = $this->getAllPostedFilesFolder();
                default:
                    $this->_currentFolder = Folder::find()->contentContainer($this->contentContainer)
                        ->readable()
                        ->where(['cfiles_folder.id' => $folderId])
                        ->one();
                    if ($this->_currentFolder === null) {
                        throw new HttpException(500, Yii::t('CfilesModule.base', 'Could not find folder with id: %id%', ['%id%' => $folderId]));
                    }
            }
        }

        return $this->_currentFolder;
    }

    protected function getRootFolder()
    {
        if ($this->_rootFolder === null) {
            $this->_rootFolder = Folder::getRoot($this->contentContainer);
        }
        return $this->_rootFolder;
    }

    protected function getAllPostedFilesFolder()
    {
        if ($this->_allPostedFilesFolder === null) {
            $this->_allPostedFilesFolder = Folder::getPostedFilesFolder($this->contentContainer);
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
     * Generate the maximum depth directory structure originating from a given folder id.
     *
     * @param int $parentId
     * @return array [['folder' => --current folder--, 'subfolders' => [['folder' => --current folder--, 'subfolders' => []], ...], ['folder' => --current folder--, 'subfolders' => [['folder' => --current folder--, 'subfolders' => []], ...], ...]
     */
    protected function getFolderList($parentId = self::ROOT_ID, $orderBy = NULL)
    {
        // set default value
        if (!$orderBy)
            $orderBy = ['title' => SORT_ASC];

        // map 0 to this containers root folder id
        if ($parentId === self::ROOT_ID) {
            $parentId = $this->getRootFolder()->id;
        }

        $dirstruc = [];
        $foldersQuery = Folder::find()->contentContainer($this->contentContainer)->readable();
        $foldersQuery->andWhere(['cfiles_folder.parent_folder_id' => $parentId]);

        // do not return any subfolders here that are root or allpostedfiles
        $foldersQuery->andWhere([
            'or',
            ['cfiles_folder.type' => null],
            ['and',
                ['<>', 'cfiles_folder.type', Folder::TYPE_FOLDER_POSTED],
                ['<>', 'cfiles_folder.type', Folder::TYPE_FOLDER_ROOT]
            ]
        ]);

        $foldersQuery->orderBy($orderBy);
        $folders = $foldersQuery->all();

        foreach ($folders as $folder) {
            $dirstruc[] = ['folder' => $folder, 'subfolders' => $this->getFolderlist($folder->id)];
        }

        return $dirstruc;
    }

    /**
     * Load all files and folders of the current folder from the database and get an array of them.
     *
     * @param array $filesOrder orderBy array appended to the files query
     * @param array $foldersOrder orderBy array appended to the folders query
     * @return array
     */
    protected function getItemsList($filesOrder = NULL, $foldersOrder = NULL)
    {
        // set default value
        if (!$filesOrder) {
            $filesOrder = ['title' => SORT_ASC];
        }
        if (!$foldersOrder) {
            $foldersOrder = ['title' => SORT_ASC];
        }

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
            ['cfiles_folder.type' => null],
            ['and',
                ['<>', 'cfiles_folder.type', Folder::TYPE_FOLDER_POSTED],
                ['<>', 'cfiles_folder.type', Folder::TYPE_FOLDER_ROOT]
            ]
        ]);
        // special default folders like the allposted files folder
        $specialFoldersQuery->andWhere(['cfiles_folder.parent_folder_id' => $this->getCurrentFolder()->id]);

        $specialFoldersQuery->andWhere(['is not', 'cfiles_folder.type', null]);

        $filesQuery->orderBy($filesOrder);
        $foldersQuery->orderBy($foldersOrder);

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
        return $this->contentContainer->permissionManager->can(WriteAccess::class);
    }

}
