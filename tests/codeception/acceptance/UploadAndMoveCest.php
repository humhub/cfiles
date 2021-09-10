<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2017 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 *
 */

namespace cfiles\acceptance;

use cfiles\AcceptanceTester;

class UploadAndMoveCest
{
    public function testUploadFile(AcceptanceTester $I)
    {
        $I->amAdmin();
        $I->wantToTest('the upload entry');
        $I->amGoingTo('install the cfiles module for space 1');
        $I->enableCfilesOnSpace();

        $I->attachFile('#cfilesUploadFiles', 'test.txt');
        $I->waitForText('test.txt', null, '#fileList');

        $I->wantToTest('the duplicate names entry');
        $I->attachFile('#cfilesUploadFiles', 'test.txt');
        $I->waitForText('test.txt', null, '#fileList');

        $I->wantToTest('the creation of a folder');
        $I->click('Add directory', '.files-action-menu');

        $I->waitForText('Create folder', null,'#globalModal');
        $I->fillField('Folder[title]', 'NewFolder');
        $I->click('Save', '#globalModal');

        $I->waitForText('This folder is empty.');
        $I->click('.fa-home', '#cfiles-crumb');

        $I->waitForText('NewFolder', null, '#fileList');

        $I->wantToTest('to move a file into my new folder');

        $I->jsClick('.allselect');
        $I->click('.chkCnt', '.files-action-menu');
        $I->click('.filemove-button', '.files-action-menu');

        $I->waitForText('Move files', null, '#globalModal');
        $I->click('[data-id="3"]');
        $I->click('Save', '#globalModal');
        $I->seeError('Some files could not be moved: Folder NewFolder can\'t be moved to itself!');

        $I->see('NewFolder', '#fileList');
        $I->dontSee('test.txt', '#fileList');

        $I->click('NewFolder', '#fileList');
        $I->waitForText('test.txt', null, '#fileList');
        $I->see('test.txt', null, '#fileList');
    }
}