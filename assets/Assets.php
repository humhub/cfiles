<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2015 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\cfiles\assets;

use yii\web\AssetBundle;

class Assets extends AssetBundle
{
    /**
     * v1.5 compatibility defer script loading
     *
     * Migrate to HumHub AssetBundle once minVersion is >=1.5
     *
     * @var bool
     */
    public $defer = true;

    public $publishOptions = [
        'forceCopy' => false
    ];
    
    public $css = [
        'css/cfiles.css',
        'css/directorylist.css'
    ];
    
    public $jsOptions = [
        'position' => \yii\web\View::POS_BEGIN
    ];
    
    public $js = [
        'js/humhub.cfiles.js'
    ];

    public function init()
    {
        $this->sourcePath = dirname(dirname(__FILE__)) . '/resources';
        parent::init();
    }

}
