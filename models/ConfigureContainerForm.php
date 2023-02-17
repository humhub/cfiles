<?php

namespace humhub\modules\cfiles\models;

use humhub\modules\content\components\ContentContainerActiveRecord;
use humhub\modules\content\components\ContentContainerSettingsManager;
use Yii;
use humhub\modules\cfiles\Module;
use yii\base\Model;

class ConfigureContainerForm extends Model
{
    public $contentHiddenDefault;

    public ContentContainerActiveRecord $contentContainer;

    public function init()
    {
        parent::init();

        $this->loadBySettings();
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
            [['contentHiddenDefault'], 'boolean'],
        ];
    }

    public function loadBySettings()
    {
        $this->contentHiddenDefault = $this->getSettings()->get(
            'contentHiddenDefault',
            $this->getModule()->getContentHiddenGlobalDefault()
        );
    }

    public function save(): bool
    {
        if (!$this->validate()) {
            return false;
        }

        $this->getSettings()->set('contentHiddenDefault', $this->contentHiddenDefault);

        return true;
    }

    private function getSettings(): ContentContainerSettingsManager
    {
        return $this->getModule()->settings->contentContainer($this->contentContainer);
    }

}
