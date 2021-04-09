<?php

namespace cfiles\api;

use cfiles\ApiTester;
use tests\codeception\_support\HumHubApiTestCest;

class FolderCest extends HumHubApiTestCest
{
    public function testFindByContainer(ApiTester $I)
    {
        if (!$this->isRestModuleEnabled()) {
            return;
        }

        $I->wantTo('find by container');
        $I->amAdmin();
        $I->seePaginationFoldersResponse('cfiles/folders/container/1', []);

        $I->createSampleFolder();
        $I->seePaginationFoldersResponse('cfiles/folders/container/1', [1, 2]);
    }

    public function testCreateFolder(ApiTester $I)
    {
        if (!$this->isRestModuleEnabled()) {
            return;
        }

        $I->wantTo('create a folder');
        $I->amAdmin();

        $I->createFolder('New folder');
        $I->seeLastCreatedFolderDefinition();
    }

    public function testGetFolderById(ApiTester $I)
    {
        if (!$this->isRestModuleEnabled()) {
            return;
        }

        $I->wantTo('get a folder by id');
        $I->amAdmin();

        $I->createSampleFolder();
        $I->sendGet('cfiles/folder/2');
        $I->seeFolderDefinitionById(2);
    }

    public function testUpdateFolder(ApiTester $I)
    {
        if (!$this->isRestModuleEnabled()) {
            return;
        }

        $I->wantTo('update a folder');
        $I->amAdmin();

        $I->sendPut('cfiles/folder/2');
        $I->seeNotFoundMessage('cFiles folder not found!');

        $I->createSampleFolder();
        $I->sendPut('cfiles/folder/2', [
            'Folder' => [
                'title' => 'Updated title',
                'description' => 'Updated description',
                'visibility' => 1,
            ],
        ]);
        $I->seeFolderDefinitionById(2);
    }

    public function testDeleteFolder(ApiTester $I)
    {
        if (!$this->isRestModuleEnabled()) {
            return;
        }

        $I->wantTo('delete a folder');
        $I->amAdmin();

        $I->sendDelete('cfiles/folder/2');
        $I->seeNotFoundMessage('Content record not found!');

        $I->createSampleFolder();
        $I->sendDelete('cfiles/folder/2');
        $I->seeSuccessMessage('Successfully deleted!');
    }

}
