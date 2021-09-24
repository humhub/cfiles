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

        $this->version = $this->file->baseFile->id;
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            ['version', 'required'],
            ['version', 'integer'],
            ['version', 'validateVersion'],
        ];
    }

    /**
     * Validate the selected version really exists for the File
     */
    public function validateVersion($attribute)
    {
        if (!$this->file->baseFile->isVersion(File::class, $this->$attribute)) {
            $this->addError($attribute, 'The selected version doesn\'t exist for the File!');
        }
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

        foreach ($this->file->baseFile->getVersions() as $versionFile) {
            /* @var BaseFile $versionFile */
            $versions[$versionFile->id] = $versionFile->file_name . ': ' .
                Html::encode($versionFile->createdBy->displayName) .
                ' (' . Yii::$app->formatter->asDatetime($versionFile->created_at, 'short') . ')' .
                ', ' . Yii::$app->formatter->asShortSize($versionFile->size, 1);
        }

        return $versions;
    }

    /**
     * @inheritdoc
     */
    public function load($data = null, $formName = null)
    {
        if ((int)Yii::$app->request->get('version') > 0) {
            $data = Yii::$app->request->get();
            $formName = '';
        } else {
            $data = Yii::$app->request->post();
        }

        return parent::load($data, $formName);
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

        $newVersionFile = BaseFile::findOne(['id' => $this->version]);

        return $newVersionFile &&
            $this->file->baseFile->replaceFileWith($newVersionFile) &&
            $this->file->refresh();
    }

}