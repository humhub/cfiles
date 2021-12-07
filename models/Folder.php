<?php

namespace humhub\modules\cfiles\models;

use humhub\modules\content\components\ContentContainerActiveRecord;
use humhub\modules\content\models\Content;
use humhub\modules\file\libs\ImageHelper;
use humhub\modules\file\models\FileContent;
use humhub\modules\file\libs\FileHelper;
use humhub\modules\user\models\User;
use humhub\modules\search\events\SearchAddEvent;
use humhub\modules\space\models\Space;
use Yii;
use yii\db\ActiveQuery;
use yii\imagine\Image;
use yii\web\UploadedFile;

/**
 * This is the model class for table "cfiles_folder".
 *
 * @property integer $id
 * @property integer $parent_folder_id
 * @property string $title
 * @property string $description
 * @property string $type
 *
 * @property Folder parentFolder
 * @property Folder[] subFolders
 * @property Folder[] specialFolders
 * @property File[] subFiles
 *
 */
class Folder extends FileSystemItem
{

    const TYPE_FOLDER_ROOT = 'root';
    const TYPE_FOLDER_POSTED = 'posted';
    const ROOT_TITLE = 'Root';
    const ROOT_DESCRIPTION = 'The root folder is the entry point that contains all available files.';
    const ALL_POSTED_FILES_TITLE = 'Files from the stream';
    const ALL_POSTED_FILES_DESCRIPTION = 'You can find all files that have been posted to this stream here.';

    /**
     * @inheritdoc
     */
    public $wallEntryClass = "humhub\modules\cfiles\widgets\WallEntryFolder";

