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
use humhub\modules\cfiles\permissions\ManageFiles;
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

    public function run()
    {
        $deleteSelectionUrl = $this->folder->createUrl('/cfiles/delete');
        $moveSelectionUrl = $this->folder->createUrl('/cfiles/move', ['init' => 1]);

        $zipSelectionUrl = $this->folder->createUrl('/cfiles/zip/download');
        $makePrivateUrl = $this->folder->createUrl('/cfiles/edit/make-private');
        $makePublicUrl = $this->folder->createUrl('/cfiles/edit/make-public');

        $canWrite = $this->contentContainer->can(ManageFiles::class);

        return $this->render('fileSelectionMenu', [
            'deleteSelectionUrl' => $deleteSelectionUrl,
            'folder' => $this->folder,
            'moveSelectionUrl' => $moveSelectionUrl,
            'zipSelectionUrl' => $zipSelectionUrl,
            'canWrite' => $canWrite,
            'zipEnabled' =>  Yii::$app->getModule('cfiles')->isZipSupportEnabled(),
            'makePrivateUrl' => $makePrivateUrl,
            'makePublicUrl' => $makePublicUrl,
        ]);
    }
}