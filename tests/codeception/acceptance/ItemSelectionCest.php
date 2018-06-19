<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2017 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 *
 */

namespace cfiles\acceptance;

use cfiles\AcceptanceTester;

class ItemSelectionCest
{
    public function testFolderContext(AcceptanceTester $I)
    {
        $I->amAdmin();
        $I->wantToTest('the visibility of folders');
        $I->amGoingTo('install the cfiles module for space 1');
        $I->enableCfilesOnSpace();

        $I->uploadFile();
        $I->createFolder('test');

        $I->amInRoot();
        $I->jsClick('.allselect');
        $I->click('.chkCnt', '.files-action-menu');
        $I->click('Delete', '.files-action-menu');
        $I->waitForText('Confirm delete file',null, '#globalModalConfirm');
        $I->click('Delete', '#globalModalConfirm');

        $I->waitForElementNotVisible('[data-cfiles-item="file_1"]');
        $I->waitForElementNotVisible('[data-cfiles-item="folder_3"]');

        $I->uploadFile();
        $I->createFolder('test');

        $I->amInRoot();
        $I->jsClick('.allselect');
        $I->click('.chkCnt', '.files-action-menu');
        $I->click('Move', '.files-action-menu');
        $I->waitForText('Move files', null, '#globalModal');
        $I->click('[data-id="4"]');
        $I->click('Save', '#globalModal');
        $I->seeError('Some files could not be moved: Folder test can\'t be moved to itself!');
        $I->seeInFileList('test');
        $I->dontSeeInFileList('test.txt');
        $I->openFolder('test');

        // Move back to root
        $I->jsClick('.allselect');
        $I->click('.chkCnt', '.files-action-menu');
        $I->click('Move', '.files-action-menu');
        $I->waitForText('Move files', null, '#globalModal');
        $I->click('[data-id="1"]');
        $I->click('Save', '#globalModal');
        $I->wait(2);
        $I->waitForElementVisible('.fa-home');
        $I->seeInFileList('test.txt');
    }
}