<?php
namespace humhub\modules\cfiles\models;

use Yii;
use humhub\modules\user\models\User;
use humhub\modules\comment\models\Comment;
use humhub\modules\content\models\Content;

/**
 * This is the model class for table "cfiles_file".
 *
 * @property integer $id
 * @property integer $parent_folder_id
 */
class File extends FileSystemItem
{
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
                'parent_folder_id',
                'integer'
            ],
            [
                'parent_folder_id',
                'validateParentFolderId'
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
            'parent_folder_id' => Yii::t('CfilesModule.models_File', 'Folder ID')
        ];
    }

    public function getItemId()
    {
        return 'file_' . $this->id;
    }

    public function getIconClass()
    {
        return File::getIconClassByExt(strtolower($this->baseFile->getExtension()));
    }

    public static function getIconClassByExt($ext)
    {
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
            return "fa-file-archive-o";
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
            'tiff',
            'png'
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

    public function getItemType()
    {
        return File::getItemTypeByExt(strtolower($this->baseFile->getExtension()));
    }

    /**
     * Make method from humhub\modules\file\models\File available.
     * 
     * @param unknown $file_name            
     * @return string
     */
    public static function sanitizeFilename($filename)
    {
        $file = new \humhub\modules\file\models\File();
        $file->file_name = $filename;
        $file->sanitizeFilename();
        return $file->file_name;
    }

    public static function getItemTypeByExt($ext)
    {
        if (in_array($ext, [
            'html',
            'cmd',
            'bat',
            'xml'
        ])) {
            return 'code';
        } elseif (in_array($ext, [
            'zip',
            'rar',
            'gz',
            'tar'
        ])) {
            return "archive";
        } elseif (in_array($ext, [
            'mp3',
            'wav'
        ])) {
            return "audio";
        } elseif (in_array($ext, [
            'xls',
            'xlsx'
        ])) {
            return "excel";
        } elseif (in_array($ext, [
            'jpg',
            'gif',
            'bmp',
            'svg',
            'tiff',
            'png'
        ])) {
            return "image";
        } elseif (in_array($ext, [
            'pdf'
        ])) {
            return "pdf";
        } elseif (in_array($ext, [
            'ppt',
            'pptx'
        ])) {
            return "powerpoint";
        } elseif (in_array($ext, [
            'txt',
            'log',
            'md'
        ])) {
            return "text";
        } elseif (in_array($ext, [
            'mp4',
            'mpeg',
            'swf'
        ])) {
            return "video";
        } elseif (in_array($ext, [
            'doc',
            'docx'
        ])) {
            return "word";
        }
        return 'unknown';
    }

    public function getTitle()
    {
        return $this->baseFile->file_name;
    }

    public function getSize()
    {
        return $this->baseFile->size;
    }

    public function getUrl($download = false)
    {
        return $this->baseFile->getUrl().($download ? '&'.http_build_query(['download' => 1]) : '');
    }

    public function getCreator()
    {
        return File::getUserById($this->baseFile->created_by);
    }
    
    public function getEditor()
    {
        return File::getUserById($this->baseFile->updated_by);
    }

    public static function getUserById($id)
    {
        return User::findOne([
            'id' => $id
        ]);
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

    public static function getPathFromId($id, $parentFolderPath = false, $separator = '/')
    {
        if ($id == 0) {
            return $separator;
        }
        $item = File::findOne([
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

    public function validateParentFolderId($attribute, $params)
    {
        parent::validateParentFolderId($attribute, $params);
    }
    

    /**
     * @inheritdoc
     */
    public function getContentName()
    {
        return Yii::t('CfilesModule.base', "File");
    }

    /**
     * @inheritdoc
     */
    public function getContentDescription()
    {
        return $this->getTitle();
    }    
    
}
