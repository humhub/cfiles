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
 * @property string $description
 */
class File extends FileSystemItem
{

    /**
     * @inheritdoc
     */
    public $autoAddToWall = true;

    /**
     * @inheritdoc
     */
    public $wallEntryClass = "humhub\modules\cfiles\widgets\WallEntryFile";

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
            ['parent_folder_id', 'integer'],
            ['parent_folder_id', 'validateParentFolderId'],
            ['description', 'string', 'max' => 255]
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

    /**
     * @inheritdoc
     */
    public function getSearchAttributes()
    {
        $attributes = array(
            'description' => $this->description,
            'creator' => $this->getCreator()->getDisplayName(),
            'editor' => $this->getEditor()->getDisplayName()
        );
        if ($this->baseFile) {
            $attributes['name'] = $this->getTitle();
        }
        $this->trigger(self::EVENT_SEARCH_ADD, new \humhub\modules\search\events\SearchAddEvent($attributes));
        return $attributes;
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
     * TODO: check against changes in 1.2
     * @deprecated will no longer work in 1.2
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
        // needs to be checked cause used with uninitialized basefile by search index
        if (!empty($this->baseFile)) {
            return $this->baseFile->file_name;
        } else {
            return "";
        }
    }

    public function getSize()
    {
        return $this->baseFile->size;
    }

    /**
     * Returns the URL to the folder where this file is located
     */
    public function getUrl()
    {
        if ($this->parentFolder === null) {
            Yii::warning('Could not get parent folder for file id: ' . $this->id, 'cfiles');
            return '';
        }

        return $this->parentFolder->getUrl();
    }

    public function getDownloadUrl($forceDownload = false)
    {
        return \humhub\modules\file\handler\DownloadFileHandler::getUrl($this->baseFile, $forceDownload);
    }

    public function getEditUrl()
    {
        return $this->content->container->createUrl('/cfiles/edit/file', ['id' => $this->getItemId()]);
    }

    public static function getUserById($id)
    {
        return User::findOne(['id' => $id]);
    }

    /**
     * Get the post the file is connected to.
     */
    public static function getBasePost($file = null)
    {
        if ($file === null) {
            return null;
        }
        $searchItem = $file;
        // if the item is connected to a Comment, we have to search for the corresponding Post
        if ($file->object_model === Comment::className()) {
            $searchItem = Comment::findOne([
                        'id' => $file->object_id
            ]);
        }
        $query = Content::find();
        $query->andWhere([
            'content.object_id' => $searchItem->object_id,
            'content.object_model' => $searchItem->object_model
        ]);
        return $query->one();
    }

    public function getBaseFile()
    {
        $query = $this->hasOne(\humhub\modules\file\models\FileUpload::className(), [
            'object_id' => 'id'
        ]);
        $query->andWhere([
            'file.object_model' => self::className()
        ]);
        return $query;
    }

    public static function getPathFromId($id, $parentFolderPath = false, $separator = '/', $withRoot = false)
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
        if (!$parentFolderPath) {
            $path .= $item->title;
        }
        $counter = 0;
        // break at maxdepth 20 to avoid hangs
        while (!empty($tempFolder) && $counter ++ <= 20) {
            $path = $separator . $tempFolder->title . $path;
            $tempFolder = $tempFolder->parentFolder;
        }
        return $path;
    }

    public function getFullPath($separator = '/')
    {
        return $this->getPathFromId($this->id, false, $separator);
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

    /**
     * Load all posted files from the database and get an array of them.
     *
     * @param array $filesOrder
     *            orderBy array appended to the files query
     * @param array $foldersOrder
     *            currently unused
     * @return Ambigous <multitype:, multitype:\yii\db\ActiveRecord >
     */
    public static function getPostedFiles($contentContainer, $filesOrder = NULL, $foldersOrder = NULL)
    {
        // set ordering default
        if (!$filesOrder) {
            $filesOrder = ['file.updated_at' => SORT_DESC, 'file.title' => SORT_ASC];
        }

        // Get Posted Files
        $query = \humhub\modules\file\models\File::find();
        // join comments to the file if available
        $query->join('LEFT JOIN', 'comment', '(file.object_id=comment.id AND file.object_model=' . Yii::$app->db->quoteValue(Comment::className()) . ')');
        // join parent post of comment or file
        $query->join('LEFT JOIN', 'content', '(comment.object_model=content.object_model AND comment.object_id=content.object_id) OR (file.object_model=content.object_model AND file.object_id=content.object_id)');

        $query->andWhere(['content.contentcontainer_id' => $contentContainer->contentContainerRecord->id]);

        // only accept Posts as the base content, so stuff from sumbmodules like files itsself or gallery will be excluded
        $query->andWhere(
                ['or',
                    ['=', 'comment.object_model', \humhub\modules\post\models\Post::className()],
                    ['=', 'file.object_model', \humhub\modules\post\models\Post::className()]
        ]);

        // Get Files from comments
        return ['postedFiles' => $query->orderBy($filesOrder)->all()];
    }

}
