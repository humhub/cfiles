<?php

namespace humhub\modules\cfiles\models;

use Yii;
use yii\helpers\Url;
use humhub\modules\user\models\User;
use humhub\modules\content\components\ContentActiveRecord;
use humhub\modules\cfiles\ItemInterface;
use humhub\modules\search\interfaces\Searchable;
use humhub\modules\cfiles\permissions\ManageFiles;

/**
 * This is the model class for table "cfiles_file".
 *
 * @property integer $id
 * @property integer $folder_id
 */
abstract class FileSystemItem extends ContentActiveRecord implements ItemInterface, Searchable
{

    /**
     * @inheritdoc
     */
    public function beforeSave($insert)
    {
        if ($this->parent_folder_id == "") {
            $this->parent_folder_id = 0;
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
        }
        return parent::afterSave($insert, $changedAttributes);
    }

    /**
     * @inheritdoc
     */
    public function getParentFolder()
    {
        $query = $this->hasOne(Folder::className(), [
            'id' => 'parent_folder_id'
        ]);
        return $query;
    }

    /**
     * @inheritdoc
     */
    public function getWallUrl()
    {
        $permaLink = Url::to(['/content/perma', 'id' => $this->content->id], true);
        return $permaLink;
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
     * @param array $params validation option
     */
    public function validateParentFolderId($attribute = 'parent_folder_id', $params)
    {
        if ($this->parent_folder_id != 0 && !($this->parentFolder instanceof Folder)) {
            $this->addError($attribute, Yii::t('CfilesModule.base', 'Please select a valid destination folder for %title%.', ['%title%' => $this->title]));
        }
    }

    public function getCreator()
    {
        return User::findOne(['id' => $this->content->created_by]);
    }

    public function getEditor()
    {
        return User::findOne(['id' => $this->content->updated_by]);
    }

    public function getMoveUrl()
    {
        return $this->content->container->createUrl('/cfiles/move', ['init' => 1]);
    }

    /**
     * Determines this item is an editable folder.
     * 
     * @param \humhub\modules\cfiles\models\FileSystemItem $item
     * @return boolean
     */
    public function isEditableFolder()
    {
        return ($this instanceof Folder) && !($this->isRoot() || $this->isAllPostedFiles());
    }

    /**
     * Determines if this item is deletable. The root folder and posted files folder is not deletable.
     * @return boolean
     */
    public function isDeletable()
    {
        if ($this instanceof Folder) {
            return !($this->isRoot() || $this->isAllPostedFiles());
        }
        return true;
    }

    /**
     * Returns a FileSystemItem instance by the given item id of form {type}_{id}
     * 
     * @param string $itemId item id of form {type}_{id}
     * @return FileSystemItem
     */
    public static function getItemById($itemId)
    {
        $params = explode('_', $itemId);

        if (sizeof($params) < 2) {
            return null;
        }

        list ($type, $id) = explode('_', $itemId);
        if ($type == 'file') {
            return File::find()->andWhere(['cfiles_file.id' => $id])->readable()->one();
        } elseif ($type == 'folder') {
            return Folder::find()->andWhere(['cfiles_folder.id' => $id])->readable()->one();
        }

        //elseif ($type == 'baseFile') {
        //    return File::findOne(['id' => $id]);
        //}

        return null;
    }

    public function canEdit(User $user)
    {
        if ($this->content->container->permissionManager->can(new ManageFiles())) {
            return true;
        }

        return false;
    }

}
