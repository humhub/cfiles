<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2017 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 *
 */

namespace cfiles\acceptance;

use cfiles\AcceptanceTester;

class VisibilityCest
{
    public function testVisibility(AcceptanceTester $I)
    {
        $I->amAdmin();
        $I->wantToTest('the visibility of folders');
        $I->amGoingTo('install the cfiles module for space 1');
        $I->enableCfilesOnSpace();

        $I->createFolder('visibility', 'visibility test');
        $I->expect('The new folder to be private');
        $I->seeElement('.folder-visibility .fa-lock');

        $I->uploadFile();
        $I->expect('The new file to be private');
        $I->seeElement('[data-cfiles-item] .fa-lock');

        $I->click('Add directory', '.files-action-menu');

        // Create another folder
        $I->waitForText('Create folder', null,'#globalModal');
        $I->fillField('Folder[title]', 'visibility2');
        $I->fillField('Folder[description]', 'visibility2');

        $I->expect('The folder visibility to be private');
        $I->seeElement('[for="folder-visibility"] .regular-checkbox-box.disabled');

        $I->click('Save', '#globalModal');
        $I->waitForText('This folder is empty.');
        $I->seeElement('.folder-visibility .fa-lock');
        $I->uploadFile();
        $I->expect('The new file to be private');
        $I->seeElement('[data-cfiles-item] .fa-lock');

        $I->amUser1(true);
        $I->amOnSpace(1, '/cfiles/browse');
        $I->expect('Not to see the files entry since there are no public files available');
        $I->see('Files from the stream', '#fileList');

        $I->amAdmin(true);
        $I->amOnSpace(1, '/cfiles/browse');

        $I->amGoingTo('set the folder visibility to public');
        $I->clickFolderContext(3, 'Edit');
        $I->waitForText('Edit folder', null, '#globalModal');
        $I->click('[for="folder-visibility"]');
        $I->click('Save', '#globalModal');
        $I->waitForText('visibility2', null, '#fileList');

        $I->expect('all subfiles and subfolders to be public too');
        $I->seeElement('.folder-visibility .fa-unlock-alt');
        $I->seeElement('[data-cfiles-item="folder_4"] .fa-unlock-alt');
        $I->seeElement('[data-cfiles-item="file_1"] .fa-unlock-alt');

        $I->click('visibility2', '#fileList');
        $I->waitForText('visibility2', null,'#cfiles-crumb');
        $I->seeElement('[data-cfiles-item="file_2"] .fa-unlock-alt');

        $I->amGoingTo('Reset the file visibility of /visibility/visibility2/test.txt to private');
        $I->clickFileContext(2, 'Edit');
        $I->waitForText('Edit file', null, '#globalModal');
        $I->jsClick('input[type="checkbox"][name="File[visibility]"]');
        $I->click('Save', '#globalModal');

        $I->seeSuccess();
        $I->waitForElementVisible('[data-cfiles-item="file_2"] .fa-lock');

        $I->amUser1(true);
        $I->amOnSpace(1, '/cfiles/browse');
        $I->seeElement('#fileList');
        $I->see('visibility');
        $I->click('visibility', '#fileList');
        $I->waitForText('visibility2',null,'#fileList');
        $I->see('test.txt', '#fileList');
        $I->click('visibility2', '#fileList');
        $I->waitForText('visibility2',null,'#cfiles-crumb');
        $I->dontSee('test.txt', '#fileList');
    }
}