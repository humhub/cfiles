<?php

namespace humhub\modules\cfiles\models;

use humhub\modules\cfiles\libs\FileUploadBatch;
use humhub\modules\cfiles\libs\FileUtils;
use humhub\modules\cfiles\services\ItemVisibilityService;
use humhub\modules\comment\models\Comment;
use humhub\modules\content\components\ContentContainerActiveRecord;
use humhub\modules\content\models\Content;
use humhub\modules\content\widgets\richtext\RichText;
use humhub\modules\file\libs\FileHelper;
use humhub\modules\file\models\File as BaseFile;
use humhub\modules\file\models\FileUpload;
use humhub\modules\post\models\Post;
use humhub\modules\topic\models\Topic;
use humhub\modules\user\models\User;
use Yii;
use yii\db\ActiveQuery;
use yii\web\UploadedFile;

/**
 * This is the model class for table "cfiles_file".
 *
 * @property int $id
 * @property int $parent_folder_id
 * @property string $description
 * @property int $download_count
 *
 * @property Folder $parentFolder
 * @property BaseFile $baseFile
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
     * @var array Content topics/tags
     */
    public $topics = [];

    /**
     * @inheritdoc
     */
    public $fileManagerEnableHistory = true;

    /**
     * @inheritdoc
     *
     * Uploading a set of files would otherwise create one notification and one e-mail per
     * file. The whole upload is announced by a single FilesUploaded notification instead.
     *
     * @see FileUploadBatch
     */
    public $silentContentCreation = true;

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
            ['description', 'string', 'max' => 1000],
            ['topics', 'safe'],
            ['hidden', 'boolean'],
        ];

        if ($this->parentFolder && $this->parentFolder->content->isPublic()) {
            $rules[] = ['visibility', 'integer', 'min' => 0, 'max' => 1];
        }

        return $rules;
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return array_merge(parent::attributeLabels(), [
            'id' => 'ID',
            'parent_folder_id' => Yii::t('CfilesModule.base', 'Folder ID'),
        ]);
    }

    /**
     * @inheritdoc
     */
    public function getSearchAttributes()
    {
        $attributes = [
            'description' => $this->description,
        ];

        if ($this->getCreator()) {
            $attributes['creator'] = $this->getCreator()->getDisplayName();
        }

        if ($this->getEditor()) {
            $attributes['editor'] = $this->getEditor()->getDisplayName();
        }

        if ($this->baseFile) {
            $attributes['name'] = $this->getTitle();
        }
        return $attributes;
    }

    public function setUploadedFile(UploadedFile $uploadedFile): bool
    {
        if ($this->baseFile) {
            $baseFile = FileUpload::findOne($this->baseFile->id);
        } else {
            $baseFile = new FileUpload(['show_in_stream' => false]);
        }
        $baseFile->setUploadedFile($uploadedFile);

        return $this->setFileContent($baseFile);
    }

    public function setFileContent(BaseFile $fileContent): bool
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

        $isNewBaseFile = $this->baseFile && ($insert || $this->baseFile->isNewRecord);
        if ($isNewBaseFile) {
            $this->baseFile->setPolymorphicRelation($this);
        }

        $fileTitleChanged = ($this->baseFile && $this->baseFile->getOldAttribute('file_name') != $this->baseFile->file_name);
        $newVersionUploaded = ($this->baseFile && isset($this->baseFile->uploadedFile) && $this->baseFile->uploadedFile instanceof UploadedFile);

        // Insert new base File OR Update the existing File if title has been changed or new file version has been uploaded
        if ($isNewBaseFile || $fileTitleChanged || $newVersionUploaded) {
            $this->baseFile->save(false);
        }

        // Save topics
        Topic::attach($this->content, $this->topics);

        ItemVisibilityService::apply($this, $this->visibility === null ? null : (int)$this->visibility);

        parent::afterSave($insert, $changedAttributes);

        RichText::postProcess($this->description, $this);

        if ($insert) {
            FileUploadBatch::add($this);
        }
    }

    /**
     * @inheritdoc
     */
    public function getContentId()
    {
        return $this->content->id;
    }

    /**
     * @inheritdoc
     */
    public function getTitle()
    {
        // needs to be checked cause used with uninitialized basefile by search index
        if (!empty($this->baseFile)) {
            return $this->baseFile->file_name;
        } else {
            return '';
        }
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    public function setTitle($title)
    {
        if (!empty($this->baseFile)) {
            $this->baseFile->file_name = $title;
        }
    }

    /**
     * @inheritdoc
     */
    public function getSize()
    {
        return $this->baseFile->size;
    }

    /**
     * Returns the URL to the folder where this file is located
     * @inheritdoc
     */
    public function getUrl(bool $scheme = false)
    {
        if ($this->parentFolder === null) {
            Yii::warning('Could not get parent folder for file id: ' . $this->id, 'cfiles');
            return '';
        }

        return $this->parentFolder->getUrl($scheme);
    }

    public function getBaseFile()
    {
        return $this->hasOne(BaseFile::class, ['object_id' => 'id'])
            ->andWhere(['file.object_model' => self::class]);
    }

}
