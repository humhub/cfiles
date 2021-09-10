<?php

namespace humhub\modules\cfiles\models;

use humhub\modules\cfiles\libs\FileUtils;
use humhub\modules\content\components\ContentContainerActiveRecord;
use humhub\modules\content\widgets\richtext\RichText;
use humhub\modules\file\handler\DownloadFileHandler;
use humhub\modules\file\libs\FileHelper;
use humhub\modules\file\models\FileUpload;
use humhub\modules\search\events\SearchAddEvent;
use humhub\modules\topic\models\Topic;
use Yii;
use humhub\modules\user\models\User;
use humhub\modules\comment\models\Comment;
use humhub\modules\content\models\Content;
use yii\db\ActiveQuery;
use yii\helpers\Url;
use yii\web\UploadedFile;

/**
 * This is the model class for table "cfiles_file".
 *
 * @property integer $id
 * @property integer $parent_folder_id
 * @property string $description
 * @property integer $download_count
 * @property integer $file_id Current file version, NULL - for the latest version
 *
 * @property Folder $parentFolder
 * @property \humhub\modules\file\models\File $baseFile
 */
class File extends FileSystemItem
{
    /**
     * @inheritdoc
     */
    public $wallEntryClass = "humhub\modules\cfiles\widgets\WallEntryFile";

    /**
     * @var File
     */
    protected $_setFileContent = null;

    /**
     * @inheritdocs
     */
    public $canMove = true;

    /**
     * @inheritdocs
     */
    public $moduleId = 'cfiles';

    /**
     * @var array Content topics/tags
     */
    public $topics = [];

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
     * @inheritdoc
     */
    public function getIcon()
    {
        return FileUtils::getIconClassByExt(FileHelper::getExtension($this->baseFile));
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        $rules = [
            [['parent_folder_id'], 'required'],
            ['parent_folder_id', 'integer'],
            ['parent_folder_id', 'validateParentFolderId'],
            ['description', 'string', 'max' => 255],
            ['topics', 'safe'],
        ];

        if($this->parentFolder && $this->parentFolder->content->isPublic()) {
            $rules[] = ['visibility', 'integer', 'min' => 0, 'max' => 1];
        }

        return $rules;
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return array_merge(parent::attributeLabels(),[
            'id' => 'ID',
            'parent_folder_id' => Yii::t('CfilesModule.models_File', 'Folder ID')
        ]);
    }

    /**
     * @inheritdoc
     */
    public function getSearchAttributes()
    {
        $attributes = [
            'description' => $this->description
        ];

        if($this->getCreator()) {
            $attributes['creator'] = $this->getCreator()->getDisplayName();
        }

        if($this->getEditor()) {
            $attributes['editor'] = $this->getEditor()->getDisplayName();
        }

        if ($this->baseFile) {
            $attributes['name'] = $this->getTitle();
        }
        $this->trigger(self::EVENT_SEARCH_ADD, new SearchAddEvent($attributes));
        return $attributes;
    }

    public function setUploadedFile(UploadedFile $uploadedFile)
    {
        $baseFile = new FileUpload(['show_in_stream' => false]);
        $baseFile->setUploadedFile($uploadedFile);

        return $this->setFileContent($baseFile);
    }

    public function setFileContent(\humhub\modules\file\models\File $fileContent)
    {
        $this->populateRelation('baseFile', $fileContent);

        // Temp Fix: https://github.com/yiisoft/yii2/issues/15875
        $this->_setFileContent = $fileContent;

        return $this->baseFile->validate();
    }

    /**
     * @inheritdoc
     */
    public function afterFind()
    {
        $this->topics = Topic::findByContent($this->content);
        parent::afterFind();
    }

    public function afterSave($insert, $changedAttributes)
    {
        // Temp Fix: https://github.com/yiisoft/yii2/issues/15875
        if ($this->_setFileContent !== null) {
            $this->populateRelation('baseFile', $this->_setFileContent);
        }

        if($insert && $this->baseFile || ($this->baseFile && $this->baseFile->isNewRecord)) {
            $this->baseFile->setPolymorphicRelation($this);
        }

        // Required if title has changed.
        if($this->baseFile && ($insert ||  ($this->baseFile->getOldAttribute('file_name') != $this->baseFile->file_name || $this->baseFile->isNewRecord))) {
            $this->baseFile->save(false);
        }

        // Save topics
        Topic::attach($this->content, $this->topics);

        $this->updateVisibility($this->visibility);

        parent::afterSave($insert, $changedAttributes);

        RichText::postProcess($this->description, $this);
    }

    /**
     * @inheritdoc
     */
    public function afterMove(ContentContainerActiveRecord $container = null) {
        parent::afterMove($container);

        /* @var $root Folder */
        $root = Folder::find()
            ->contentContainer($container)
            ->andWhere(['type' => Folder::TYPE_FOLDER_ROOT])
            ->one();
        if ($root) {
            // Put the moved file into the root of new container:
            $this->parent_folder_id = $root->id;
            $this->save();
        }
    }

    public function updateVisibility($visibility)
    {
        if ($visibility === null) {
            return;
        }

        if(!$this->parentFolder->content->isPrivate() || $visibility == Content::VISIBILITY_PRIVATE) {
            // For user profile files we use Content::VISIBILITY_OWNER isntead of private
            $this->content->visibility = $visibility;
        }
    }

