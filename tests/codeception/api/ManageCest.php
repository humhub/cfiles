<?php

namespace cfiles\api;

use cfiles\ApiTester;
use tests\codeception\_support\HumHubApiTestCest;

class ManageCest extends HumHubApiTestCest
{
    public function testMakePublic(ApiTester $I)
    {
        if (!$this->isRestModuleEnabled()) {
            return;
        }

        $I->wantTo('make items as public');
        $I->amAdmin();
        $I->createSampleFile();

        $I->sendPatch('cfiles/items/container/1/make-public', [
            'selection' => ['folder_1', 'file_1'],
        ]);
        $I->seeSuccessMessage('Items successfully marked public!');
    }

    public function testMakePrivate(ApiTester $I)
    {
        if (!$this->isRestModuleEnabled()) {
            return;
        }

        $I->wantTo('make items as private');
        $I->amAdmin();
        $I->createSampleFile();

        $I->sendPatch('cfiles/items/container/1/make-private', [
            'selection' => ['folder_1', 'file_1'],
        ]);
        $I->seeSuccessMessage('Items successfully marked private!');
    }

    public function testMoveDelete(ApiTester $I)
    {
        if (!$this->isRestModuleEnabled()) {
            return;
        }

        $I->wantTo('move and delete items');
        $I->amAdmin();

        $I->createFolder('First folder'); // folder id = 2, (root folder id = 1)
        $I->createFolder('Sub folder 1', ['target_id' => 2]); // folder id = 3
        $I->uploadFiles(['test.txt', 'test.zip'], ['folder_id' => 3]); // file ids = 1, 2
        $I->createFolder('Sub folder 2', ['target_id' => 2]); // folder id = 4
        $I->createFolder('Sub-sub folder 1', ['target_id' => 3]); // folder id = 5

        $I->sendPost('cfiles/items/container/1/move', [
            'source_id' => 5,
            'MoveForm' => ['destId' => 2],
            'selection' => ['folder_4', 'file_1', 'file_2'],
        ]);
        $I->seeSuccessMessage('Items successfully moved.');

        $I->sendDelete('cfiles/items/container/1/delete', [
            'selection' => ['file_1', 'file_2', 'folder_5', 'folder_4', 'folder_3', 'folder_2'],
        ]);
        $I->seeSuccessMessage('Selected items are successfully deleted!');
    }

}
