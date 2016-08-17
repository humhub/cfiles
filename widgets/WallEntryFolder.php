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
class WallEntryFolder extends \humhub\modules\content\widgets\WallEntry
{

    /**
     * @inheritdoc
     */
    public $editRoute = "";

    /**
     * @inheritdoc
     */
    public function run()
    {
        
        return $this->render('wallEntryFolder', array('folder' => $this->contentObject));
    }

}
