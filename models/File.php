<?php
namespace humhub\modules\cfiles\models;

use Yii;
use humhub\modules\user\models\User;

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
            [
                [
                    'folder_id'
                ],
                'integer'
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
            'folder_id' => 'Folder ID'
        ];
    }

    public function getBaseFile()
    {
        $query = $this->hasOne(\humhub\modules\file\models\File::className(), [
            'object_id' => 'id'
        ]);
        $query->andWhere([
            'file.object_model' => self::className()
        ]);
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
        $ext = strtolower($this->baseFile->getExtension());
        
        if (in_array($ext, [
            'html',
            'cmd',
            'bat',
            'xml'
        ])) {
            return 'fa-file-code-o';
        } elseif (in_array($ext, [
            'zip',
            'rar',
            'gz',
            'tar'
        ])) {
            return "fa-archive-o";
        } elseif (in_array($ext, [
            'mp3',
            'wav'
        ])) {
            return "fa-file-audio-o";
        } elseif (in_array($ext, [
            'xls',
            'xlsx'
        ])) {
            return "fa-file-excel-o";
        } elseif (in_array($ext, [
            'jpg',
            'gif',
            'bmp',
            'svg',
            'tiff'
        ])) {
            return "fa-file-image-o";
        } elseif (in_array($ext, [
            'pdf'
        ])) {
            return "fa-file-pdf-o";
        } elseif (in_array($ext, [
            'ppt',
            'pptx'
        ])) {
            return "fa-file-powerpoint-o";
        } elseif (in_array($ext, [
            'txt',
            'log',
            'md'
        ])) {
            return "fa-file-text-o";
        } elseif (in_array($ext, [
            'mp4',
            'mpeg',
            'swf'
        ])) {
            return "fa-file-video-o";
        } elseif (in_array($ext, [
            'doc',
            'docx'
        ])) {
            return "fa-file-word-o";
        }
        return 'fa-file-o';
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

    public function getCreator()
    {
        return User::findOne([
            'id' => $this->baseFile->created_by
        ]);
    }
}
