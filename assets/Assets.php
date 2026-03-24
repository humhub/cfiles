<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2015 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\cfiles\assets;

use humhub\components\assets\AssetBundle;

class Assets extends AssetBundle
{
    /**
     * @inheridoc
     */
    public $sourcePath = '@cfiles/resources';

    public $forceCopy = false;

    public $css = [
        'css/cfiles.css',
        'css/directorylist.css',
    ];

    public $jsOptions = [
        'position' => \yii\web\View::POS_BEGIN,
    ];

    public $js = [
        'js/humhub.cfiles.js',
    ];
}
