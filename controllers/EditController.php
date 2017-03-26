<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2015 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\cfiles\controllers;

use Yii;
use yii\web\HttpException;
use humhub\modules\cfiles\models\File;
use humhub\modules\cfiles\models\Folder;

/**
 * Description of BrowseController
 *
 * @author luke, Sebastian Stumpf
 */
class EditController extends BrowseController
{

    /**
     * Action to edit a given folder.
     *
     * @return string
     */
    public function actionFolder()
    {
        if (!$this->canWrite()) {
            throw new HttpException(401, Yii::t('CfilesModule.base', 'Insufficient rights to execute this action.'));
        }

        $folder = \humhub\modules\cfiles\models\FileSystemItem::getItemById(Yii::$app->request->get('id'));

        if (Yii::$app->request->get('cancel')) {
            return $this->renderAjaxContent($folder->getWallOut());
        }

        // create new folder if no folder was found or folder is not editable.
        if (!$folder || !$folder->isEditableFolder($folder)) {
            $folder = new Folder(['parent_folder_id' => $this->getCurrentFolder()->id]);
            $folder->content->container = $this->contentContainer;
        }

        if ($folder->load(Yii::$app->request->post()) && $folder->save()) {
            $this->view->saved();
            return $this->htmlRedirect($this->contentContainer->createUrl('/cfiles/browse/index', ['fid' => $folder->id]));
        }

        // if it could not be saved successfully, or the formular was empty, render the edit folder modal
        return $this->renderPartial('modal_edit_folder', [
                    'folder' => $folder,
                    'contentContainer' => $this->contentContainer,
                    'currentFolderId' => $this->getCurrentFolder()->id,
        ]);
    }

    /**
     * Action to edit a given file.
     *
     * @return string
     */
    public function actionFile()
    {
        if (!$this->canWrite()) {
            throw new HttpException(401, Yii::t('CfilesModule.base', 'Insufficient rights to execute this action.'));
        }

        $fromWall = Yii::$app->request->get('fromWall');
        $file = \humhub\modules\cfiles\models\FileSystemItem::getItemById(Yii::$app->request->get('id'));

        // if not return cause this should not happen
        if (empty($file) || !($file instanceof File)) {
            throw new HttpException(401, Yii::t('CfilesModule.base', 'Cannot edit non existing file.'));
        }

        if (Yii::$app->request->get('cancel')) {
            return $this->renderAjaxContent($file->getWallOut());
        }

        // if there is no folder with the same name, try to save the current folder
        if ($file->load(Yii::$app->request->post()) && $file->save()) {
            if ($fromWall) {
                return $this->asJson(['success' => true]);
            } else {
                $this->view->saved();
                return $this->htmlRedirect($this->contentContainer->createUrl('/cfiles/browse/index', ['fid' => $file->parent_folder_id]));
            }
        }

        // if it could not be saved successfully, or the formular was empty, render the edit folder modal
        return $this->renderPartial('modal_edit_file', [
                    'file' => $file,
                    'contentContainer' => $this->contentContainer,
                    'currentFolderId' => $file->parent_folder_id,
                    'fromWall' => $fromWall
        ]);
    }

}
