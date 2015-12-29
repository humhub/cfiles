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

    /**
     * Check if a parent folder is valid or lies in itsself, etc.
     * @param unknown $attribute
     * @param unknown $params
     */
    public function validateParentFolderId($attribute, $params)
    {
        $parent = Folder::findOne([
            'id' => $this->$attribute
        ]);
        
        if ($this->$attribute != 0 && ! ($parent instanceof Folder)) {
            $this->addError($attribute, Yii::t('CfilesModule.base', 'Please select a valid destination folder for %title%.', [
                '%title%' => $this->title
            ]));
        }
        
        // check if one of the parents is oneself to avoid circles
        while (! empty($parent)) {
            if ($this->id == $parent->id) {
                $this->addError($attribute, Yii::t('CfilesModule.base', 'Please select a valid destination folder for %title%.', [
                    '%title%' => $this->title
                ]));
                break;
            }
            $parent = Folder::findOne([
                'id' => $parent->parent_folder_id
            ]);
        }
    }
}