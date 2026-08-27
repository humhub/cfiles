<?php

namespace humhub\modules\cfiles\models;

use humhub\modules\cfiles\Module;
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

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'visibility' => Yii::t('CfilesModule.base', 'Is Public'),
            'hidden' => Yii::t('CfilesModule.base', 'Hide in Stream'),
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

        $parentFolder = Folder::findOne(['id' => $this->parent_folder_id]);

        if ($parentFolder && $parentFolder->content->contentcontainer_id === $this->content->contentcontainer_id) {
            return;
        }

        // The parent stayed behind in the old container, so this lands at the top level of the
        // new one — which is simply a null parent.
        $this->parent_folder_id = null;
        $this->save();
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
     * Check if a parent folder is valid or lies in itsself, etc.
     *
     * @param string $attribute the parent folder attribute to validate
     */
    public function validateParentFolderId($attribute = 'parent_folder_id')
    {
        // A null parent is the container's top level, and valid.
        if ($this->parent_folder_id !== null && !($this->parentFolder instanceof Folder)) {
            $this->addError($attribute, Yii::t('CfilesModule.base', 'Please select a valid destination folder for %title%.', ['%title%' => $this->getTitle()]));
        }
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
