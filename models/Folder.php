<?php

namespace humhub\modules\cfiles\models;

use humhub\modules\cfiles\services\FolderContentService;
use humhub\modules\cfiles\services\ItemMoveService;
use humhub\modules\cfiles\services\ItemVisibilityService;
use humhub\modules\content\components\ContentContainerActiveRecord;
use humhub\modules\content\models\Content;
use humhub\modules\file\libs\ImageHelper;
use humhub\modules\file\models\FileContent;
use humhub\modules\file\libs\FileHelper;
use humhub\modules\user\models\User;
use humhub\modules\space\models\Space;
use Throwable;
use Yii;
use yii\db\ActiveQuery;
use yii\imagine\Image;
use yii\web\UploadedFile;

/**
 * This is the model class for table "cfiles_folder".
 *
 * @property int $id
 * @property int $parent_folder_id
 * @property string $title
 * @property string $description
 *
 * @property-read Folder|null $parentFolder
 * @property-read Folder[] $folders direct subfolders, used by the cascade delete
 * @property-read File[] $files direct files, used by the cascade delete
 */
class Folder extends FileSystemItem
{
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
        return 'folder-filled';
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
            ['title', 'uniqueTitle'],
        ]);

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
        if (!$this->hasTitleChanged()) {
            return;
        }

        $content = new FolderContentService($this->content->container, $this->parentFolder);

        if ($content->folderExists($this->title, $this)) {
            $this->addError('title', \Yii::t('CfilesModule.base', 'A folder with this name already exists.'));
        } elseif ($content->fileExists($this->title)) {
            $this->addError('title', \Yii::t('CfilesModule.base', 'A file with that name already exists in this folder.'));
        }
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return array_merge(parent::attributeLabels(), [
            'id' => 'ID',
            'parent_folder_id' => Yii::t('CfilesModule.base', 'Parent Folder ID'),
            'title' => Yii::t('CfilesModule.base', 'Title'),
            'description' => Yii::t('CfilesModule.base', 'Description'),
        ]);
    }

    /**
     * @inheritdoc
     */
    public function getSearchAttributes()
    {
        $attributes = [
            'name' => $this->title,
            'description' => $this->description,
        ];

        if ($this->content->createdBy) {
            $attributes['creator'] = $this->content->createdBy->getDisplayName();
        }

        if ($this->content->updatedBy) {
            $attributes['editor'] = $this->content->updatedBy->getDisplayName();
        }

        return $attributes;
    }

    /**
     * A change of a folder cascades into what it holds (its visibility, see beforeSave(), and its
     * hard deletion, see beforeDelete()): all of it or nothing, in one transaction.
     *
     * @inheritdoc
     */
    public function transactions()
    {
        return array_fill_keys(array_keys($this->scenarios()), self::OP_UPDATE | self::OP_DELETE);
    }

    /**
     * Moved to the trash with everything inside it (see afterSoftDelete()), or not at all.
     *
     * @inheritdoc
     */
    public function softDelete(): bool
    {
        $transaction = Yii::$app->db->beginTransaction();
        try {
            $deleted = parent::softDelete();
        } catch (Throwable $e) {
            $transaction->rollBack();
            throw $e;
        }

        $deleted ? $transaction->commit() : $transaction->rollBack();

        return $deleted;
    }

    /**
     * The id of the folder and those of the folders above it, up to the top level: one query
     * of the parent's id per level (no recursive query on MySQL 5.7).
     *
     * @return int[]
     */
    public static function ancestorIds(int $folderId): array
    {
        $ids = [];
        for ($id = $folderId; $id !== null && !isset($ids[$id]); $id = $parentId) {
            // A corrupt parent chain (a cycle) must not hang the request.
            $ids[$id] = true;
            $parentId = static::find()->select('parent_folder_id')->where(['id' => $id])->scalar();
            $parentId = $parentId ? (int)$parentId : null;
        }

        return array_keys($ids);
    }

    /**
     * Marks the folders as changed now, by the current user, as a save of their content would:
     * one statement for all of them.
     *
     * @param int[] $folderIds
     */
    public static function markAsChanged(array $folderIds): void
    {
        if ($folderIds === []) {
            return;
        }

        $attributes = ['updated_at' => date('Y-m-d H:i:s')];
        if (isset(Yii::$app->user->id)) {
            $attributes['updated_by'] = Yii::$app->user->id;
        }

        Content::updateAll($attributes, ['object_model' => static::class, 'object_id' => $folderIds]);
    }

    /**
     * @inheritdoc
     */
    public function beforeSave($insert)
    {
        if ($this->visibility !== null) {
            if ($insert) {
                // Nothing inside it yet, so there is nothing to propagate to.
                $this->content->visibility = (int)$this->visibility;
            } elseif ((int)$this->visibility !== (int)$this->content->visibility) {
                ItemVisibilityService::apply($this, (int)$this->visibility);
            }
        }

        return parent::beforeSave($insert);
    }

    /**
     * A folder moves to another container with everything inside it (see afterMove()) — or not
     * at all: when one item may not follow (its author may not post there, say), the folder is
     * refused rather than leaving that item behind in a folder of another container, where
     * neither container's browser reaches it. Recursive: a subfolder asks the same of its items.
     *
     * @inheritdoc
     */
    public function canMove(?ContentContainerActiveRecord $container = null)
    {
        $canMove = parent::canMove($container);

        if ($canMove !== true || $container === null) {
            return $canMove;
        }

        foreach (ItemMoveService::subItems($this) as $item) {
            $itemCanMove = $item->content->canMove($container);

            if ($itemCanMove !== true) {
                return Yii::t('CfilesModule.base', '"{name}" cannot be moved along: {reason}', [
                    'name' => $item->getTitle(),
                    'reason' => $itemCanMove,
                ]);
            }
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function afterMove(?ContentContainerActiveRecord $container = null)
    {
        parent::afterMove($container);

        ItemMoveService::moveSubItemsToContainer($this, $container);
    }

    /**
     * @inheritdoc
     */
    public function afterSoftDelete()
    {
        foreach ($this->folders as $folder) {
            $folder->delete();
        }

        foreach ($this->subFiles as $file) {
            $file->delete();
        }

        parent::afterSoftDelete();
    }

    /**
     * @inheritdoc
     */
    public function beforeDelete()
    {
        foreach ($this->folders as $folder) {
            $folder->hardDelete();
        }

        foreach ($this->subFiles as $file) {
            $file->hardDelete();
        }

        return parent::beforeDelete();
    }

    /**
     * The files directly inside the folder. Not `files`: that is the relation of every content
     * record to the files attached to it, which the core's file manager reads
     * ({@see \humhub\modules\content\components\ContentActiveRecord::getFiles()}).
     *
     * @return ActiveQuery of all direct child files
     * @since 1.0
     */
    public function getSubFiles()
    {
        return $this->hasMany(File::class, ['parent_folder_id' => 'id'])
                        ->joinWith('baseFile')
                        ->orderBy(['title' => SORT_ASC]);
    }

    /**
     * @return ActiveQuery of all direct child folders
     */
    public function getFolders()
    {
        return $this->hasMany(Folder::class, ['parent_folder_id' => 'id'])->orderBy(['title' => SORT_ASC]);
    }

    /**
     * @return bool
     */
    public function hasTitleChanged()
    {
        return $this->isNewRecord || $this->getOldAttribute('title') != $this->title;
    }

    /**
     * @inheritdoc
     */
    public function getTitle()
    {
        return $this->getAttribute('title');
    }

    /**
     * @inheritdoc
     */
    public function getSize()
    {
        return 0;
    }

    /**
     * @inheritdoc
     */
    public function getUrl(bool $scheme = false)
    {
        if (empty($this->content->container)) {
            return '';
        }

        return $this->content->container->createUrl('/cfiles/browse/index', ['fid' => $this->id], $scheme);
    }

    public function noSpaces($attribute, $params)
    {
        if (trim((string) $this->$attribute) !== $this->$attribute) {
            $this->addError($attribute, Yii::t('CfilesModule.base', 'Folder should not start or end with blank space.'));
        }
    }

    /**
     * @inheritdoc
     */
    public function getContentDescription()
    {
        return $this->title;
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

}
