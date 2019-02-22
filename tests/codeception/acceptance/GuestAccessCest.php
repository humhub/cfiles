<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2017 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 *
 */

namespace cfiles\acceptance;

use cfiles\AcceptanceTester;

class GuestAccessCest
{
    public function testGuestAccess(AcceptanceTester $I)
    {
        $I->amAdmin();
        $I->allowGuestAccess();

        $I->amUser1(true);
        $I->wantToTest('the visibility of folders and files for guests');
        $I->amGoingTo('install the cfiles module for space 2');
        $I->enableCfilesOnSpace(2);

        $I->amGoingTo('create a public folder and file');
        $I->createFolder('guest', 'guest test', true);
        $I->uploadFile('test.txt');

        $I->amInRoot();

        $I->createFolder('private', 'private test', false);
        $I->uploadFile('test.txt');

        $I->logout();

        $I->amOnSpace2();
        $I->see('Files', '.layout-nav-container');
        $I->click('Files', '.layout-nav-container');

        $I->seeInFileList('guest');
        $I->dontSeeInFileList('private');

        $I->openFolder('guest');
        $I->seeInFileList('test.txt');
    }

}