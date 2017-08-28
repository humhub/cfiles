<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2015 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\cfiles\controllers;

use humhub\modules\cfiles\models\FileSystemItem;
use humhub\modules\cfiles\permissions\WriteAccess;
use humhub\modules\content\models\Content;
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
    public function getAccessRules()
    {
        return [
            ['permission' => [WriteAccess::class]]
        ];
    }

    /**
     * Action to edit a given folder.
     *
     * @return string
     */
    public function actionFolder($id = null, $visibility = Content::VISIBILITY_PRIVATE)
    {
        $folder = FileSystemItem::getItemById($id);

        // create new folder if no folder was found or folder is not editable.
        if (!$folder || !($folder instanceof Folder) || !$folder->isEditableFolder($folder)) {
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
    public function actionFile($id, $fromWall = 0)
    {
        $file = FileSystemItem::getItemById($id);

        // if not return cause this should not happen
        if (empty($file) || !($file instanceof File)) {
            throw new HttpException(404, Yii::t('CfilesModule.base', 'Cannot edit non existing file.'));
        }

        if ($file->baseFile->load(Yii::$app->request->post()) && $file->baseFile->validate()) {
            // check for duplicate
            $dup = File::getFileByName($file->baseFile->file_name, $file->parent_folder_id, $this->contentContainer);
            if ($dup && $dup->id !== $file->id) {
                $file->baseFile->addErrors(['file_name' => Yii::t('CfilesModule.base', 'A file with that name already exists in this folder.')]);
            } elseif ($file->load(Yii::$app->request->post()) && $file->save()) {
                if ($fromWall) {
                    return $this->asJson(['success' => true]);
                } else {
                    $this->view->saved();
                    return $this->htmlRedirect($this->contentContainer->createUrl('/cfiles/browse/index', ['fid' => $file->parent_folder_id]));
                }
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
