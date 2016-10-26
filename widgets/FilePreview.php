<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2015 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\cfiles\widgets;

/**
 * @inheritdoc
 */
class FilePreview extends \humhub\components\Widget
{

    public $file;
    public $height = -1;
    public $width = -1;
    public $htmlConf = [];
    
    /**
     * @inheritdoc
     */
    public function run()
    {
        return $this->render('filePreview', array('file' => $this->file, 'width' => $this->width, 'height' => $this->height, 'htmlConf' => $this->htmlConf));
    }

}
