<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2017 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 *
 */

use cfiles\AcceptanceTester;

class ImportZipCest
{
    public function testUploadFile(AcceptanceTester $I)
    {
        $I->amAdmin();
        $I->wantToTest('the import a zip file');
        $I->amGoingTo('install the cfiles module for space 1');
        $I->enableCfilesOnSpace();

        $I->createFolder('test');
        $I->wait(1);

        $I->attachFile('#cfilesUploadFiles', 'test.txt');

        $I->click('.fa-home', '#cfiles-crumb');
        $I->wait(2);

        $I->attachFile('#cfilesUploadZipFile', 'test.zip');

        $I->wait(5);

        $I->click('test', '#fileList');
        $I->waitForText('test.txt', 10, '#fileList');
        $I->waitForText('test(1).txt', 10, '#fileList');
        $I->waitForText('test.jpg', 10, '#fileList');
        $I->waitForText('test2', 10, '#fileList');

        // Disable tooltip from 'test description' of the parent "test" folder which is misplaced during tests
        $I->executeJS("
            var tooltips = document.querySelectorAll('.tooltip');
            tooltips.forEach(function(tooltip) {
                tooltip.remove();
            });
        ");
        $I->click('test2', '#fileList');
        $I->waitForText('test2.txt', 10, '#fileList');

    }
}
