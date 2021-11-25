<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2015 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\cfiles\controllers;

use humhub\modules\cfiles\models\File;
use humhub\modules\cfiles\models\FileSystemItem;
use humhub\modules\cfiles\models\Folder;
use humhub\modules\cfiles\models\forms\MoveForm;
use humhub\modules\cfiles\permissions\ManageFiles;
use Yii;

/**
 * Description of BrowseController
 *
 * @author luke, Sebastian Stumpf
 */
class MoveController extends BaseController
{

    /**
     * @inheritdoc
     */
    public function getAccessRules()
    {
        return [
            ['permission' => [ManageFiles::class]]
        ];
    }

    /**
     * Action to move files and folders from the current, to another folder.
     * @return string
     */
    public function actionIndex() //Make sure an $fid is given otherwise the root folder is used as default
    {
        $model = new MoveForm([
            'root' => $this->getRootFolder(),
            'sourceFolder' => $this->getCurrentFolder()
        ]);

        if (!$model->load(Yii::$app->request->post())) {
            return $this->renderAjax('modal_move', [
                'model' => $model,
            ]);
        }

        if($model->save()) {
            $this->view->saved();
            return $this->htmlRedirect($model->destination->createUrl('/cfiles/browse'));
        } else {
            $errorMsg = Yii::t('CfilesModule.base', 'Some files could not be moved: ');
            foreach ($model->getErrors() as $key => $errors) {
                foreach ($errors as $error) {
                    $errorMsg .= $error.' ';
                }
            }

            $this->view->error($errorMsg);
            return $this->htmlRedirect($model->sourceFolder->createUrl('/cfiles/browse'));
        }
    }

    public function actionDrop()
    {
        $this->forcePostRequest();

        $targetFolder = $this->getTargetFolder(Yii::$app->request->post('targetFolder'));
        if (!$targetFolder) {
            return $this->asJson([
                'error' => Yii::t('CfilesModule.base', 'Wrong target folder!'),
            ]);
        }

        $droppedItem = $this->getDroppedItem(Yii::$app->request->post('droppedItem'));
        if (!$droppedItem) {
            return $this->asJson([
                'error' => Yii::t('CfilesModule.base', 'Wrong moved item!'),
            ]);
        }

        // Move the dropped Item(File/Folder) to the target Folder
        if (!$targetFolder->moveItem($droppedItem)) {
            return $this->asJson([
                'error' => $droppedItem->getFirstError($droppedItem->getTitle()) ?? Yii::t('CfilesModule.base', 'Could not move the item!'),
            ]);
        }

        $resultData = [
            'movedItemName' => $droppedItem->getTitle(),
            'targetFolderName' => $targetFolder->getTitle(),
        ];
        if ($droppedItem instanceof File) {
            return $this->asJson([
                'success' => Yii::t('CfilesModule.base', 'File "{movedItemName}" has been moved into the folder "{targetFolderName}".', $resultData),
            ]);
        } else {
            return $this->asJson([
                'success' => Yii::t('CfilesModule.base', 'Folder "{movedItemName}" has been moved into the folder "{targetFolderName}".', $resultData),
            ]);
        }
    }

    private function getTargetFolder(?string $idData): ?Folder
    {
        if (!preg_match('/^folder_(\d+)$/', $idData, $targetFolderData)) {
            return null;
        }

        return Folder::find()
            ->contentContainer($this->contentContainer)
            ->where(['cfiles_folder.id' => $targetFolderData[1]])
            ->one();
    }

    private function getDroppedItem(?string $idData): ?FileSystemItem
    {
        if (!preg_match('/^(folder_|file_)(\d+)$/', $idData, $droppedItemData)) {
            return null;
        }

        /* @var FileSystemItem $droppedItem */
        switch ($droppedItemData[1]) {
            case 'folder_':
                $droppedItem = Folder::find()->where(['cfiles_folder.id' => $droppedItemData[2]]);
                break;
            case 'file_':
                $droppedItem = File::find()->where(['cfiles_file.id' => $droppedItemData[2]]);
                break;
        }

        return $droppedItem->contentContainer($this->contentContainer)->one();
    }
}
