<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2015 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\cfiles\controllers;

use Yii;
use yii\web\HttpException;
use humhub\modules\cfiles\models\Folder;

/**
 * Description of BrowseController
 *
 * @author luke, Sebastian Stumpf
 */
class DeleteController extends BrowseController
{

    /**
     * Action to delete a file or folder.
     * @return Ambigous <\humhub\modules\cfiles\controllers\type, string>
     */
    public function actionIndex()
    {
        if (!$this->canWrite()) {
            throw new HttpException(401, Yii::t('CfilesModule.base', 'Insufficient rights to execute this action.'));
        }
        $selectedItems = Yii::$app->request->post('selected');
        if (!Yii::$app->request->get('confirm')) {
            return $this->renderPartial('modal_delete', [
                        'contentContainer' => $this->contentContainer,
                        'selectedItems' => $selectedItems,
                        'currentFolder' => $this->getCurrentFolder(),
            ]);
        }
        if (is_array($selectedItems)) {
            foreach ($selectedItems as $itemId) {
                $item = $this->module->getItemById($itemId);
                if ($this->isDeletable($item)) {
                    $item->delete();
                }
            }
        }

        return $this->renderFileList();
    }

    private function isDeletable($item)
    {
        if ($item === null) {
            return false;
        }
        if ($item instanceof Folder) {
            if ($item->isRoot() || $item->isAllPostedFiles()) {
                return false;
            }
        }
        return true;
    }

}
