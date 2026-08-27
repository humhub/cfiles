<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2017 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\cfiles\controllers;

use humhub\modules\cfiles\models\File;
use humhub\modules\cfiles\models\FileSystemItem;
use humhub\modules\cfiles\models\Folder;
use humhub\modules\cfiles\serializers\FileSerializer;
use humhub\modules\cfiles\serializers\FolderSerializer;
use humhub\modules\content\components\ContentContainerController;
use yii\web\HttpException;

/**
 * The edit dialog reached from a wall entry in the stream.
 *
 * The file browser edits inline — it opens `ItemForm` in a Vue modal of its own and never
 * comes here. The stream is still server-rendered, though, and its wall entry controls open
 * an edit URL as a modal (`WallEntryFile::$editRoute`, `EDIT_MODE_MODAL`), so that URL has to
 * keep answering with a modal.
 *
 * It answers with the SAME island the browser uses, wrapped in a modal shell. There is one
 * edit form in this module, not two.
 *
 * @author luke, Sebastian Stumpf
 */
class EditController extends ContentContainerController
{
    public function actionFile($id)
    {
        $file = File::find()->readable()->where(['cfiles_file.id' => (int)$id])->one();

        return $this->renderItem($file, FileSerializer::file(...));
    }

    public function actionFolder($id)
    {
        $folder = Folder::find()->readable()->where(['cfiles_folder.id' => (int)$id])->one();

        return $this->renderItem($folder, FolderSerializer::folder(...));
    }

    private function renderItem(?FileSystemItem $item, callable $serialize): string
    {
        if ($item === null || $item->content->container->id !== $this->contentContainer->id) {
            throw new HttpException(404);
        }

        if (!$item->content->canEdit()) {
            throw new HttpException(403);
        }

        return $this->renderAjax('modal', ['item' => $serialize($item)]);
    }
}
