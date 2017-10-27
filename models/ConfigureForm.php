<?php

namespace humhub\modules\cfiles\models;

use Yii;
use humhub\modules\cfiles\Module;

/**
 * ConfigureForm defines the configurable fields.
 *
 * @package humhub\modules\cfiles\models
 * @author Sebastian Stumpf
 */
class ConfigureForm extends \yii\base\Model
{

    public $disableZipSupport;
    public $uploadBehaviour;

    public function init()
    {
        parent::init();
        $module = $this->getModule();
        $this->disableZipSupport = !$module->isZipSupportEnabled();
        $this->uploadBehaviour = $module->getUploadBehaviour();
    }

    /**
     * @return Module
     */
    public function getModule()
    {
        return Yii::$app->getModule('cfiles');
    }

    /**
     * Declares the validation rules.
     */
    public function rules()
    {
        return [
            ['disableZipSupport', 'boolean'],
            ['uploadBehaviour', 'integer'],
        ];
    }

    /**
     * Declares customized attribute labels.
     * If not declared here, an attribute would have a label that is
     * the same as its name with the first letter in upper case.
     */
    public function attributeLabels()
    {
        return [
            'disableZipSupport' => Yii::t('CfilesModule.base', 'Disable archive (ZIP) support'),
            'uploadBehaviour' => Yii::t('CfilesModule.base', 'Upload behaviour for existing file names'),
        ];
    }

    public function attributeHints()
    {
        return [
            'uploadBehaviour' => Yii::t('CfilesModule.base', '<strong>Note:</strong> The replacement behaviour is currently not supported for zip imports.')
        ];
    }

    public function save()
    {
        if(!$this->validate()) {
            return false;
        }

        $module = $this->getModule();
        $module->settings->set('disableZipSupport', $this->disableZipSupport);
        $module->settings->set('uploadBehaviour', $this->uploadBehaviour);
        return true;
    }
}
