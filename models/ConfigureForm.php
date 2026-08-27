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
    public $displayDownloadCount;

    public $contentHiddenDefault;

    public function init()
    {
        parent::init();

        $module = $this->getModule();
        $this->displayDownloadCount = $module->getDisplayDownloadCount();
        $this->contentHiddenDefault = $module->getContentHiddenGlobalDefault();
    }

    /**
     * @return Module
     */
    public function getModule()
    {
        return Yii::$app->getModule('cfiles');
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['displayDownloadCount', 'contentHiddenDefault'], 'boolean'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'displayDownloadCount' => Yii::t('CfilesModule.base', 'Display a download count column'),
        ];
    }

    public function save(): bool
    {
        if (!$this->validate()) {
            return false;
        }

        $module = $this->getModule();
        $module->settings->set('displayDownloadCount', $this->displayDownloadCount);
        $module->settings->set('contentHiddenGlobalDefault', $this->contentHiddenDefault);

        return true;
    }
}
