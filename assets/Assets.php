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

    public $publishOptions = [
        'forceCopy' => true
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
