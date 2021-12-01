<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2021 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace cfiles\acceptance;

use cfiles\AcceptanceTester;
use humhub\modules\file\models\File as BaseFile;
use Yii;

class VersionCest
{
    public function testFileVersion(AcceptanceTester $I)
    {
        $I->amAdmin();
        $I->wantToTest('the file versioning');
        $I->amGoingTo('install the cfiles module for space 1');
        $I->enableCfilesOnSpace();

        $I->attachFile('#cfilesUploadFiles', 'test.txt');
        $I->waitForText('test.txt', null, '#fileList');
        $I->wait(1);

        $firstVersionFile = $this->getFile(1);
        $I->seeFileSizeOnSpaceStream($firstVersionFile);
        $I->amOnFilesBrowser();

        $I->wantToTest('the keep old version');
        $I->attachFile('#cfilesUploadFiles', 'version/test.txt');
        $I->wait(1);

        $secondVersionFile = $this->getFile(1);
        $I->seeFileSizeOnSpaceStream($secondVersionFile);
        $I->amOnFilesBrowser();

        $I->amGoingTo('view file versions');
        $I->seeElement('[data-cfiles-item="file_1"]');
        $I->wait(1);
        $I->clickFileContext(1, 'Versions');
        $I->waitForText('File versions', null, '#globalModal');

        $I->jsClick('#version_file_' . $firstVersionFile->id . ' [title="Revert to this version"]');
        $I->seeSuccess('File ' . $firstVersionFile->file_name . ' has been reverted to version from ' . Yii::$app->formatter->asDatetime($firstVersionFile->created_at, 'short'));
        $I->seeFileSizeOnSpaceStream($firstVersionFile);
    }

    private function getFile(int $id): BaseFile
    {
        return BaseFile::findOne(['id' => $id]);
    }
}