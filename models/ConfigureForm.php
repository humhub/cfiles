<?php

namespace humhub\modules\cfiles\models;

use Yii;

/**
 * ConfigureForm defines the configurable fields.
 *
 * @package humhub\modules\cfiles\models
 * @author Sebastian Stumpf
 */
class ConfigureForm extends \yii\base\Model
{

    public $disableZipSupport;

    /**
     * Declares the validation rules.
     */
    public function rules()
    {
        return array(
            array('disableZipSupport', 'boolean'),
        );
    }

    /**
     * Declares customized attribute labels.
     * If not declared here, an attribute would have a label that is
     * the same as its name with the first letter in upper case.
     */
    public function attributeLabels()
    {
        return array(
            'disableZipSupport' => Yii::t('CfilesModule.base', 'Disable archive (ZIP) support'),
        );
    }

}
