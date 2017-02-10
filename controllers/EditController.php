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

        $itemId = Yii::$app->request->get('id');
        $fromWall = Yii::$app->request->get('fromWall');
        $folder = $this->module->getItemById($itemId);
        $cancel = Yii::$app->request->get('cancel');

        if ($cancel) {
            return $this->renderAjaxContent($folder->getWallOut());
        }

        // the new / edited folders title
        $title = trim(Yii::$app->request->post('Folder')['title']);
        Yii::$app->request->post('Folder')['title'] = $title;

        // if not a folder has to be created
        if (empty($folder) || !($folder instanceof Folder)) {
            $titleChanged = true;
            // create a new folder
            $folder = new Folder();
            $folder->content->container = $this->contentContainer;
            $folder->parent_folder_id = $this->getCurrentFolder()->id;
        } else {
            $titleChanged = $title !== $folder->title;
        }
        // check if a folder with the given parent id and title exists
        $query = Folder::find()->contentContainer($this->contentContainer)
                ->readable()
                ->where([
            'cfiles_folder.title' => $title,
            'cfiles_folder.parent_folder_id' => $folder->parent_folder_id
        ]);
        $similarFolder = $query->one();

        // if a similar folder exists and a new folder should be created, add an error to the model.
        if (!empty($similarFolder) && $titleChanged) {
            $folder->title = $title;
            $folder->addError('title', \Yii::t('CfilesModule.base', 'A folder with this name already exists.'));
        } elseif ($folder->load(Yii::$app->request->post()) && $folder->validate() && $folder->save()) {
            // if there is no folder with the same name, try to save the current folder
            if ($fromWall) {
                return $this->renderAjaxContent($folder->getWallOut([
                                    'justEdited' => true
                ]));
            } else {
                return $this->redirect($this->contentContainer->createUrl('/cfiles/browse/index', [
                                    'fid' => $folder->id
                ]));
            }
        }

        // if it could not be saved successfully, or the formular was empty, render the edit folder modal
        return $this->renderPartial(($fromWall ? 'wall_edit_folder' : 'modal_edit_folder'), [
                    'folder' => $folder,
                    'contentContainer' => $this->contentContainer,
                    'currentFolderId' => $this->getCurrentFolder()->id,
                    'fromWall' => $fromWall
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

        $itemId = Yii::$app->request->get('id');
        $fromWall = Yii::$app->request->get('fromWall');
        $file = $this->module->getItemById($itemId);
        $cancel = Yii::$app->request->get('cancel');

        if ($cancel) {
            return $this->renderAjaxContent($file->getWallOut());
        }

        // if not return cause this should not happen
        if (empty($file) || !($file instanceof File)) {
            throw new HttpException(401, Yii::t('CfilesModule.base', 'Cannot edit non existing file.'));
        }

        // if there is no folder with the same name, try to save the current folder
        if ($file->load(Yii::$app->request->post()) && $file->validate() && $file->save()) {
            if ($fromWall) {
                return $this->renderAjaxContent($file->getWallOut([
                                    'justEdited' => true
                ]));
            } else {
                return $this->htmlRedirect($this->contentContainer->createUrl('/cfiles/browse/index', [
                                    'fid' => $this->getCurrentFolder()->id
                ]));
            }
        }

        // if it could not be saved successfully, or the formular was empty, render the edit folder modal
        return $this->renderPartial(($fromWall ? 'wall_edit_file' : 'modal_edit_file'), [
                    'file' => $file,
                    'contentContainer' => $this->contentContainer,
                    'currentFolderId' => $this->getCurrentFolder()->id,
                    'fromWall' => $fromWall
        ]);
    }

    private function isEditable($item)
    {
        if ($item === null) {
            return false;
        }
        if ($item instanceof Folder) {
            if ($item->isRoot() || $item->isAllPostedFiles()) {
                return false;
            }
        } elseif ($item instanceof \humhub\modules\file\models\File) {
            return false;
        }
        return true;
    }

}
