<?php
namespace humhub\modules\cfiles\models;

use Yii;
use humhub\modules\user\models\User;
use humhub\modules\content\components\ContentActiveRecord;
use humhub\modules\content\models\Content;
use yii\web\HttpException;
use humhub\models\Setting;
use yii\helpers\FileHelper;

/**
 * This is the model class for table "cfiles_folder".
 *
 * @property integer $id
 * @property integer $parent_folder_id
 * @property string $title
 * @property string $description
 * @property string $type
 */
class Folder extends FileSystemItem
{

    const TYPE_FOLDER_ROOT = 'root';

    const TYPE_FOLDER_POSTED = 'posted';

    /**
     * @inheritdoc
     */
    public $autoAddToWall = false;

    /**
     * @inheritdoc
     */
    public $wallEntryClass = "humhub\modules\cfiles\widgets\WallEntryFolder";

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'cfiles_folder';
    }

    public function getItemType()
    {
        return 'folder' . ($this->type !== null ? '-' . $this->type : '');
    }

    public function getWallUrl()
    {
        $firstWallEntryId = $this->content->getFirstWallEntryId();
        
        if ($firstWallEntryId == "") {
            return '';
        }
        
        return \yii\helpers\Url::toRoute([
            '/content/perma/wall-entry',
            'id' => $firstWallEntryId
        ]);
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
            ],
            [
                'title',
                'required'
            ],
            [
                'title',
                'string',
                'max' => 255
            ],
            [
                'title',
                'noSpaces'
            ],
            [
                'description',
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
            'parent_folder_id' => Yii::t('CfilesModule.models_Folder', 'Parent Folder ID'),
            'title' => Yii::t('CfilesModule.models_Folder', 'Title'),
            'description' => Yii::t('CfilesModule.models_Folder', 'Description for the wall entry.')
        ];
    }

    public function getFiles()
    {
        return $this->hasMany(File::className(), [
            'parent_folder_id' => 'id'
        ])
            ->joinWith('baseFile')
            ->orderBy([
            'title' => SORT_ASC
        ]);
    }

    public function getFolders()
    {
        return $this->hasMany(Folder::className(), [
            'parent_folder_id' => 'id'
        ])->orderBy([
            'title' => SORT_ASC
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
        return $this->getItemType() . '_' . $this->id;
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

    /**
     * @inheritdoc
     */
    public function getSearchAttributes()
    {
        if ($this->isAllPostedFiles() || $this->isRoot()) {
            $attributes = [];
        } else {
            $attributes = array(
                'name' => $this->title,
                'description' => $this->description,
                'creator' => $this->getCreator()->getDisplayName(),
                'editor' => $this->getEditor()->getDisplayName()
            );
        }
        $this->trigger(self::EVENT_SEARCH_ADD, new \humhub\modules\search\events\SearchAddEvent($attributes));
        return $attributes;
    }

    public function noSpaces($attribute, $params)
    {
        if (trim($this->$attribute) !== $this->$attribute) {
            $this->addError($attribute, Yii::t('CfilesModule.base', 'Folder should not start or end with blank space.'));
        }
    }

    public function getFullPath($separator = '/')
    {
        return $this->getPathFromId($this->id, false, $separator);
    }

    public static function getPathFromId($id, $parentFolderPath = false, $separator = '/', $withRoot = false)
    {
        if ($id == 0) {
            return $separator;
        }
        $item = Folder::findOne([
            'id' => $id
        ]);
        if (empty($item)) {
            return null;
        }
        $tempFolder = $item->parentFolder;
        $path = '';
        if (! $parentFolderPath) {
            if ($item->isRoot()) {
                if ($withRoot) {
                    $path .= $item->title;
                }
            } else {
                $path .= $separator . $item->title;
            }
        }
        $counter = 0;
        // break at maxdepth to avoid hangs
        while (! empty($tempFolder)) {
            if ($tempFolder->isRoot()) {
                if ($withRoot) {
                    $path = $tempFolder->title . $path;
                }
                break;
            } else {
                if (++ $counter > 10) {
                    $path = '...' . $path;
                    break;
                }
                $path = $separator . $tempFolder->title . $path;
            }
            
            $tempFolder = $tempFolder->parentFolder;
        }
        return $path;
    }

    public static function getIdFromPath($path, $contentContainer, $separator = '/')
    {
        $titles = array_reverse(explode($separator, $path));
        
        if (sizeof($titles) <= 0) {
            return null;
        }
        
        $folders = Folder::find()->contentContainer($contentContainer)
            ->readable()
            ->where([
            'title' => $titles[0]
        ])
            ->all();
        if (sizeof($folders) <= 0) {
            return null;
        }
        unset($titles[0]);
        
        foreach ($titles as $index => $title) {
            if (sizeof($folders) <= 0) {
                return null;
            }
        }
        
        $query = $this->hasOne(\humhub\modules\content\models\Content::className(), [
            'object_id' => 'id'
        ]);
        $query->andWhere([
            'file.object_model' => self::className()
        ]);
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
        return Yii::t('CfilesModule.base', "Folder");
    }

    /**
     * @inheritdoc
     */
    public function getContentDescription()
    {
        return $this->title;
    }

    public function isRoot()
    {
        return $this->type === self::TYPE_FOLDER_ROOT;
    }

    public function isAllPostedFiles()
    {
        return $this->type === self::TYPE_FOLDER_POSTED;
    }

    public function afterSave($insert, $changedAttributes)
    {
        parent::afterSave($insert, $changedAttributes);
        // Rootfolder and Allposted files folder do never have wallentries
        if ($insert && ! $this->isAllPostedFiles() && ! $this->isRoot()) {
            $this->content->addToWall();
        }
    }
}
