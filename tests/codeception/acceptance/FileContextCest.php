<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2017 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 *
 */

namespace cfiles\acceptance;

use cfiles\AcceptanceTester;

class FileContextCest
{
    public function testFolderContext(AcceptanceTester $I)
    {
        $I->amAdmin();
        $I->wantToTest('the folder context menu');
        $I->amGoingTo('install the cfiles module for space 1');
        $I->enableCfilesOnSpace();

        $I->uploadFile();
        $I->wait(1);

        $I->amGoingTo('edit my file per context menu');
        $I->clickFileContext(1, 'Edit');
        $I->waitForText('Edit file', null, '#globalModal');
        $I->fillField('File[file_name]', 'newFile.txt');
        $I->click('Save', '#globalModal');

        $I->seeInFileList('newFile.txt');

        $I->amGoingTo('move my file per context menu');
        $I->createFolder('move');
        $I->amInRoot();
        $I->clickFileContext(1, 'Move');
        $I->waitForText('Move files', null, '#globalModal');
        $I->click('[data-id="3"]');
        $I->click('Save', '#globalModal');
        $I->seeInCrumb('move');
        $I->seeInFileList('newFile.txt');

        $I->amGoingTo('delete my file per context menu');
        $I->seeElement('[data-cfiles-item="file_1"]');
        $I->clickFileContext(1, 'Delete');
        $I->waitForElementVisible('#globalModalConfirm', 5);
        $I->see('Confirm delete file');
        $I->click('Delete', '#globalModalConfirm');
        $I->waitForElementNotVisible('[data-cfiles-item="file_1"]');

        $I->amGoingTo('show post of my file');
        $I->uploadFile();
        $I->clickFileContext(2, 'Show Post');
        $I->waitForElementVisible('#wallStream');
        $I->waitForText('test.txt', null,'[data-stream-entry]');
    }
}