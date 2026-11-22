<?php

namespace humhub\modules\cfiles\models;

use Yii;
use humhub\modules\cfiles\Module;
use humhub\modules\oauth\Module as OAuthModule;
use yii\helpers\Html;
use yii\helpers\Url;

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
        $webDav = Yii::t('CfilesModule.base', 'Users connect the files of their spaces and their profile as a network drive, with a password for each device (a personal access token).');
        if (!$this->canEnableWebDav()) {
            $webDav .= ' ' . Yii::t('CfilesModule.base', 'Requires personal access tokens, which are switched off under {link}.', [
                'link' => Html::a(Yii::t('CfilesModule.base', 'Apps & API Access'), Url::to(['/oauth/admin/index'])),
            ]);
        }

        return [
            // Hints are not encoded
            'enableWebDav' => $webDav,
        ];
    }

    /**
     * Whether WebDAV can work: a device signs in with a personal access token.
     */
    public function canEnableWebDav(): bool
    {
        return OAuthModule::instance()->allowsPersonalAccessTokens();
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
