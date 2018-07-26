<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2017 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\cfiles\controllers;

use Yii;
use yii\web\HttpException;
use humhub\modules\cfiles\models\FileSystemItem;
use humhub\modules\cfiles\models\forms\SelectionForm;
use humhub\modules\cfiles\permissions\ManageFiles;
use humhub\modules\cfiles\permissions\WriteAccess;
use humhub\modules\content\models\Content;
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
    public function actionFolder($id = null)
    {
        $folder = FileSystemItem::getItemById($id);

        if ($folder && !$folder->content->canEdit()) {
            throw new HttpException(403);
        }

        if ($folder && $folder->content->container->id !== $this->contentContainer->id) {
            throw new HttpException(404);
        }

        // create new folder if no folder was found or folder is not editable.
        if (!$folder || !($folder instanceof Folder) || !$folder->isEditableFolder($folder)) {
            $folder = $this->getCurrentFolder()->newFolder();
            $folder->content->container = $this->contentContainer;
        }

        if ($folder->load(Yii::$app->request->post()) && $folder->save()) {
            $this->view->saved();
            return $this->htmlRedirect($folder->createUrl('/cfiles/browse/index'));
        }

        return $this->renderPartial('modal_edit_folder', [
            'folder' => $folder,
            'submitUrl' => $this->getCurrentFolder()->createUrl('/cfiles/edit/folder', ['id' => $folder->getItemId()])
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

        if ($file && $file->content->container->id !== $this->contentContainer->id) {
            throw new HttpException(404);
        }

        // if not return cause this should not happen
        if (empty($file) || !($file instanceof File)) {
            throw new HttpException(404, Yii::t('CfilesModule.base', 'Cannot edit non existing file.'));
        }

        if (!$file->content->canEdit()) {
            throw new HttpException(403);
        }

        if ($file->baseFile->load(Yii::$app->request->post()) && $file->baseFile->validate()) {
            $duplicate = File::getFileByName($file->baseFile->file_name, $file->parent_folder_id, $this->contentContainer);
            if ($duplicate && !$duplicate->is($file)) {
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

        return $this->renderPartial('modal_edit_file', [
            'file' => $file,
            'submitUrl' =>  $this->contentContainer->createUrl('/cfiles/edit/file', ['fid' => $file->parent_folder_id, 'id' => $file->getItemId(), 'fromWall' => $fromWall]),
        ]);
    }

    /**
     * @return string
     */
    public function actionMakePrivate()
    {
        return $this->updateVisibility(new SelectionForm(), Content::VISIBILITY_PRIVATE);
    }

    /**
     * @return string
     */
    public function actionMakePublic()
    {
        return $this->updateVisibility(new SelectionForm(), Content::VISIBILITY_PUBLIC);
    }

    /**
     * @param SelectionForm $model
     * @param $visibility
     * @return string
     */
    private function updateVisibility(SelectionForm $model, $visibility)
    {
        foreach ($model->selection as $itemId) {
            $item = FileSystemItem::getItemById($itemId);

            if(!$item->content->canEdit()) {
                throw new HttpException(403);
            }

            if ($item && $item->content->container->id === $this->contentContainer->id) {
                $item->updateVisibility($visibility);
                $item->content->save();
            }
        }

        return $this->renderFileList();
    }

}
