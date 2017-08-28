<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2017 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 *
 */

/**
 * Created by PhpStorm.
 * User: buddha
 * Date: 26.08.2017
 * Time: 18:56
 */

namespace humhub\modules\cfiles\actions;


use humhub\modules\cfiles\libs\ZipExtractor;
use humhub\modules\cfiles\models\FileSystemItem;
use Yii;
use yii\web\UploadedFile;

class UploadZipAction extends UploadAction
{
    protected function handleFileUpload(UploadedFile $uploadedFile, $hideInStream = false)
    {
        $zip = new ZipExtractor();
        $file = $zip->extract($this->controller->getCurrentFolder(), $uploadedFile);

        if($file->hasErrors()) {
            return $this->getValidationErrorResponse($file);
        }

        return ['error' => false];
    }

    protected function getValidationErrorResponse(FileSystemItem $file)
    {
        $errorMessage = Yii::t('FileModule.actions_UploadAction', 'File {fileName} could not be uploaded!', ['fileName' => $file->baseFile->name]);

        if (!empty($file->hasErrors())) {
            $errorMessage = array_values($file->getErrors())[0];
        }

        return [
            'error' => true,
            'errors' => $errorMessage,
            'name' => $file->baseFile->name,
            'size' => $file->baseFile->size
        ];
    }
}