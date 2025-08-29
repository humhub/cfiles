<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2019 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\cfiles\controllers\rest;

use humhub\modules\cfiles\helpers\RestDefinitions;
use humhub\modules\cfiles\models\File;
use humhub\modules\cfiles\models\Folder;
use humhub\modules\cfiles\permissions\ManageFiles;
use humhub\modules\content\components\ContentActiveRecord;
use humhub\modules\content\components\ContentContainerActiveRecord;
use humhub\modules\content\models\ContentContainer;
use humhub\modules\rest\components\BaseContentController;
use Yii;
use yii\web\UploadedFile;

class FileController extends BaseContentController
{
    /**
     * {@inheritdoc}
     */
    public function getContentActiveRecordClass()
    {
        return File::class;
    }

    /**
     * {@inheritdoc}
     */
    public function returnContentDefinition(ContentActiveRecord $contentRecord)
    {
        /** @var File $contentRecord */
        return RestDefinitions::getFile($contentRecord);
    }

    public function actionUpload($containerId)
    {
        $containerRecord = ContentContainer::findOne(['id' => $containerId]);
        if ($containerRecord === null) {
            return $this->returnError(404, 'Content container not found!');
        }
        /** @var ContentContainerActiveRecord $container */
        $container = $containerRecord->getPolymorphicRelation();

        $folderId = Yii::$app->request->post('folder_id', null);
        if (is_null($folderId)) {
            return $this->returnError(400, 'Target folder id is required!');
        }

        $targetDir = Folder::find()->contentContainer($container)->andWhere(['cfiles_folder.id' => $folderId])->one();
        if ($targetDir === null) {
            return $this->returnError(404, 'cFiles folder not found!');
        }
        if (!$container->can(ManageFiles::class)) {
            return $this->returnError(403, 'You cannot upload files into this folder!');
        }

        $files = UploadedFile::getInstancesByName('files');

        if (empty($files)) {
            return $this->returnError(400, 'No files to upload.');
        }

        foreach ($files as $file) {
            $file = $targetDir->addUploadedFile($file);

            if ($file->hasErrors() || $file->baseFile->hasErrors()) {
                return $this->returnError(422, "File {$file->baseFile->name} could not be uploaded!", [
                    'errors' => array_merge($file->getErrors(), $file->baseFile->getErrors()),
                ]);
            }
        }

        return $this->returnSuccess('Files successfully uploaded!');
    }
}
