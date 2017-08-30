<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2017 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 *
 */

/**
 * Created by PhpStorm.
 * User: buddha
 * Date: 18.08.2017
 * Time: 21:58
 */

namespace humhub\modules\cfiles\widgets;


use humhub\components\Widget;
use humhub\modules\cfiles\models\Folder;
use humhub\modules\content\components\ContentContainerActiveRecord;
use Yii;

class FileSelectionMenu extends Widget
{
    /**
     * @var Folder
     */
    public $folder;

    /**
     * @var ContentContainerActiveRecord
     */
    public $contentContainer;

    /**
     * @var boolean
     */
    public $canWrite;

    public function run()
    {
        $deleteSelectionUrl = $this->contentContainer->createUrl('/cfiles/delete', ['fid' => $this->folder->id]);
        $moveSelectionUrl = $this->contentContainer->createUrl('/cfiles/move', ['init' => 1, 'fid' => $this->folder->id]);

        $zipSelectionUrl = $this->contentContainer->createUrl('/cfiles/zip/download');
        $makePrivateUrl = $this->contentContainer->createUrl('/cfiles/edit/make-private');
        $makePublicUrl = $this->contentContainer->createUrl('/cfiles/edit/make-public');

        return $this->render('fileSelectionMenu', [
            'deleteSelectionUrl' => $deleteSelectionUrl,
            'folder' => $this->folder,
            'moveSelectionUrl' => $moveSelectionUrl,
            'zipSelectionUrl' => $zipSelectionUrl,
            'canWrite' => $this->canWrite,
            'zipEnabled' =>  Yii::$app->getModule('cfiles')->isZipSupportEnabled(),
            'makePrivateUrl' => $makePrivateUrl,
            'makePublicUrl' => $makePublicUrl,
        ]);
    }
}