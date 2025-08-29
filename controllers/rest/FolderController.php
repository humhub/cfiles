<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2019 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\cfiles\controllers\rest;

use humhub\modules\cfiles\helpers\RestDefinitions;
use humhub\modules\cfiles\models\Folder;
use humhub\modules\cfiles\permissions\ManageFiles;
use humhub\modules\content\components\ContentActiveRecord;
use humhub\modules\content\components\ContentContainerActiveRecord;
use humhub\modules\content\models\Content;
use humhub\modules\content\models\ContentContainer;
use humhub\modules\rest\components\BaseContentController;
use Yii;

class FolderController extends BaseContentController
{
    /**
     * {@inheritdoc}
     */
    public function getContentActiveRecordClass()
    {
        return Folder::class;
    }

    /**
     * {@inheritdoc}
     */
    public function returnContentDefinition(ContentActiveRecord $contentRecord)
    {
        /** @var Folder $contentRecord */
        return RestDefinitions::getFolder($contentRecord);
    }

    public function actionCreate($containerId)
    {
        $containerRecord = ContentContainer::findOne(['id' => $containerId]);
        if ($containerRecord === null) {
            return $this->returnError(404, 'Content container not found!');
        }
        /** @var ContentContainerActiveRecord $container */
        $container = $containerRecord->getPolymorphicRelation();
        $params = Yii::$app->request->getBodyParams();
        $isPublicDirectory = isset($params['Folder']['visibility']) && $params['Folder']['visibility'] === Content::VISIBILITY_PUBLIC;

        if (empty($params['target_id'])) {
            if (!($targetDir = Folder::getRoot($container)) &&
                !($targetDir = Folder::initRoot($container))) {
                return $this->returnError(400, 'Target folder id is required!');
            }
        } else {
            $targetDir = Folder::findOne(['id' => $params['target_id']]);
        }

        if (empty($targetDir)) {
            return $this->returnError(404, 'cFiles folder not found!');
        }
        if (!$container->can(ManageFiles::class)) {
            return $this->returnError(403, 'You cannot create folder in this container!');
        }

        if ((! $targetDir->isRoot() && $targetDir->content->isPrivate()) && $isPublicDirectory) {
            return $this->returnError(403, 'Could not create public directory inside private directory!');
        }

        $folder = $targetDir->newFolder();
        $folder->content->container = $container;

        if ($folder->load($params) && $folder->save()) {
            return RestDefinitions::getFolder(Folder::findOne(['id' => $folder->id]));
        }

        if ($folder->hasErrors()) {
            return $this->returnError(422, 'Validation failed', [
                'errors' => $folder->getErrors(),
            ]);
        } else {
            Yii::error('Could not create valid folder.', 'api');
            return $this->returnError(500, 'Internal error while create folder!');
        }
    }

    public function actionUpdate($id)
    {
        $folder = Folder::findOne(['id' => $id]);

        if ($folder === null) {
            return $this->returnError(404, 'cFiles folder not found!');
        }
        if (!$folder->content->canEdit()) {
            return $this->returnError(403, 'You cannot edit this folder!');
        }

        if ($folder->load(Yii::$app->request->getBodyParams()) && $folder->save()) {
            return RestDefinitions::getFolder(Folder::findOne(['id' => $folder->id]));
        }

        if ($folder->hasErrors()) {
            return $this->returnError(422, 'Validation failed', [
                'errors' => $folder->getErrors(),
            ]);
        } else {
            Yii::error('Could not update valid folder.', 'api');
            return $this->returnError(500, 'Internal error while update cFiles folder!');
        }
    }
}
