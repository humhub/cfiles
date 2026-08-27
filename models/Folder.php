<?php

namespace humhub\modules\cfiles\models;

use humhub\modules\cfiles\services\FolderContentService;
use humhub\modules\cfiles\services\FolderTreeService;
use humhub\modules\cfiles\services\ItemMoveService;
use humhub\modules\cfiles\services\ItemVisibilityService;
use humhub\modules\content\components\ContentContainerActiveRecord;
use humhub\modules\content\models\Content;
use humhub\modules\file\libs\ImageHelper;
use humhub\modules\file\models\FileContent;
use humhub\modules\file\libs\FileHelper;
use humhub\modules\user\models\User;
use humhub\modules\space\models\Space;
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
 * @property string $type
 *
 * @property-read Folder|null $parentFolder
 * @property-read Folder[] $folders direct subfolders, used by the cascade delete
 * @property-read File[] $files direct files, used by the cascade delete
 */
class Folder extends FileSystemItem
{
    public const TYPE_FOLDER_ROOT = 'root';

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
            ['title', 'uniqueTitle'],
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

        if ((new FolderContentService($this->parentFolder))->folderExists($this->title)) {
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
        if ($this->isRoot()) {
            $attributes = [];
        } else {
            $attributes = [
                'name' => $this->title,
                'description' => $this->description,
            ];

            if ($this->getCreator()) {
                $attributes['creator'] = $this->getCreator()->getDisplayName();
            }

            if ($this->getEditor()) {
                $attributes['editor'] = $this->getEditor()->getDisplayName();
            }
        }
        return $attributes;
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

        foreach ($this->files as $file) {
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

        foreach ($this->files as $file) {
            $file->hardDelete();
        }

        return parent::beforeDelete();
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
     * @return bool
     */
    public function hasTitleChanged()
    {
        return $this->isNewRecord || $this->getOldAttribute('title') != $this->title;
    }

    /**
     * @inheritdoc
     */
    public function getContentId()
    {
        return $this->content->id;
    }

    /**
     * @inheritdoc
     */
    public function getTitle()
    {
        return $this->title;
    }

    public function getDescription()
    {
        return $this->description;
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

    public function isRoot()
    {
        return $this->type === self::TYPE_FOLDER_ROOT;
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
