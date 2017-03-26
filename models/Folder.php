<?php

namespace humhub\modules\cfiles\models;

use Yii;

/**
 * This is the model class for table "cfiles_folder".
 *
 * @property integer $id
 * @property integer $parent_folder_id
 * @property string $title
 * @property string $description
 * @property string $type
 */
class Folder extends FileSystemItem
{

    const TYPE_FOLDER_ROOT = 'root';
    const TYPE_FOLDER_POSTED = 'posted';

    /**
     * @inheritdoc
     */
    public function beforeSave($insert)
    {
        $this->streamChannel = null;
        return parent::beforeSave($insert);
    }

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
    public function getSearchAttributes()
    {
        if ($this->isAllPostedFiles() || $this->isRoot()) {
            $attributes = [];
        } else {
            $attributes = array(
                'name' => $this->title,
                'description' => $this->description,
                'creator' => $this->getCreator()->getDisplayName(),
                'editor' => $this->getEditor()->getDisplayName()
            );
        }
        $this->trigger(self::EVENT_SEARCH_ADD, new \humhub\modules\search\events\SearchAddEvent($attributes));
        return $attributes;
    }

    public function getItemType()
    {
        return 'folder' . ($this->type !== null ? '-' . $this->type : '');
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            ['parent_folder_id', 'integer'],
            ['parent_folder_id', 'validateParentFolderId'],
            ['title', 'required'],
            ['title', 'trim'],
            ['title', 'string', 'max' => 255],
            ['title', 'noSpaces'],
            ['description', 'string', 'max' => 255],
            ['title', 'uniqueTitle']
        ];
    }

    /**
     * Makes sure that after an title change there is no equal title for the given container in the given parent folder.
     *
     * @param type $attribute
     * @param type $params
     * @param type $validator
     * @return null
     */
    public function uniqueTitle($attribute, $params, $validator)
    {
        if (!$this->hasTitleChanged()) {
            return;
        }

        $query = self::find()->contentContainer($this->content->container)->readable()->where([
            'cfiles_folder.title' => $this->title,
            'cfiles_folder.parent_folder_id' => $this->parent_folder_id
        ]);

        if (!empty($query->one())) {
            $this->addError('title', \Yii::t('CfilesModule.base', 'A folder with this name already exists.'));
        }
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'parent_folder_id' => Yii::t('CfilesModule.models_Folder', 'Parent Folder ID'),
            'title' => Yii::t('CfilesModule.models_Folder', 'Title'),
            'description' => Yii::t('CfilesModule.models_Folder', 'Description')
        ];
    }

    public function getFiles()
    {
        return $this->hasMany(File::className(), ['parent_folder_id' => 'id'])
                        ->joinWith('baseFile')
                        ->orderBy(['title' => SORT_ASC]);
    }

    public function getFolders()
    {
        return $this->hasMany(Folder::className(), ['parent_folder_id' => 'id'])->orderBy(['title' => SORT_ASC]);
    }

    public function hasTitleChanged()
    {
        return $this->isNewRecord || $this->getOldAttribute('title') != $this->title;
    }

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

    public function getItemId()
    {
        return $this->getItemType() . '_' . $this->id;
    }

    public function getIconClass()
    {
        return 'fa-folder';
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function getSize()
    {
        return 0;
    }

    public function getUrl()
    {
        if (empty($this->content->container)) {
            return "";
        }

        return $this->content->container->createUrl('/cfiles/browse/index', ['fid' => $this->id]);
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

    public static function getIdFromPath($path, $contentContainer, $separator = '/')
    {
        $titles = array_reverse(explode($separator, $path));

        if (sizeof($titles) <= 0) {
            return null;
        }

        $folders = Folder::find()->contentContainer($contentContainer)
                ->readable()
                ->where([
                    'title' => $titles[0]
                ])
                ->all();
        if (sizeof($folders) <= 0) {
            return null;
        }
        unset($titles[0]);

        foreach ($titles as $index => $title) {
            if (sizeof($folders) <= 0) {
                return null;
            }
        }

        $query = $this->hasOne(\humhub\modules\content\models\Content::className(), [
            'object_id' => 'id'
        ]);
        $query->andWhere([
            'file.object_model' => self::className()
        ]);
    }

    /**
     * @inheritdoc
     */
    public function getContentName()
    {
        return Yii::t('CfilesModule.base', "Folder");
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
     * @param array $params
     */
    public function validateParentFolderId($attribute = 'parent_folder_id', $params)
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

        parent::validateParentFolderId($attribute, $params);
    }

    public function getItems($filesOrder = ['title' => SORT_ASC], $foldersOrder = ['title' => SORT_ASC])
    {
        return [
            'specialFolders' => $this->getSpecialFolders(),
            'folders' => $this->getSubFolders($foldersOrder),
            'files' => $this->getSubFiles($filesOrder)
        ];
    }

    protected function getSpecialFolders($order = ['title' => SORT_ASC])
    {
        $specialFoldersQuery = Folder::find()->contentContainer($this->content->container)->readable();
        $specialFoldersQuery->andWhere(['cfiles_folder.parent_folder_id' => $this->id]);
        $specialFoldersQuery->andWhere(['is not', 'cfiles_folder.type', null]);
        return $specialFoldersQuery->all();
    }

    protected function getSubFolders($order = ['title' => SORT_ASC])
    {
        $foldersQuery = Folder::find()->contentContainer($this->content->container)->readable();
        $foldersQuery->andWhere(['cfiles_folder.parent_folder_id' => $this->id]);

        // do not return any folders here that are root or allpostedfiles
        $foldersQuery->andWhere(
                ['or',
                    ['cfiles_folder.type' => null],
                    ['and',
                        ['<>', 'cfiles_folder.type', Folder::TYPE_FOLDER_POSTED],
                        ['<>', 'cfiles_folder.type', Folder::TYPE_FOLDER_ROOT]
                    ]
        ]);
        $foldersQuery->orderBy($order);
        return $foldersQuery->all();
    }

    protected function getSubFiles($order = null)
    {
        if (!$order) {
            $order = 'file.file_name ASC';
        }

        $filesQuery = File::find()->joinWith('baseFile')->contentContainer($this->content->container)->readable();
        $filesQuery->andWhere(['cfiles_file.parent_folder_id' => $this->id]);
        $filesQuery->orderBy($order);
        return $filesQuery->all();
    }

}