    /**
     * @inheritdoc
     */
    public $streamChannel = null;

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'cfiles_folder';
    }

    /**
     * @inheritdoc
     */
    public function getContentName()
    {
        return Yii::t('CfilesModule.base', "Folder");
    }

    /**
     * @inheritdoc
     */
    public function getIcon()
    {
        return'fa-folder';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {

        $result = array_merge(parent::rules(), [
            ['parent_folder_id', 'integer'],
            ['parent_folder_id', 'validateParentFolderId'],
            ['title', 'required'],
            ['title', 'trim'],
            ['title', 'string', 'min' => 1, 'max' => 255],
            ['title', 'noSpaces'],
            ['description', 'string', 'max' => 255],
            ['title', 'uniqueTitle']
        ]);

        if (!$this->isRoot()) {
            $result[] = ['parent_folder_id', 'required'];
        }

        return $result;
    }

    /**
     * Makes sure that after an title change there is no equal title for the given container in the given parent folder.
     *
     * @param string $attribute
     * @param array $params
     * @param string $validator
     */
    public function uniqueTitle($attribute, $params, $validator)
    {
        if ($this->isRoot() || !$this->hasTitleChanged()) {
            return;
        }

        if ($this->parentFolder->folderExists($this->title)) {
            $this->addError('title', \Yii::t('CfilesModule.base', 'A folder with this name already exists.'));
        }
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return array_merge(parent::attributeLabels(), [
            'id' => 'ID',
            'parent_folder_id' => Yii::t('CfilesModule.models_Folder', 'Parent Folder ID'),
            'title' => Yii::t('CfilesModule.models_Folder', 'Title'),
            'description' => Yii::t('CfilesModule.models_Folder', 'Description')
        ]);
    }

    /**
     * @inheritdoc
     */
    public function attributeHints()
    {
        if (!$this->isNewRecord) {
            return ['visibility' => Yii::t('CfilesModule.models_FileSystemItem', 'Note: Changes of the folders visibility, will be inherited by all contained files and folders.')];
        }
        return parent::attributeHints();
    }

    /**
     * @inheritdoc
     */
    public function getSearchAttributes()
    {
        if ($this->isAllPostedFiles() || $this->isRoot()) {
            $attributes = [];
        } else {
            $attributes = [
                'name' => $this->title,
                'description' => $this->description
            ];

            if($this->getCreator()) {
                $attributes['creator'] = $this->getCreator()->getDisplayName();
            }

            if($this->getEditor()) {
                $attributes['editor'] = $this->getEditor()->getDisplayName();
            }
        }
        $this->trigger(self::EVENT_SEARCH_ADD, new SearchAddEvent($attributes));
        return $attributes;
    }

    /**
     * @inheritdoc
     */
    public function beforeSave($insert)
    {
        if ($insert && $this->visibility !== null) {
            $this->content->visibility = $this->visibility;
        } else if ($this->visibility !== null && $this->visibility != $this->content->visibility) {
            $this->updateVisibility($this->visibility);
        }
        return parent::beforeSave($insert);
    }

    /**
     * @inheritdoc
     */
    public function afterMove(ContentContainerActiveRecord $container = null)
    {
        parent::afterMove($container);

        // Move all sub folders and files into the same Container where this Folder has been moved to
        $this->moveSubFoldersToContainer($container);
        $this->moveSubFilesToContainer($container);
    }

    public function moveSubFoldersToContainer(ContentContainerActiveRecord $container = null)
    {
        if ($container === null) {
            $container = $this->content->getContainer();
        }

        $folders = Folder::find()
            ->andWhere(['parent_folder_id' => $this->id])
            ->all();

        foreach ($folders as $folder) {
            /* @var Folder $folder */
            $folder->move($container);
        }
    }

    public function moveSubFilesToContainer(ContentContainerActiveRecord $container = null)
    {
        if ($container === null) {
            $container = $this->content->getContainer();
        }

        $files = File::find()
            ->joinWith('baseFile')
            ->andWhere(['cfiles_file.parent_folder_id' => $this->id])
            ->all();

        foreach ($files as $file) {
            /* @var File $file */
            $file->move($container);
        }
    }

    /**
     * @param $visibility
     */
    public function updateVisibility($visibility)
    {
        if ($visibility === null) {
            return;
        }

        $this->content->visibility = $visibility;

        foreach ($this->getSubFiles() as $file) {
            $file->content->visibility = $visibility;
            $file->content->save();
        }

        foreach ($this->getSubFolders() as $folder) {
            $folder->updateVisibility($visibility);
            $folder->content->save();
        }
    }

    /**
     * @inheritdoc
     */
    public function beforeDelete()
    {
        foreach ($this->folders as $folder) {
            $folder->delete();
        }

        foreach ($this->files as $file) {
            $file->delete();
        }

        return parent::beforeDelete();
    }

    public function getVisibilityTitle()
    {
        if (Yii::$app->getModule('friendship')->getIsEnabled() && $this->content->container instanceof User) {
            if ($this->content->container->isCurrentuser()) {
                $privateText =  Yii::t('CfilesModule.base', 'This folder is only visible for you and your friends.');
            } else {
                $privateText =  Yii::t('CfilesModule.base', 'This folder is protected.');
            }

            return $this->content->isPublic() ? Yii::t('CfilesModule.base', 'This folder is public.') : $privateText;
        }

        return $this->content->isPublic() ? Yii::t('CfilesModule.base', 'This folder is public.') : Yii::t('CfilesModule.base', 'This folder is private.');
    }

    /**
     * In older versions there was no actual root folder, all root files and folders had parent_folder_id 0 or null.
     * This function can be executed for newly created root folders and will move all files/folders to the new root.
     */
    public function migrateFromOldStructure()
    {
        if (!$this->isRoot()) {
            return;
        }

        $filesQuery = File::find()->joinWith('baseFile')->contentContainer($this->content->container)
            ->andWhere(['OR', ['IS', 'cfiles_file.parent_folder_id', new \yii\db\Expression('NULL')], ['cfiles_file.parent_folder_id' => 0]]);

        $foldersQuery = Folder::find()->contentContainer($this->content->container)
            ->andWhere(['OR', ['IS', 'cfiles_folder.parent_folder_id', new \yii\db\Expression('NULL')], ['cfiles_folder.parent_folder_id' => 0]])
            ->andWhere(['IS', 'cfiles_folder.type', new \yii\db\Expression('NULL')]);

        $rootsubfiles = $filesQuery->all();
        $rootsubfolders = $foldersQuery->all();

        foreach ($rootsubfiles as $file) {
            $file->parent_folder_id = $this->id;
            $file->save();
        }

        foreach ($rootsubfolders as $folder) {
            $folder->parent_folder_id = $this->id;
            $folder->save();
        }
    }

    /**
     * Initializes a root folder for the given $contentContainer
     * @param ContentContainerActiveRecord $contentContainer
     * @return Folder|boolean
     */
    public static function initRoot(ContentContainerActiveRecord $contentContainer)
    {
        if (!empty(self::getRoot($contentContainer))) {
            return false;
        }

        $root = new self($contentContainer, Content::VISIBILITY_PUBLIC, [
            'type' => self::TYPE_FOLDER_ROOT,
            'title' => self::ROOT_TITLE,
            'description' => self::ROOT_DESCRIPTION
        ]);

        $root->content->created_by = self::getContainerOwnerId($contentContainer);
        $root->silentContentCreation = true;
        if ($root->save()) {
            return $root;
        }

        return false;
    }

    /**
     * Initializes the posted files folder for the given $contentContainer
     * @param ContentContainerActiveRecord $contentContainer
     * @return bool|Folder
     */
    public static function initPostedFilesFolder(ContentContainerActiveRecord $contentContainer)
    {
        $root = self::getRoot($contentContainer);

        if (!$root || !empty(self::getPostedFilesFolder($contentContainer))) {
            return false;
        }

        $postedFilesFolder = new self($contentContainer, Content::VISIBILITY_PUBLIC, [
            'type' => self::TYPE_FOLDER_POSTED,
            'title' => self::ALL_POSTED_FILES_TITLE,
            'description' => self::ALL_POSTED_FILES_DESCRIPTION,
            'parent_folder_id' => $root->id,
        ]);

        $postedFilesFolder->content->created_by = self::getContainerOwnerId($contentContainer);
        $postedFilesFolder->silentContentCreation = true;
        if ($postedFilesFolder->save()) {
            return $postedFilesFolder;
        }

        return false;
    }

    /**
     * Generate the maximum depth directory structure originating from a given folder id.
     *
     * @param Folder $parentId
     * @return array [['folder' => --current folder--, 'subfolders' => [['folder' => --current folder--, 'subfolders' => []], ...], ['folder' => --current folder--, 'subfolders' => [['folder' => --current folder--, 'subfolders' => []], ...], ...]
     */
    public static function getFolderList($parent, $orderBy = ['title' => SORT_ASC])
    {
        $parentId = ($parent instanceof Folder) ? $parent->id : $parent;

        $dirStruc = [];
        foreach (self::getSubFoldersByParent($parent, $orderBy)->all() as $folder) {
            $dirStruc[] = ['folder' => $folder, 'subfolders' => self::getFolderlist($folder, $orderBy)];
        }

        return $dirStruc;
    }

    /**
     * Returns all readable subfolders of the given parent folder.
     *
     * @param $contentContainer
     * @param Folder $parent
     * @param array $orderBy
     * @return ActiveQuery
     */
    public static function getSubFoldersByParent($parent, $orderBy = ['title' => SORT_ASC])
    {
        $query = Folder::find()->contentContainer($parent->content->container)->readable();
        $query->andWhere(['cfiles_folder.parent_folder_id' => $parent->id]);

        // do not return any subfolders here that are root or allpostedfiles
        $query->andWhere([
            'or',
            ['cfiles_folder.type' => null],
            ['and',
                ['<>', 'cfiles_folder.type', Folder::TYPE_FOLDER_POSTED],
                ['<>', 'cfiles_folder.type', Folder::TYPE_FOLDER_ROOT]
            ]
        ]);

        return $query->orderBy($orderBy);
    }

    /**
     * @param ContentContainerActiveRecord $contentContainer
     * @return int
     */
    private static function getContainerOwnerId(ContentContainerActiveRecord $contentContainer)
    {
        if ($contentContainer instanceof User) {
            return $contentContainer->id;
        } else if ($contentContainer instanceof Space) {
            return $contentContainer->created_by;
        }

        return null;
    }

    /**
     * @param ContentContainerActiveRecord $contentContainer
     * @return Folder the root folder of the given ContentContainerActiveRecord
     */
    public static function getOrInitRoot(ContentContainerActiveRecord $contentContainer)
    {
        if ($root = Folder::getRoot($contentContainer)) {
            return $root;
        }

        return Folder::initRoot($contentContainer);
    }

    /**
     * @param ContentContainerActiveRecord $contentContainer
     * @return Folder the root folder of the given ContentContainerActiveRecord
     */
    public static function getRoot(ContentContainerActiveRecord $contentContainer)
    {
        return self::find()->contentContainer($contentContainer)->andWhere(['type' => self::TYPE_FOLDER_ROOT])->one();
    }

    /**
     * @param ContentContainerActiveRecord $contentContainer
     * @return Folder the root folder of the given ContentContainerActiveRecord
     */
    public static function getPostedFilesFolder(ContentContainerActiveRecord $contentContainer)
    {
        return self::find()->contentContainer($contentContainer)->andWhere(['type' => self::TYPE_FOLDER_POSTED])->one();
    }

    /**
     * @return ActiveQuery of all direct child files
     */
    public function getFiles()
    {
        return $this->hasMany(File::className(), ['parent_folder_id' => 'id'])
                        ->joinWith('baseFile')
                        ->orderBy(['title' => SORT_ASC]);
    }

    /**
     * @return ActiveQuery of all direct child folders
     */
    public function getFolders()
    {
        return $this->hasMany(Folder::className(), ['parent_folder_id' => 'id'])->orderBy(['title' => SORT_ASC]);
    }

    /**
     * @return boolean
     */
    public function hasTitleChanged()
    {
        return $this->isNewRecord || $this->getOldAttribute('title') != $this->title;
    }

    /**
     * @return string
     */
    public function getItemId()
    {
        return $this->getItemType() . '_' . $this->id;
    }

    /**
     * @inheritdoc
     */
    public function getContentId()
    {
        return $this->content->id;
    }

    /**
     * @return string
     */
    public function getItemType()
    {
        return 'folder' . ($this->type !== null ? '-' . $this->type : '');
    }

    public function getTitle()
    {
        if ($this->isRoot()) {
            return  Yii::t('CfilesModule.base', 'Root');
        } else if ($this->isAllPostedFiles()) {
            return  Yii::t('CfilesModule.base', 'Files from the stream');
        }

        return $this->title;
    }

    public function getDescription()
    {
        if ($this->isRoot()) {
            return  Yii::t('CfilesModule.base', 'The root folder is the entry point that contains all available files.');
        } else if ($this->isAllPostedFiles()) {
            return  Yii::t('CfilesModule.base', 'You can find all files that have been posted to this stream here.');
        }

        return $this->description;
    }

    /**
     * @return string
     */
    public function getDownloadCount()
    {
        return '';
    }

    public function getSize()
    {
        return 0;
    }

    public function createUrl($route = null, $params = [], $scheme = false)
    {
        $params = (is_array($params)) ? $params : [];
        $params['fid'] = $this->id;
        return $this->content->container->createUrl($route, $params, $scheme);
    }

    public function getUrl()
    {
        if (empty($this->content->container)) {
            return "";
        }

        return $this->content->container->createUrl('/cfiles/browse/index', ['fid' => $this->id]);
    }

    public function getFullUrl()
    {
        if (empty($this->content->container)) {
            return "";
        }

        return $this->content->container->createUrl('/cfiles/browse/index', ['fid' => $this->id], true);
    }

    public function getEditUrl()
    {
        return $this->content->container->createUrl('/cfiles/edit/folder', ['id' => $this->getItemId()]);
    }

    public function noSpaces($attribute, $params)
    {
        if (trim($this->$attribute) !== $this->$attribute) {
            $this->addError($attribute, Yii::t('CfilesModule.base', 'Folder should not start or end with blank space.'));
        }
    }

    public function getFullPath($separator = '/')
    {
        return $this->getPathFromId($this->id, false, $separator);
    }

    public static function getPathFromId($id, $parentFolderPath = false, $separator = '/', $withRoot = false)
    {
        if ($id == 0) {
            return $separator;
        }
        $item = Folder::findOne([
                    'id' => $id
        ]);
        if (empty($item)) {
            return null;
        }
        $tempFolder = $item->parentFolder;
        $path = '';
        if (!$parentFolderPath) {
            if ($item->isRoot()) {
                if ($withRoot) {
                    $path .= $item->title;
                }
            } else {
                $path .= $separator . $item->title;
            }
        }
        $counter = 0;
        // break at maxdepth to avoid hangs
        while (!empty($tempFolder)) {
            if ($tempFolder->isRoot()) {
                if ($withRoot) {
                    $path = $tempFolder->title . $path;
                }
                break;
            } else {
                if (++$counter > 10) {
                    $path = '...' . $path;
                    break;
                }
                $path = $separator . $tempFolder->title . $path;
            }

            $tempFolder = $tempFolder->parentFolder;
        }
        return $path;
    }

    /**
     * Returns the folder path as ordered array.
     * @return Folder[]
     */
    public function getCrumb()
    {
        $parent = $this;
        do {
            $crumb[] = $parent;
            $parent = $parent->parentFolder;
        } while ($parent != null);
        return array_reverse($crumb);
    }

    /**
     * @inheritdoc
     */
    public function getContentDescription()
    {
        return $this->title;
    }

    public function isRoot()
    {
        return $this->type === self::TYPE_FOLDER_ROOT;
    }

    public function isAllPostedFiles()
    {
        return $this->type === self::TYPE_FOLDER_POSTED;
    }

    /**
     * Validate parent folder id
     *
     * @param string $attribute the attribute name
     */
    public function validateParentFolderId($attribute = 'parent_folder_id')
    {
        $parent = $this->parentFolder;

        // check if one of the parents is oneself to avoid circles
        while (!empty($parent)) {
            if ($this->id == $parent->id) {
                $this->addError($attribute, Yii::t('CfilesModule.base', 'Please select a valid destination folder for %title%.', ['%title%' => $this->title]));
                break;
            }
            $parent = static::findOne(['id' => $parent->parent_folder_id]);
        }

        parent::validateParentFolderId($attribute);
    }

    /**
     * @return FileSystemItem[] return all child folders and child files excluding special folders
     */
    public function getChildren()
    {
        return array_merge($this->getSubFolders(), $this->getSubFiles());
    }

    /**
     * @param array $order
     * @return Folder[]
     */
    public function getSpecialFolders()
    {
        $specialFoldersQuery = Folder::find()->contentContainer($this->content->container)->readable();
        $specialFoldersQuery->andWhere(['cfiles_folder.parent_folder_id' => $this->id]);
        $specialFoldersQuery->andWhere(['is not', 'cfiles_folder.type', null]);
        return $specialFoldersQuery->all();
    }

    /**
     * @return Folder[]
     */
    public function getSubFolders($order = 'title ASC')
    {
        return self::getSubFoldersByParent($this, $order)->all();
    }

    /**
     * @param null $order
     * @return File[]
     */
    public function getSubFiles($order = 'file.file_name ASC')
    {
        $filesQuery = File::find()->joinWith('baseFile')->contentContainer($this->content->container)->readable();
        $filesQuery->andWhere(['cfiles_file.parent_folder_id' => $this->id]);
        $filesQuery->orderBy($order);
        return $filesQuery->all();
    }

    /**
     * Creates and adds the given UploadedFile to this directory.
     *
     * Returns the newly created cfiles file.
     * The calling function has to make sure there are no errors by checking_
     *
     * ```php
     * $file->hasErrors()
     * ```
     * and
     *
     * ```php
     * $file->baseFile->hasErrors();
     * ```
     * @param UploadedFile $uploadedFile
     * @return File
     */
    public function addUploadedFile(UploadedFile $uploadedFile): File
    {
        // Get file instance either an existing one or a new one
        $file = $this->getFileInstance($uploadedFile);

        if ($file->setUploadedFile($uploadedFile)) {
            $file->save();
        }

        return $file;
    }

    /**
     * @param UploadedFile $uploadedFile
     * @return File
     */
    private function getFileInstance(UploadedFile $uploadedFile): File
    {
        if ($file = $this->findFileByName($uploadedFile->name)) {
            return $file;
        }

        return new File($this->content->container, $this->getNewItemVisibility(), [
            'parent_folder_id' => $this->id
        ]);
    }

    private function getNewItemVisibility()
    {
        if ($this->isRoot()) {
            return $this->content->container->getDefaultContentVisibility();
        }

        return $this->content->visibility;
    }

    public function addFileFromPath($filename, $filePath)
    {
        $file = new File($this->content->container, $this->getNewItemVisibility(), [
            'parent_folder_id' => $this->id
        ]);

        $fileContent = new FileContent([
            'mime_type' => FileHelper::getMimeType($filePath),
            'size' => filesize($filePath),
            'show_in_stream' => 0,
            'file_name' => $this->getAddedFileName($filename)
        ]);

        if ($fileContent->mime_type == 'image/jpeg') {
            $image = Image::getImagine()->open($filePath);
            ImageHelper::fixJpegOrientation($image, $filePath);
        }

        $fileContent->newFileContent = stream_get_contents(fopen($filePath, 'r'));

        $file->setFileContent($fileContent);
        $file->save();

        return $file;
    }

    /**
     * Creates a new non persisted folder within this folder.
     *
     * @param string|null $title
     * @param string|null $description
     * @return Folder
     */
    public function newFolder($title = null, $description = null)
    {
        return new self($this->content->container, $this->getNewItemVisibility(), [
            'parent_folder_id' => $this->id,
            'title' => $title,
            'description' => $description]);
    }

    /**
     * Moves the given item into this folder.
     *
     * This method checks for duplicate file/folder names.
     *
     * If a file with the same title already exists we use a file name index e.g. file(1).txt
     *
     * If a folder already exists with the same title we merge all sub items into the existing folder
     *
     *
     * @param FileSystemItem $item
     * @return bool
     */
    public function moveItem(FileSystemItem $item)
    {
        if (!$item) {
            return false;
        }

        if (!$item->canEdit()) {
            if ($item instanceof File) {
                $item->addError($item->getTitle(), Yii::t('CfilesModule.base', 'You cannot move the file "{name}"!', ['name' => $item->getTitle()]));
            } else {
                $item->addError($item->getTitle(), Yii::t('CfilesModule.base', 'You cannot move the folder "{name}"!', ['name' => $item->getTitle()]));
            }
            return false;
        }

        if ($item instanceof Folder && !$item->isEditableFolder()) {
            $item->addError($item->getTitle(), Yii::t('CfilesModule.base', 'Folder {name} given folder is not editable!', ['name' => $item->getTitle()]));
            return false;
        }

        if ($item->getItemId() === $this->getItemId()) {
            $item->addError($item->getTitle(), Yii::t('CfilesModule.base', 'Folder {name} can\'t be moved to itself!', ['name' => $item->getTitle()]));
            return false;
        }

        // We ignore invalid items and items already residing in the given destination
        if ($item->hasParent($this) || $item->is($this)) {
            return true;
        }

        // Note we don't set the content visibility directly to run recursive visibility change in folders
        $item->visibility = $this->content->visibility;
        $item->parent_folder_id = $this->id;

        $moveResult = $this->checkForDuplicate($item);

        if (!$moveResult) {
            // Probably an error when moving subfiles to an existing folder
            return false;
        }

        if ($item->is($moveResult)) {
            // Either no duplicate or just simple file rename
            return $moveResult->save();
        }

        // Successfully moved subfiles to existing folder with same title
        return true;
    }

    /**
     * Checks if the given $item title already exists in this folder and renames already existing files or
     * merges already existing folders.
     *
     * This method returns either the item itself in case there was no duplicate or a file duplicate (which was renamed)
     * or the already existing folder in case the item is a folder with the same title as an existing sub folder,
     * or null in case there was an error when moving files to an existing subfolder.
     *
     * @param FileSystemItem $item
     * @return FileSystemItem|null
     */
    private function checkForDuplicate(FileSystemItem $item)
    {
        $result = null;
        if ($item instanceof File) {
            $item->setTitle($this->getAddedFileName($item->getTitle()));
            $result = $item;
        } else if ($item instanceof Folder) {
            $result = $item;

            $existingFolderWithTitle = $this->findFolderByName($item->title);

            // Check if the folder exists if not, move children to existing subfolder, if there is an error we set §result to null
            if ($existingFolderWithTitle && !$existingFolderWithTitle->is($item)) {
                $result = $existingFolderWithTitle;
                foreach ($item->getChildren() as $child) {
                    // if moving the given item fails we set result to null and add an item error
                    if (!$existingFolderWithTitle->moveItem($child)) {
                        $result = null;
                        foreach ($child->getErrors() as $attribute => $errors) {
                            $item->addErrors([$child->getTitle() => $errors]);
                        }
                    };
                }

                if ($result) {
                    $item->delete();
                }
            }
        }

        return $result;
    }

    /**
     * Searches for direct sub files with the given file name and returns an indexed file name in form of
     * myFile(<index>).txt in case an existing file was found, otherwise the original fileName is returned.
     *
     * @param $fileName
     * @return string either an indexed file name or original filename if no duplicate title was found.
     */
    protected function getAddedFileName($fileName)
    {
        $counter = 0;
        $parts = preg_split('~\.(?=[^\.]*$)~', $fileName);
        $origName = $parts[0];
        $ext = sizeof($parts) == 2 ? '.' . $parts[1] : '';

        while ($this->fileExists($fileName)) {
            $fileName = $origName . '(' . ++$counter . ')' . $ext;
        }

        return $fileName;
    }

    public function fileExists($name)
    {
        return File::find()->joinWith('baseFile')->where(['file_name' => $name, 'cfiles_file.parent_folder_id' => $this->id])->count();
    }

    public function folderExists($name)
    {
        return Folder::find()->where(['title' => $name, 'parent_folder_id' => $this->id])->count();
    }

    public function findFileByName($name): ?File
    {
        return File::find()->contentContainer($this->content->container)
            ->joinWith('baseFile')
            ->andWhere(['file_name' => $name])
            ->andWhere(['cfiles_file.parent_folder_id' => $this->id])
            ->one();
    }

    public function findFolderByName($name)
    {
        return Folder::find()->contentContainer($this->content->container)
            ->andWhere(['title' => $name, 'parent_folder_id' => $this->id])->one();
    }

    /**
     * @inheritdoc
     */
    public function getVersionsUrl(int $versionId = 0): ?string
    {
        return null;
    }

    /**
     * @inheritdoc
     */
    public function getDeleteVersionUrl(int $versionId): ?string
    {
        return null;
    }

}
