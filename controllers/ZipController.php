<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2017 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\cfiles\controllers;

use humhub\modules\cfiles\actions\UploadZipAction;
use humhub\modules\cfiles\libs\ZIPCreator;
use humhub\modules\cfiles\models\FileSystemItem;
use humhub\modules\cfiles\permissions\WriteAccess;
use Yii;
use yii\web\BadRequestHttpException;

/**
 * ZipController
 *
 * @author Sebastian Stumpf
 */
class ZipController extends BrowseController
{
    /**
     * @inheritdoc
     */
    protected function getAccessRules()
    {
        return [
            ['checkZipSupport'],
            ['permission' => [WriteAccess::class], 'actions' => ['upload']],
        ];
    }

    public function actions()
    {
        return [
            'upload' => [
                'class' => UploadZipAction::class,
            ],
        ];
    }

    public function checkZipSupport($rule, $access)
    {
        if (!$this->module->isZipSupportEnabled()) {
            $access->code = 404;
            $access->reason = Yii::t('CfilesModule.base', 'ZIP support is not enabled.');
            return false;
        }

        return true;
    }

    /**
     * Action to download a zip of the selected items.
     */
    public function actionDownload()
    {
        $selectedItems = Yii::$app->request->post('selection');

        $items = [];
        // Download only the selected items if at least one is selected
        if (is_array($selectedItems)) {
            foreach ($selectedItems as $itemId) {
                $item = FileSystemItem::getItemById($itemId);
                if ($item !== null) {
                    $items[] = $item;
                }
            }
        }
        // Otherwise fallback to current folder when no items are selected
        if ($items === []) {
            if (!Yii::$app->request->get('fid')) {
                throw new BadRequestHttpException('Wrong request without folder id!');
            }
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
