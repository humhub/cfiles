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

    public $showGlobalMenuItem;

    public $enableWebDav;

    public function init()
    {
        parent::init();

        $module = $this->getModule();
        $this->displayDownloadCount = $module->getDisplayDownloadCount();
        $this->contentHiddenDefault = $module->getContentHiddenGlobalDefault();
        $this->showGlobalMenuItem = $module->getShowGlobalMenuItem();
        $this->enableWebDav = $module->getEnableWebDav();
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
            [['displayDownloadCount', 'contentHiddenDefault', 'showGlobalMenuItem', 'enableWebDav'], 'boolean'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'displayDownloadCount' => Yii::t('CfilesModule.base', 'Display a download count column'),
            'showGlobalMenuItem' => Yii::t('CfilesModule.base', 'Add entry to main navigation'),
            'enableWebDav' => Yii::t('CfilesModule.base', 'Access via WebDAV (network drive)'),
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeHints()
    {
        return [
            'enableWebDav' => Yii::t('CfilesModule.base', 'Users connect the files of their spaces and their profile as a network drive, signed in with a personal access token.'),
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
        $module->settings->set('showGlobalMenuItem', $this->showGlobalMenuItem);
        $module->settings->set('enableWebDav', $this->enableWebDav);

        return true;
    }
}
