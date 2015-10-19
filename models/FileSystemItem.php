<?php
namespace humhub\modules\cfiles\models;

use Yii;

/**
 * This is the model class for table "cfiles_file".
 *
 * @property integer $id
 * @property integer $folder_id
 */
abstract class FileSystemItem extends \humhub\modules\content\components\ContentActiveRecord implements \humhub\modules\cfiles\ItemInterface
{

    /**
     * @inheritdoc
     */
    public $autoAddToWall = false;

    public $path = null;

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

    public function getBaseContent()
    {
        $query = $this->hasOne(\humhub\modules\content\models\Content::className(), [
            'object_id' => 'id'
        ]);
        $query->andWhere([
            'file.object_model' => self::className()
        ]);
        return $query;
    }
}