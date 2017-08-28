<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2017 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 *
 */

use cfiles\AcceptanceTester;

/**
 * Created by PhpStorm.
 * User: buddha
 * Date: 27.08.2017
 * Time: 20:59
 */

class ImportZipCest
{
    public function testUploadFile(AcceptanceTester $I)
    {
        $I->amAdmin();
        $I->wantToTest('the import a zip file');
        $I->enableModule(1, 'cfiles');
        $I->waitForText('Files', null, '.layout-nav-container');

        $I->click('Files', '.layout-nav-container');
        $I->waitForText('Files from the stream');

        $I->createFolder('test');

        $I->wait(1);

        $I->attachFile('#cfilesUploadFiles', 'test.txt');

        $I->click('.fa-home', '#cfiles-crumb');
        $I->wait(2);

        $I->attachFile('#cfilesUploadZipFile', 'test.zip');

        $I->wait(5);

        $I->click('test', '#fileList');
        $I->waitForText('test.txt', null, '#fileList');
        $I->waitForText('test(1).txt', null, '#fileList');
        $I->waitForText('test.jpg', null, '#fileList');
        $I->waitForText('test2', null, '#fileList');

        $I->click('test2', '#fileList');
        $I->waitForText('test2.txt', null,'#fileList');

    }
}