    public function getVisibilityTitle()
    {
        if(Yii::$app->getModule('friendship')->getIsEnabled() && $this->content->container instanceof User) {
            if($this->content->container->isCurrentuser()) {
                $privateText =  Yii::t('CfilesModule.base', 'This file is only visible for you and your friends.');
            } else {
                $privateText =  Yii::t('CfilesModule.base', 'This file is protected.');
            }

            return  $this->content->isPublic()
                ?  Yii::t('CfilesModule.base', 'This file is public.')
                : $privateText;
        }

        return  $this->content->isPublic()
            ?  Yii::t('CfilesModule.base', 'This file is public.')
            : Yii::t('CfilesModule.base', 'This file is private.');
    }

    /**
     * @return string
     */
    public function getItemId()
    {
        return 'file_' . $this->id;
    }

    /**
     * @return string
     */
    public function getItemType()
    {
        return FileUtils::getItemTypeByExt(FileHelper::getExtension($this->baseFile));
    }

    /**
     * @return string file title (name)
     */
    public function getTitle()
    {
        // needs to be checked cause used with uninitialized basefile by search index
        if (!empty($this->baseFile)) {
            return $this->baseFile->file_name;
        } else {
            return "";
        }
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @return integer
     */
    public function getDownloadCount()
    {
        return $this->download_count;
    }

    public function setTitle($title)
    {
        if (!empty($this->baseFile)) {
            $this->baseFile->file_name = $title;
        }
    }

    /**
     * @return int file size
     */
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

    public function getFullUrl()
    {
        return $this->getDownloadUrl(true, true);
    }

    /**
     * @param bool $forceDownload forces a download for each file type instead of opening in browser
     * @return string download url
     */
    public function getDownloadUrl($forceDownload = false, $scheme = true)
    {
        if(!$scheme) {
            return DownloadFileHandler::getUrl($this->baseFile, $forceDownload);
        } else {
            // Todo can be removed after v1.2.3 then call DownloadFileHandler::getUrl($this->baseFile, $forceDownload, $scheme)
            return Url::to(['/file/file/download', 'guid' => $this->baseFile->guid, 'download' => $forceDownload], $scheme);
        }
    }

    /**
     * @inheritdoc
     */
    public function getEditUrl()
    {
        return $this->content->container->createUrl('/cfiles/edit/file', ['id' => $this->getItemId()]);
    }

    /**
     * Get the post related to the given file file.
     */
    public static function getBasePost(\humhub\modules\file\models\File $file = null)
    {
        if ($file === null) {
            return null;
        }

        $searchItem = $file;
        // if the item is connected to a Comment, we have to search for the corresponding Post
        if ($file->object_model === Comment::class) {
            $searchItem = Comment::findOne($file->object_id);
        }

        return Content::find()->where([
            'content.object_id' => $searchItem->object_id,
            'content.object_model' => $searchItem->object_model
        ])->one();
    }

    public function getBaseFile()
    {
        return $this->hasOne(FileUpload::class, ['object_id' => 'id'])
            ->andWhere(['file.object_model' => self::class])
            ->leftJoin(self::tableName() . ' cf_version', 'file.id = cf_version.file_id')
            ->orderBy(['cf_version.file_id' => SORT_DESC, 'file.id' => SORT_DESC]);
    }

    public static function getPathFromId($id, $parentFolderPath = false, $separator = '/', $withRoot = false)
    {
        if ($id == 0) {
            return $separator;
        }
        $item = File::findOne(['id' => $id]);

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
     * Returns a query for all posted files visible for the current user.
     *
     * @param $contentContainer ContentContainerActiveRecord
     * @param array $filesOrder orderBy array appended to the files query
     * @return ActiveQuery
     */
    public static function getPostedFiles($contentContainer, $filesOrder = ['file.updated_at' => SORT_ASC, 'file.title' => SORT_ASC])
    {
        // Get Posted Files
        $query = \humhub\modules\file\models\File::find();
        // join comments to the file if available
        $query->join('LEFT JOIN', 'comment', '(file.object_id=comment.id AND file.object_model=' . Yii::$app->db->quoteValue(Comment::className()) . ')');
        // join parent post of comment or file
        $query->join('LEFT JOIN', 'content', '(comment.object_model=content.object_model AND comment.object_id=content.object_id) OR (file.object_model=content.object_model AND file.object_id=content.object_id)');

        $query->andWhere(['content.contentcontainer_id' => $contentContainer->contentContainerRecord->id]);

        if(!$contentContainer->canAccessPrivateContent()) {
            // Note this will cut comment images, but including the visibility of comments is pretty complex...
            $query->andWhere(['content.visibility' => Content::VISIBILITY_PUBLIC]);
        }

        // only accept Posts as the base content, so stuff from sumbmodules like files itsself or gallery will be excluded
        $query->andWhere(
                ['or',
                    ['=', 'comment.object_model', \humhub\modules\post\models\Post::className()],
                    ['=', 'file.object_model', \humhub\modules\post\models\Post::className()]
        ]);

        // Get Files from comments
        return $query->orderBy($filesOrder);
    }

    /**
     * @return file of given name in given parent folder
     */
    public static function getFileByName($name, $parentFolderId, $contentContainer)
    {
        $filesQuery = self::find()->contentContainer($contentContainer)
                ->joinWith('baseFile')
                ->readable()
                ->andWhere([
            'file_name' => $name,
            'cfiles_file.parent_folder_id' => $parentFolderId
        ]);
        return $filesQuery->one();
    }

    /**
     * Get File by guid
     *
     * @param $guid
     * @return array|File|\yii\db\ActiveRecord
     */
    public static function getFileByGuid($guid)
    {
        return self::find()
            ->innerJoin('file', 'object_id = ' . self::tableName() . '.id')
            ->where(['guid' => $guid])
            ->andWhere(['object_model' => self::class])
            ->one();
    }
}
