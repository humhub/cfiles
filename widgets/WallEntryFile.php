<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2015 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\cfiles\widgets;

use humhub\modules\file\converter\PreviewImage;
use humhub\modules\cfiles\models\File;
use humhub\libs\MimeHelper;

/**
 * @inheritdoc
 */
class WallEntryFile extends \humhub\modules\content\widgets\WallEntry
{

    /**
     * @inheritdoc
     */
    public $editRoute = "/cfiles/edit/file";

    /**
     * @inheritdoc
     */
    public $editMode = self::EDIT_MODE_MODAL;

    /**
     * @inheritdoc
     */
    public function run()
    {
        $cFile = $this->contentObject;

        $folderUrl = '#';
        if ($cFile->parentFolder !== null) {
            $folderUrl = $cFile->parentFolder->getUrl();
        }

        return $this->render('wallEntryFile', [
                    'cFile' => $cFile,
                    'fileName' => $cFile->getTitle(),
                    'fileSize' => $cFile->getSize(),
                    'file' => $cFile->baseFile,
                    'previewImage' => new PreviewImage(),
                    'folderUrl' => $folderUrl,
                    'mimeIconClass' => MimeHelper::getMimeIconClassByExtension($cFile->baseFile)
        ]);
    }

    /**
     * Returns the edit url to edit the content (if supported)
     *
     * @return string url
     */
    public function getEditUrl()
    {
        if (parent::getEditUrl() === "") {
            return "";
        }
        if ($this->contentObject instanceof File) {
            return $this->contentObject->content->container->createUrl($this->editRoute, ['id' => $this->contentObject->getItemId(), 'fromWall' => true]);
        }
        return "";
    }

}
