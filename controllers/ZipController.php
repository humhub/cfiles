<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2017 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\cfiles\controllers;

use Yii;
use yii\web\HttpException;
use humhub\modules\cfiles\libs\ZIPCreator;
use humhub\modules\cfiles\widgets\FileList;

/**
 * ZipController
 *
 * @author Sebastian Stumpf
 */
class ZipController extends BaseController
{

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();

        if (!$this->module->isZipSupportEnabled()) {
            throw new HttpException(404, Yii::t('CfilesModule.base', 'ZIP support is not enabled.'));
        }
    }

    /**
     * Action to download a zip of the selected items.
     */
    public function actionDownload()
    {
        $items = FileList::getSelectedItems();

        if (count($items) === 0) {
            // Fallback to current folder when not items are selected
            $items[] = $this->getCurrentFolder();
        }

        $zip = new ZIPCreator();
        foreach ($items as $item) {
            $zip->add($item);
        }
        $zip->close();

        return Yii::$app->response->sendFile($zip->getZipFile(), (count($items) == 1) ? $items[0]->title . '.zip' : 'files.zip');
    }

}
