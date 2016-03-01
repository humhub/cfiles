<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2015 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\cfiles\controllers;

use Yii;
use yii\web\HttpException;
use yii\web\UploadedFile;
use humhub\modules\cfiles\models\File;
use humhub\modules\cfiles\models\Folder;
use humhub\modules\content\models\Content;
use humhub\modules\content\components\ContentActiveRecord;
use humhub\modules\comment\models\Comment;
use yii\helpers\FileHelper;
use humhub\models\Setting;

/**
 * Description of BrowseController
 *
 * @author luke, Sebastian Stumpf
 */
class MoveController extends BaseController {

    /**
     * Action to move files and folders from the current, to another folder.
     * @return string
     */
    public function actionIndex()
    {
        if(!$this->canWrite()) {
            throw new HttpException(401, Yii::t('CfilesModule.base', 'Insufficient rights to execute this action.'));
        }
        $folder = $this->getCurrentFolder();
        $currentFolderId = empty($folder) ? self::ROOT_ID : $folder->id;
        $selectedItems = Yii::$app->request->post('selected');
        $selectedDatabaseItems = [];
        $destFolderId = Yii::$app->request->post('destfid');
        $init = Yii::$app->request->get('init');
        $errorMsgs = [];

        if ($init) {
            // render modal if no destination folder is specified
            return $this->renderAjax('modal_move', [
                        'folders' => $this->getFolderList(),
                        'contentContainer' => $this->contentContainer,
                        'selectedItems' => $selectedItems,
                        'selectedFolderId' => $currentFolderId
            ]);
        }

        if (is_array($selectedItems) && !empty($selectedItems)) {
            foreach ($selectedItems as $itemId) {
                $item = $this->module->getItemById($itemId);
                if ($item !== null) {
                    if ($item->parent_folder_id == $destFolderId) {
                        $errorMsgs[] = Yii::t('CfilesModule.base', 'Moving to the same folder is not valid. Choose a valid parent folder for %title%.', [
                                    '%title%' => $item->title
                        ]);
                        continue;
                    }
                    $selectedDatabaseItems[] = $item;
                    $item->setAttribute('parent_folder_id', $destFolderId);
                    $item->validate();
                    if (!empty($item->errors)) {
                        foreach ($item->errors as $key => $error) {
                            $errorMsgs[] = $item->errors[$key][0];
                        }
                    }
                }
            }
        } else {
            $errorMsgs[] = Yii::t('CfilesModule.base', 'No valid items were selected to move.');
        }

        // render modal if errors occurred
        if (!empty($errorMsgs)) {
            return $this->renderAjax('modal_move', [
                        'errorMsgs' => $errorMsgs,
                        'folders' => $this->getFolderList(),
                        'contentContainer' => $this->contentContainer,
                        'selectedItems' => $selectedItems,
                        'selectedFolderId' => $destFolderId
            ]);
        } else {
            // items are only then saved, if no error occurred. Else, the move transaction is canceled.
            foreach ($selectedDatabaseItems as $item) {
                $item->save();
            }
            return $this->htmlRedirect($this->contentContainer->createUrl('/cfiles/browse', [
                                'fid' => $destFolderId
            ]));
        }
    }
}
