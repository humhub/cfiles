<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2017 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 *
 */

namespace cfiles\acceptance;

use cfiles\AcceptanceTester;

class FolderContextCest
{
    public function testFolderContext(AcceptanceTester $I)
    {
        $I->amAdmin();
        $I->wantToTest('the visibility of folders');
        $I->amGoingTo('install the cfiles module for space 1');
        $I->enableCfilesOnSpace();

        $I->createFolder('context', 'context test');
        $I->uploadFile();

        $I->amInRoot();

        $I->amGoingTo('test the folder open context item');
        $I->clickFolderContext(3, 'Open');
        $I->seeInCrumb('context');

        $I->amInRoot();

        $I->amGoingTo('test the folder edit context item');
        $I->clickFolderContext(3, 'Edit');
        $I->waitForText('Edit folder', null, '#globalModal');
        $I->fillField('Folder[title]', 'context2');
        $I->click('Save', '#globalModal');

        $I->uploadFile();

        $I->amInRoot();
        $I->seeInFileList('context2');

        $I->amGoingTo('test the folder move context item');

        $I->createFolder('context1', 'context test');
        $I->amInRoot();
        $I->clickFolderContext(3, 'Move');

        $I->waitForText('Move files', null, '#globalModal');
        $I->click('[data-id="4"]');
        $I->click('Save', '#globalModal');

        $I->seeInCrumb('context1');
        $I->seeInFileList('context2');
        $I->openFolder('context2');
        $I->seeInFileList('test.txt');

        $I->amGoingTo('test the folder delete context item');
        $I->amInRoot();
        $I->seeElement('[data-cfiles-item="folder_4"]');
        $I->clickFolderContext(4, 'Delete');
        $I->waitForElementVisible('#globalModalConfirm', 5);
        $I->see('Confirm delete file');
        $I->click('Delete', '#globalModalConfirm');
        $I->waitForElementNotVisible('[data-cfiles-item="folder_4"]');


    }
}