<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2015 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\cfiles\controllers;

use Yii;
use humhub\modules\cfiles\permissions\ManageFiles;
use yii\web\HttpException;
use humhub\modules\cfiles\models\FileSystemItem;

/**
 * Description of BrowseController
 *
 * @author luke, Sebastian Stumpf
 */
class DeleteController extends BrowseController
{
    /**
     * Action to delete a file or folder.
     * @return string
     */
    public function actionIndex()
    {
        $selectedItems = Yii::$app->request->post('selection');
        
        if (is_array($selectedItems)) {
            foreach ($selectedItems as $itemId) {
                $item = FileSystemItem::getItemById($itemId);

                if(!$item->content->canEdit()) {
                    throw new HttpException(403);
                }

                if ($item && $item->isDeletable() && $item->content->container->id === $this->contentContainer->id) {
                    $item->delete();
                }
            }
        }

        return $this->renderFileList();
    }
}
