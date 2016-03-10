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
class EditController extends BrowseController
{

    /**
     * Action to edit a given folder (the folders name).
     * @return string
     */
    public function actionIndex()
    {
        if(!$this->canWrite()) {
            throw new HttpException(401, Yii::t('CfilesModule.base', 'Insufficient rights to execute this action.'));
        }

        // id is set if a folder should be edited
        $id = (int) Yii::$app->request->get('id');

        // check if a folder with the given id exists.
        $query = Folder::find()->contentContainer($this->contentContainer)
                ->readable()
                ->where([
            'cfiles_folder.id' => $id
        ]);
        $folder = $query->one();
        
        // if not a folder has to be created
        if (empty($folder)) {
            // create a new folder
            $folder = new Folder();
            $folder->content->container = $this->contentContainer;
            $folder->parent_folder_id = $this->getCurrentFolder()->id;
        }

        // the new / edited folders title
        $title = trim(Yii::$app->request->post('Folder')['title']);
        Yii::$app->request->post('Folder')['title'] = $title;
        
        // check if a folder with the given parent id and title exists
        $query = Folder::find()->contentContainer($this->contentContainer)
                ->readable()
                ->where([
            'cfiles_folder.title' => $title,
            'cfiles_folder.parent_folder_id' => $this->getCurrentFolder()->id
        ]);
        $similarFolder = $query->one();
        
        // if there is no folder with the same name, try to save the current folder
        if (empty($similarFolder) && $folder->load(Yii::$app->request->post()) && $folder->validate() && $folder->save()) {
            return $this->htmlRedirect($this->contentContainer->createUrl('/cfiles/browse/index', [
                'fid' => $folder->id
            ]));
        }

        // if a similar folder exists, add an error to the model.
        if (!empty($similarFolder)) {
            $folder->title = $title;
            $folder->addError('title', \Yii::t('CfilesModule.base', 'A folder with this name already exists.'));
        }

        // if it could not be saved successfully, or the formular was empty, render the edit folder modal
        return $this->renderAjax('modal_edit', [
                    'folder' => $folder,
                    'contentContainer' => $this->contentContainer,
                    'currentFolderId' => $this->getCurrentFolder()->id
        ]);
    }
}
