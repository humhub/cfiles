<?php

namespace humhub\modules\cfiles\models;

use Yii;

/**
 * This is the model class for table "cfiles_file".
 *
 * @property integer $id
 * @property integer $folder_id
 */
class File extends \humhub\modules\content\components\ContentActiveRecord implements \humhub\modules\cfiles\ItemInterface
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
        return 'cfiles_file';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['folder_id'], 'integer']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'folder_id' => 'Folder ID',
        ];
    }

    public function getBaseFile()
    {
        $query = $this->hasOne(\humhub\modules\file\models\File::className(), ['object_id' => 'id']);
        $query->andWhere(['file.object_model' => self::className()]);
        return $query;
    }

    public function beforeSave($insert)
    {
        if ($this->folder_id == "") {
            $this->folder_id = 0;
        }

        return parent::beforeSave($insert);
    }

    public function getItemId()
    {
        return 'file-' . $this->id;
    }

    public function getIconClass()
    {
        return 'music';
    }

    public function getTitle()
    {
        return $this->baseFile->file_name;
    }

    public function getSize()
    {
        return $this->baseFile->size;
    }

    public function getUrl()
    {
        return $this->baseFile->getUrl();
    }

}
