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
class FolderPreview extends \humhub\components\Widget
{

    public $htmlConf = [];
    public $lightboxDataParent;
    public $lightboxDataGallery;
    public $folder;

    /**
     * @inheritdoc
     */
    public function run()
    {
        return $this->render('folderPreview', array('lightboxDataParent' => $this->lightboxDataParent, 'lightboxDataGallery' => $this->lightboxDataGallery, 'htmlConf' => $this->htmlConf, 'folder' => $this->folder));
    }

}
