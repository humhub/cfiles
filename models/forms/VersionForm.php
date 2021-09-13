<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2021 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\cfiles\models\forms;

use humhub\libs\Html;
use humhub\modules\file\models\File as BaseFile;
use humhub\modules\cfiles\models\File;
use Yii;
use yii\base\Model;

/**
 * VersionForm to view current version of the selected file and to switch to another
 *
 * @property-read null|int $currentVersionFileId
 * @property-read array $versions All versions of the File
 *
 * @author luke
 */
class VersionForm extends Model
{
    /**
     * @var File
     */
    public $file;

    /**
     * @var int File ID of the current version
     */
    public $version;

    public function init()
    {
        parent::init();

        $this->version = $this->getCurrentVersionFileId();
    }

    private function getCurrentVersionFileId(): ?int
    {
        if ($this->file->file_id !== null) {
            return $this->file->file_id;
        }

        /* @var BaseFile $file */
        // Get the latest version if not defined yet
        $file = $this->file->getVersionsQuery()->limit(1)->one();

        return $file ? $file->id : null;
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            ['version', 'required'],
            ['version', 'integer'],
        ];
    }

    public function attributeHints(): array
    {
        return [
            'version' => Yii::t('CfilesModule.base', 'Select what file version you want to switch.'),
        ];
    }

    /**
     * @return array All versions of the File
     */
    public function getVersions(): array
    {
        $versions = [];

        foreach ($this->file->getVersionsQuery()->all() as $versionFile) {
            /* @var BaseFile $versionFile */
            $versions[$versionFile->id] = $versionFile->file_name . ': ' .
                Html::encode($versionFile->createdBy->displayName) .
                ' (' . Yii::$app->formatter->asDatetime($versionFile->created_at, 'short') . ')' .
                ', ' . Yii::$app->formatter->asShortSize($versionFile->size, 1);
        }

        return $versions;
    }

    /**
     * Switch the File to a selected version
     *
     * @return bool
     */
    public function save(): bool
    {
        if (!$this->validate()) {
            return false;
        }

        $this->file->file_id = $this->version;

        return $this->file->save();
    }

}