<?php

namespace cfiles\functional;

use humhub\modules\cfiles\services\FolderTreeService;
use cfiles\FunctionalTester;
use humhub\modules\cfiles\models\Folder;
use humhub\modules\content\models\Content;
use humhub\modules\space\models\Space;
use Yii;

class RootFolderOwnerCest
{
    public function testRepairsRootFolderOwner(FunctionalTester $I)
    {
        $space = Space::findOne(2);
        $I->enableModule(2, 'cfiles');

        $rootFolder = FolderTreeService::getOrInitRoot($space);
        $rootFolder->content->created_by = 1;
        $rootFolder->content->save(false, ['created_by']);

        Yii::$app->installationState->setInstalled();
        $I->switchIdentity('User1');
        $I->amOnSpace2('/cfiles/browse');
        $I->seeResponseCodeIs(200);
        $I->seeResponseCodeIs(200);

        $I->seeRecord(Content::class, [
            'id' => $rootFolder->content->id,
            'created_by' => $space->created_by,
        ]);
    }

    public function testRepairsMissingRootFolder(FunctionalTester $I)
    {
        $space = Space::findOne(2);
        $I->enableModule(2, 'cfiles');

        $rootFolder = FolderTreeService::getOrInitRoot($space);
        Yii::$app->db->createCommand()
            ->delete(Content::tableName(), [
                'object_model' => Folder::class,
                'object_id' => $rootFolder->id,
            ])
            ->execute();
        Yii::$app->db->createCommand()
            ->delete(Folder::tableName(), ['id' => $rootFolder->id])
            ->execute();

        Yii::$app->installationState->setInstalled();
        $I->switchIdentity('User1');
        $I->amOnSpace2('/cfiles/browse');
        $I->seeResponseCodeIs(200);
        $I->seeResponseCodeIs(200);

        $newRootFolder = FolderTreeService::getRoot($space);
        $I->seeRecord(Folder::class, [
            'id' => $newRootFolder->id,
            'type' => Folder::TYPE_FOLDER_ROOT,
        ]);
        $I->seeRecord(Content::class, [
            'id' => $newRootFolder->content->id,
            'created_by' => $space->created_by,
        ]);
    }
}
