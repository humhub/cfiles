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
    public $displayDownloadCount;

    public function init()
    {
        parent::init();
        $module = $this->getModule();
        $this->disableZipSupport = !$module->isZipSupportEnabled();
        $this->displayDownloadCount = $module->getDisplayDownloadCount();
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
            ['displayDownloadCount', 'boolean'],
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
            'displayDownloadCount' => Yii::t('CfilesModule.base', 'Display a download count column'),
        ];
    }

    public function save()
    {
        if(!$this->validate()) {
            return false;
        }

        $module = $this->getModule();
        $module->settings->set('disableZipSupport', $this->disableZipSupport);
        $module->settings->set('displayDownloadCount', $this->displayDownloadCount);
        return true;
    }
}
