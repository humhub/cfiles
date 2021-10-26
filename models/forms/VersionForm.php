<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2021 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\cfiles\models\forms;

use humhub\modules\cfiles\models\File;
use humhub\modules\file\models\FileHistory;
use Yii;
use yii\base\Model;

/**
 * VersionForm to view current version of the selected file and to switch to another
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
        if (!$this->getFileVersion()) {
            $this->addError($attribute, 'The selected version doesn\'t exist for the File!');
        }
    }

    public function attributeHints(): array
    {
        return [
            'version' => Yii::t('CfilesModule.base', 'Select what file version you want to switch.'),
        ];
    }

    public function getFileVersion(): ?FileHistory
    {
        return $this->file->baseFile->getFileHistoryById($this->version);
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

        $this->file->baseFile->setStoredFile($this->getFileVersion()->getFileStorePath());

        return $this->file->baseFile->save() && $this->file->refresh();
    }

}