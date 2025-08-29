<?php

namespace humhub\modules\cfiles\models;

use humhub\modules\cfiles\Module;
use humhub\modules\cfiles\permissions\ManageFiles;
use humhub\modules\cfiles\permissions\WriteAccess;
use humhub\modules\content\components\ContentContainerActiveRecord;
use humhub\modules\content\components\ContentActiveRecord;
use humhub\modules\content\models\Content;
use humhub\modules\user\models\User;
use humhub\modules\search\interfaces\Searchable;
use Yii;

/**
 * This is the model class for table "cfiles_file".
 *
 * @property int $id
 * @property int $parent_folder_id
 * @property string description
 *
 * @property-read Folder|null $parentFolder
 */
abstract class FileSystemItem extends ContentActiveRecord implements ItemInterface, Searchable
{
    /**
     * @var int used for edit form
     */
    public $visibility;

    /**
     * @var ?int used for edit form
     */
    public $hidden = null;

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
            ['visibility', 'integer', 'min' => 0, 'max' => 1],
            ['hidden', 'boolean'],
        ];
    }

    abstract public function updateVisibility($visibility);

    abstract public function getSize();

    abstract public function getItemType();

    abstract public function getDescription();

    abstract public function getDownloadCount();

    abstract public function getVisibilityTitle();

    /**
     * @return string
     */
    abstract public function getVersionsUrl(int $versionId = 0): ?string;

    /**
     * @return string
     */
    abstract public function getDeleteVersionUrl(int $versionId): ?string;

    /**
     * Rename a conflicted Item with same name
     *
     * @return bool
     */
    abstract public function renameConflicted(): bool;

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'visibility' => Yii::t('CfilesModule.base', 'Is Public'),
            'hidden' => Yii::t('CfilesModule.base', 'Hide in Stream'),
            'download_count' => Yii::t('CfilesModule.base', 'Downloads'),
        ];
    }

    /**
     * @inheritdoc
     */
    public function afterFind()
    {
        $this->visibility = $this->content->visibility;
        $this->hidden = $this->content->hidden;
        parent::afterFind();
    }

    /**
     * @inheritdoc
     */
    public function beforeSave($insert)
    {
        if ($this->parent_folder_id == "") {
            $this->parent_folder_id = null;
        }

        if ($insert && $this->hidden === null) {
            /** @var Module $module */
            $module = Yii::$app->getModule('cfiles');
            $this->hidden = $module->getContentHiddenDefault($this->content->container);
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
            if ($this->parentFolder->content->isPrivate() && $this->content->isPublic()) {
                $this->content->visibility = Content::VISIBILITY_PRIVATE;
            }
        }

        $this->content->hidden = $this->hidden;
        if (!$insert) {
            $this->content->save();
        }

        parent::afterSave($insert, $changedAttributes);
    }

    /**
     * @inheritdoc
     */
    public function afterStateChange(?int $newState, ?int $previousState): void
    {
        // All parent folders should be restored after at least one child file/folder was restored
        if ($previousState === Content::STATE_DELETED && $newState === Content::STATE_PUBLISHED) {
            $parentFolder = $this->parentFolder;
            if ($parentFolder instanceof Folder) {
                $parentFolder->content->getStateService()->publish();
            }
        }

        parent::afterStateChange($newState, $previousState);
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
            'id' => 'parent_folder_id',
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
     * @inheritdoc
     */
    public function getCreator()
    {
        return $this->content->createdBy;
    }

    /**
     * @inheritdoc
     */
    public function getEditor()
    {
        return $this->content->updatedBy;
    }

    /**
     * Determines this item is an editable folder.
     *
     * @param \humhub\modules\cfiles\models\FileSystemItem $item
     * @return bool
     */
    public function isEditableFolder()
    {
        // TODO: not that clean...
        return ($this instanceof Folder) && !($this->isRoot() || $this->isAllPostedFiles());
    }

    /**
     * Determines if this item is deletable. The root folder and posted files folder is not deletable.
     * @return bool
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
        $params = empty($itemId) ? [] : explode('_', $itemId);

        if (sizeof($params) < 2) {
            return null;
        }

        list($type, $id) = $params;
        if ($type == 'file') {
            return File::find()->andWhere(['cfiles_file.id' => $id])->readable()->one();
        } elseif ($type == 'folder') {
            return Folder::find()->andWhere(['cfiles_folder.id' => $id])->readable()->one();
        }

        return null;
    }

    public function canManage(): bool
    {
        // Fixes race condition on newly created files (import vs. onlyoffice)
        if ($this->content->container === null && $this->content->isNewRecord) {
            return true;
        }

        return $this->content->container->permissionManager->can(ManageFiles::class);
    }

    public function canEdit(): bool
    {
        if ($this->canManage()) {
            return true;
        }

        if (Yii::$app->user->isGuest || $this->isNewRecord) {
            return false;
        }

        return $this->content->created_by === Yii::$app->user->id &&
            $this->content->container->permissionManager->can(WriteAccess::class);
    }

}
