<?php

namespace cfiles\api;

use cfiles\ApiTester;
use tests\codeception\_support\HumHubApiTestCest;

class FileCest extends HumHubApiTestCest
{
    public function testFindByContainer(ApiTester $I)
    {
        if (!$this->isRestModuleEnabled()) {
            return;
        }

        $I->wantTo('find by container');
        $I->amAdmin();
        $I->seePaginationFilesResponse('cfiles/files/container/1', []);

        $I->createSampleFile();
        $I->seePaginationFilesResponse('cfiles/files/container/1', [1]);
    }

    public function testUploadFiles(ApiTester $I)
    {
        if (!$this->isRestModuleEnabled()) {
            return;
        }

        $I->wantTo('upload files');
        $I->amAdmin();

        $I->createFolder('Root');
        $I->seeLastCreatedFolderDefinition();

        $I->uploadFiles(['test.txt', 'test.zip']);
        $I->seeSuccessMessage('Files successfully uploaded!');
    }

    public function testGetFileById(ApiTester $I)
    {
        if (!$this->isRestModuleEnabled()) {
            return;
        }

        $I->wantTo('get file by id');
        $I->amAdmin();

        $I->createSampleFile();
        $I->sendGet('cfiles/file/1');
        $I->seeFileDefinitionById(1);
    }

    public function testDeleteFileById(ApiTester $I)
    {
        if (!$this->isRestModuleEnabled()) {
            return;
        }

        $I->wantTo('delete file by id');
        $I->amAdmin();

        $I->sendDelete('cfiles/file/1');
        $I->seeNotFoundMessage('Content record not found!');

        $I->createSampleFile();
        $I->sendDelete('cfiles/file/1');
        $I->seeSuccessMessage('Successfully deleted!');
    }

}
