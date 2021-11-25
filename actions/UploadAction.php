<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2017 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 *
 */

namespace humhub\modules\cfiles\actions;

use humhub\modules\cfiles\models\FileSystemItem;
use humhub\modules\file\libs\FileHelper;
use Yii;
use yii\web\UploadedFile;

/**
 * Created by PhpStorm.
 * User: buddha
 * Date: 17.08.2017
 * Time: 18:25
 */

/**
 * Class UploadAction
 *
 */
class UploadAction extends \humhub\modules\file\actions\UploadAction
{
    /**
     * @var \humhub\modules\cfiles\controllers\BrowseController
     */
    public $controller;

    public function run()
    {
        $result = parent::run();
        $result['fileList'] = $this->controller->renderFileList();
        return $result;
    }

    protected function handleFileUpload(UploadedFile $uploadedFile, $hideInStream = false)
    {
        $folder = $this->controller->getCurrentFolder();

        $file = $folder->addUploadedFile($uploadedFile);

        if($file->hasErrors()) {
            return $this->getValidationErrorResponse($file);
        }

        if($file->baseFile->hasErrors()) {
            return $this->getErrorResponse($file->baseFile);
        }

        return array_merge(['error' => false], FileHelper::getFileInfos($file->baseFile));
    }

    protected function getValidationErrorResponse(FileSystemItem $file)
    {
        $errorMessage = Yii::t('FileModule.actions_UploadAction', 'File {fileName} could not be uploaded!', ['fileName' => $file->baseFile->name]);

        if(!empty($file->hasErrors())) {
            $errorMessage = $file->getErrorSummary(false);
        }

        return [
            'error' => true,
            'errors' => $errorMessage,
            'name' => $file->baseFile->name,
            'size' => $file->baseFile->size
        ];
    }
}