<?php
namespace humhub\modules\cfiles\models;

use Yii;
use humhub\modules\user\models\User;
use humhub\modules\content\components\ContentActiveRecord;
use humhub\modules\content\models\Content;

/**
 * This is the model class for table "cfiles_folder".
 *
 * @property integer $id
 * @property integer $parent_folder_id
 * @property string $title
 */
class Folder extends \humhub\modules\content\components\ContentActiveRecord implements \humhub\modules\cfiles\ItemInterface
{

    /**
     * @inheritdoc
     */
    public $autoAddToWall = false;

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
    public function rules()
    {
        return [
            [
                [
                    'parent_folder_id'
                ],
                'integer'
            ],
            [
                [
                    'title'
                ],
                'required'
            ],
            [
                [
                    'title'
                ],
                'string',
                'max' => 255
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
            'parent_folder_id' => 'Parent Folder ID',
            'title' => 'Title'
        ];
    }

    public function beforeSave($insert)
    {
        if ($this->parent_folder_id == "") {
            $this->parent_folder_id = 0;
        }
        
        return parent::beforeSave($insert);
    }

    public function getParentFolder()
    {
        $query = $this->hasOne(self::className(), [
            'id' => 'parent_folder_id'
        ]);
        return $query;
    }

    public function getFiles()
    {
        return $this->hasMany(File::className(), [
            'folder_id' => 'id'
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
        return 'folder-' . $this->id;
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
        $content = Content::findOne([
            'object_model' => $this->className(),
            'object_id' => $this->id
        ]);
        return User::findOne([
            'id' => $content->created_by
        ]);
    }
}
