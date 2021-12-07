<?php

namespace humhub\modules\cfiles\models;

use humhub\modules\cfiles\permissions\ManageFiles;
use humhub\modules\content\components\ContentContainerActiveRecord;
use humhub\modules\content\components\ContentActiveRecord;
use humhub\modules\content\models\Content;
use humhub\modules\user\models\User;
use humhub\modules\search\interfaces\Searchable;
use Yii;

/**
 * This is the model class for table "cfiles_file".
 *
 * @property integer $id
 * @property integer $parent_folder_id
 * @property string description
 */
abstract class FileSystemItem extends ContentActiveRecord implements ItemInterface, Searchable
{
    /**
     * @var int used for edit form
     */
    public $visibility;

    /**
     * @inheritdoc
     */
    public $managePermission = ManageFiles::class;

    /**
     * @inheritdocs
     */
    public $canMove = true;

    /**
     * @inheritdocs
     */
    public $moduleId = 'cfiles';

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            ['visibility', 'integer', 'min' => 0, 'max' => 1]
        ];
    }

    abstract function updateVisibility($visibility);
    abstract function getSize();
    abstract function getItemType();
    abstract function getDescription();
    abstract function getDownloadCount();
    abstract function getVisibilityTitle();

    /**
     * @return string
     */
    abstract public function getVersionsUrl(int $versionId = 0): ?string;

    /**
     * @return string
     */
    abstract public function getDeleteVersionUrl(int $versionId): ?string;

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'visibility' => Yii::t('CfilesModule.models_FileSystemItem', 'Is Public'),
            'download_count' => Yii::t('CfilesModule.models_FileSystemItem', 'Downloads'),
        ];
    }

    /**
     * @inheritdoc
     */
    public function afterFind() {
        $this->visibility = $this->content->visibility;
        parent::afterFind();
    }

    public function handleContentSave($evt, $content = null)
    {
        /* @var $content Content */
        $content = ($content) ? $content : $evt->sender;
        if($evt->sender->container instanceof User && $evt->sender->isPrivate()) {
            $evt->sender->visibility = Content::VISIBILITY_OWNER;
        }

        return true;
    }


    /**
     * @inheritdoc
     */
    public function beforeSave($insert)
    {
        if ($this->parent_folder_id == "") {
            $this->parent_folder_id = null;
        }

        return parent::beforeSave($insert);
    }

    /**
     * @inheritdoc
     */
    public function afterSave($insert, $changedAttributes)
    {
        // this should set the editor and edit date of all parent folders if sth. inside of them has changed
        if (!empty($this->parentFolder)) {
            $this->parentFolder->save();
            if($this->parentFolder->content->isPrivate() && $this->content->isPublic()) {
                $this->content->visibility = Content::VISIBILITY_PRIVATE;
                $this->content->save();
            }
        }

        parent::afterSave($insert, $changedAttributes);
    }

    /**
     * @inheritdoc
     */
    public function afterMove(ContentContainerActiveRecord $container = null)
    {
        parent::afterMove($container);
        $this->updateParentFolder();
    }

    /**
     * Update parent Folder if it is from different Content Container(Space/User)
     * This File/Folder will be moved into the root Folder of the current Content Container
     *
     * @param ContentContainerActiveRecord $container
     * @return bool True on success moving or if parent Folder is already in the same Content Container
     */
    public function updateParentFolder(): bool
    {
        $parentFolder = Folder::findOne(['id' => $this->parent_folder_id]);
        if ($parentFolder && $parentFolder->content->contentcontainer_id == $this->content->contentcontainer_id) {
            return true;
        }

        if (!($root = Folder::getOrInitRoot($this->content->getContainer()))) {
            return false;
        }

        $this->parent_folder_id = $root->id;
        return $this->save();
    }

    public function hasAttributeChanged($attributeName)
    {
        return $this->hasAttribute($attributeName) && ($this->isNewRecord || $this->getOldAttribute($attributeName) != $this->$attributeName);
    }

    public function is(FileSystemItem $item)
    {
        return $this->getItemId() === $item->getItemId();
    }

    public function hasParent(FileSystemItem $folder)
    {
        return $folder instanceof Folder && $folder->id === $this->parent_folder_id;
    }
    
    /**
     * @inheritdoc
     */
    public function getParentFolder()
    {
        $query = $this->hasOne(Folder::className(), [
            'id' => 'parent_folder_id'
        ]);
        return $query;
    }

    /**
     * @inheritdoc
     */
    public function getWallUrl()
    {
        return $this->getUrl();
    }

    /**
     * Returns the base content
     * 
     * @return \yii\db\ActiveQuery
     */
    public function getBaseContent()
    {
        $query = $this->hasOne(\humhub\modules\content\models\Content::className(), ['object_id' => 'id']);
        $query->andWhere(['file.object_model' => self::className()]);
        return $query;
    }

    /**
     * Check if a parent folder is valid or lies in itsself, etc.
     * 
     * @param string $attribute the parent folder attribute to validate
     */
    public function validateParentFolderId($attribute = 'parent_folder_id')
    {
        if ($this->parent_folder_id != 0 && !($this->parentFolder instanceof Folder)) {
            $this->addError($attribute, Yii::t('CfilesModule.base', 'Please select a valid destination folder for %title%.', ['%title%' => $this->title]));
        }
    }

    /**
     * @return User
     */
    public function getCreator()
    {
        return $this->content->createdBy;
    }

    /**
     * @return User
     */
    public function getEditor()
    {
        return $this->content->updatedBy;
    }

    /**
     * Determines this item is an editable folder.
     * 
     * @param \humhub\modules\cfiles\models\FileSystemItem $item
     * @return boolean
     */
    public function isEditableFolder()
    {
        // TODO: not that clean...
        return ($this instanceof Folder) && !($this->isRoot() || $this->isAllPostedFiles());
    }

    /**
     * Determines if this item is deletable. The root folder and posted files folder is not deletable.
     * @return boolean
     */
    public function isDeletable()
    {
        if ($this instanceof Folder) {
            return !($this->isRoot() || $this->isAllPostedFiles());
        }
        return true;
    }

    /**
     * Returns a FileSystemItem instance by the given item id of form {type}_{id}
     * 
     * @param string $itemId item id of form {type}_{id}
     * @return FileSystemItem
     */
    public static function getItemById($itemId)
    {
        $params = explode('_', $itemId);

        if (sizeof($params) < 2) {
            return null;
        }

        list ($type, $id) = explode('_', $itemId);
        if ($type == 'file') {
            return File::find()->andWhere(['cfiles_file.id' => $id])->readable()->one();
        } elseif ($type == 'folder') {
            return Folder::find()->andWhere(['cfiles_folder.id' => $id])->readable()->one();
        }

        //elseif ($type == 'baseFile') {
        //    return File::findOne(['id' => $id]);
        //}

        return null;
    }

    public function canEdit(): bool
    {
        // Fixes race condition on newly created files (import vs. onlyoffice)
        if ($this->content->container === null && $this->content->isNewRecord) {
            return true;
        }

        if ($this->content->container->permissionManager->can(new ManageFiles())) {
            return true;
        }

        return false;
    }

}