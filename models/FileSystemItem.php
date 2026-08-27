<?php

namespace humhub\modules\cfiles\models;

use humhub\modules\cfiles\Module;
use humhub\modules\cfiles\services\FolderTreeService;
use humhub\modules\cfiles\permissions\ManageFiles;
use humhub\modules\cfiles\permissions\WriteAccess;
use humhub\modules\content\components\ContentContainerActiveRecord;
use humhub\modules\content\components\ContentActiveRecord;
use humhub\modules\content\models\Content;
use Yii;

/**
 * What a cfiles file and a cfiles folder have in common: a row with a parent, wrapped in a
 * HumHub content record.
 *
 * Deliberately thin. Everything that is BEHAVIOUR around the tree — creating items, moving
 * them, resolving name collisions, propagating visibility, managing the root — lives in
 * `services\`, so what remains here is persistence and the content integration the platform
 * requires.
 *
 * @property int $id
 * @property int $parent_folder_id
 * @property string $description
 *
 * @property-read Folder|null $parentFolder
 */
abstract class FileSystemItem extends ContentActiveRecord
{
    /**
     * @var ?int whether the item is hidden from the stream.
     *
     * Not a column either: synced from the content record in {@see self::afterFind()},
     * defaulted from the module setting on insert in {@see self::beforeSave()}, and written
     * back in {@see self::afterSave()}.
     */
    public $hidden = null;

    /**
     * @var int|null the requested content visibility.
     *
     * Not a column: {@see Folder::beforeSave()} and {@see File::afterSave()} apply it to the
     * content record, recursively for a folder. A write leaves it null to keep the current
     * visibility.
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
            ['visibility', 'integer', 'min' => 0, 'max' => 1],
            ['hidden', 'boolean'],
        ];
    }

    abstract public function getSize();

    /**
     * @return string the item's display name — a folder's title, a file's file name.
     */
    abstract public function getTitle();

    /**
     * @return string the item's own URL, or the folder URL a file lives in
     */
    abstract public function getUrl(bool $scheme = false);

    abstract public function getDescription();

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
    public function afterMove(?ContentContainerActiveRecord $container = null)
    {
        parent::afterMove($container);
        $this->updateParentFolder();
    }

    /**
     * Update parent Folder if it is from different Content Container(Space/User)
     * This File/Folder will be moved into the root Folder of the current Content Container
     *
     * @return bool True on success moving or if parent Folder is already in the same Content Container
     */
    public function updateParentFolder(): bool
    {
        $parentFolder = Folder::findOne(['id' => $this->parent_folder_id]);
        if ($parentFolder && $parentFolder->content->contentcontainer_id == $this->content->contentcontainer_id) {
            return true;
        }

        if (!($root = FolderTreeService::getOrInitRoot($this->content->getContainer()))) {
            return false;
        }

        $this->parent_folder_id = $root->id;
        return $this->save();
    }

    public function hasAttributeChanged($attributeName)
    {
        return $this->hasAttribute($attributeName) && ($this->isNewRecord || $this->getOldAttribute($attributeName) != $this->$attributeName);
    }

    /**
     * Whether this is the same item — same kind, same row. A file and a folder can share a
     * numeric id, so the class is part of the comparison.
     */
    public function is(FileSystemItem $item)
    {
        return static::class === $item::class && (int)$this->id === (int)$item->id;
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
            $this->addError($attribute, Yii::t('CfilesModule.base', 'Please select a valid destination folder for %title%.', ['%title%' => $this->getTitle()]));
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
     * Whether this item is a folder that may be renamed, moved or deleted — every folder
     * except the container's root, which is the tree itself rather than a node in it.
     */
    public function isEditableFolder(): bool
    {
        return ($this instanceof Folder) && !$this->isRoot();
    }

    /**
     * Whether this item may be deleted. Files always may; the root folder never may, since
     * deleting it would take the container's whole tree with it.
     */
    public function isDeletable(): bool
    {
        return !($this instanceof Folder) || !$this->isRoot();
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

        if (Yii::$app->user->isGuest) {
            return false;
        }

        return ($this->isNewRecord || $this->content->created_by === Yii::$app->user->id)
            && $this->content->container->permissionManager->can(WriteAccess::class);
    }

}
