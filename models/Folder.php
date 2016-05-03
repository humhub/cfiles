<?php
namespace humhub\modules\cfiles\models;

use Yii;
use humhub\modules\user\models\User;
use humhub\modules\content\components\ContentActiveRecord;
use humhub\modules\content\models\Content;
use yii\web\HttpException;
use humhub\models\Setting;
use yii\helpers\FileHelper;

/**
 * This is the model class for table "cfiles_folder".
 *
 * @property integer $id
 * @property integer $parent_folder_id
 * @property string $title
 */
class Folder extends FileSystemItem
{

    public $path = "";

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'cfiles_folder';
    }

    public function getItemType()
    {
        return 'folder';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [
                'parent_folder_id',
                'integer'
            ],
            [
                'parent_folder_id',
                'validateParentFolderId'
            ],
            [
                'title',
                'required'
            ],
            [
                'title',
                'string',
                'max' => 255
            ],
            [
                'title',
                'noSpaces'
            ]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'parent_folder_id' => Yii::t('CfilesModule.models_Folder', 'Parent Folder ID'),
            'title' => Yii::t('CfilesModule.models_Folder', 'Title')
        ];
    }

    public function getFiles()
    {
        return $this->hasMany(File::className(), [
            'parent_folder_id' => 'id'
        ]);
    }

    public function getFolders()
    {
        return $this->hasMany(Folder::className(), [
            'parent_folder_id' => 'id'
        ]);
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
        return 'folder_' . $this->id;
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
        return $this->content->container->createUrl('/cfiles/browse/index', [
            'fid' => $this->id
        ]);
    }

    public function getCreator()
    {
        return User::findOne([
            'id' => $this->content->created_by
        ]);
    }

    public function getEditor()
    {
        return User::findOne([
            'id' => $this->content->updated_by
        ]);
    }

    public function noSpaces($attribute, $params)
    {
        if (trim($this->$attribute) !== $this->$attribute) {
            $this->addError($attribute, Yii::t('CfilesModule.base', 'Folder should not start or end with blank space.'));
        }
    }

    public static function getPathFromId($id, $parentFolderPath = false, $separator = '/')
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
        $path = $separator;
        if (! $parentFolderPath) {
            $path .= $item->title;
        }
        while (! empty($tempFolder)) {
            $path = $separator . $tempFolder->title . $path;
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

    public function validateParentFolderId($attribute, $params)
    {
        parent::validateParentFolderId($attribute, $params);
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
    public function getContentDescription()
    {
        return $this->title;
    }
}
