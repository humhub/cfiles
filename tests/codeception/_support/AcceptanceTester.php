<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2017 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 *
 */

namespace cfiles;

use humhub\modules\file\models\File as BaseFile;
use Yii;

/**
 * Inherited Methods
 * @method void wantToTest($text)
 * @method void wantTo($text)
 * @method void execute($callable)
 * @method void expectTo($prediction)
 * @method void expect($prediction)
 * @method void amGoingTo($argumentation)
 * @method void am($role)
 * @method void lookForwardTo($achieveValue)
 * @method void comment($description)
 * @method \Codeception\Lib\Friend haveFriend($name, $actorClass = null)
 *
 * @SuppressWarnings(PHPMD)
*/
class AcceptanceTester extends \AcceptanceTester
{
    use _generated\AcceptanceTesterActions;

    public function seeInCrumb($text)
    {
        $this->waitForText($text, null,'#cfiles-crumb');
    }

    public function dontSeeInCrumb($text)
    {
        $this->dontSee($text, '#cfiles-crumb');
    }

    public function seeInFileList($text)
    {
        $this->waitForElement('#fileList');
        $this->waitForText($text);
    }

    public function dontSeeInFileList($text)
    {
        $this->dontSee($text, '#fileList');
    }

    public function openFolder($text)
    {
        $this->click($text, '#fileList');
        $this->seeInCrumb($text);
    }

    public function amInRoot()
    {
        $this->click('.fa-home', '#cfiles-crumb');
        $this->waitForText('Files from the stream', null, '#fileList');
    }

    public function uploadFile($file = "test.txt")
    {
        $this->attachFile('#cfilesUploadFiles', $file);
        $this->wait(2);
        $this->waitForText($file, null, '#fileList');
    }

    public function rightClickFolder($id)
    {
        $this->clickWithRightButton('[data-cfiles-item="folder_'.$id.'"]');
    }

    public function rightClickFile($id)
    {
        $this->clickWithRightButton('[data-cfiles-item="file_'.$id.'"]');
    }

    public function clickFileContext($id, $menuItem)
    {
        $this->rightClickFile($id);
        $this->waitForText($menuItem,null, '[data-cfiles-item="file_'.$id.'"] .contextMenu');
        $this->click($menuItem, '[data-cfiles-item="file_'.$id.'"] .contextMenu');
    }

    public function clickFolderContext($id, $menuItem)
    {
        $this->rightClickFolder($id);
        $this->waitForText($menuItem,null, '[data-cfiles-item="folder_'.$id.'"] .contextMenu');
        $this->click($menuItem, '[data-cfiles-item="folder_'.$id.'"] .contextMenu');
    }

    public function importZip($file = "test.zip")
    {
        $this->attachFile('#cfilesUploadZipFile', $file);
        $this->waitForText($file, null, '#fileList');
    }

    public function enableCfilesOnSpace($guid = 1)
    {
        $this->enableModule($guid, 'cfiles');

        $this->amOnSpace($guid);
        $this->expectTo('see files in the space nav');
        $this->waitForText('Files', 30, '.layout-nav-container');

        $this->amOnFilesBrowser();
    }

    public function amOnFilesBrowser()
    {
        $this->amGoingTo('open files module');
        $this->click('Files', '.layout-nav-container');
        $this->waitForText('Files from the stream');
    }

   /**
    * Define custom actions here
    */
   public function createFolder($title = 'test', $description = 'test description', $isPublic = false)
   {
       $this->click('Add directory', '.files-action-menu');

       $this->waitForText('Create folder', null,'#globalModal');
       $this->fillField('Folder[title]', $title);
       $this->fillField('Folder[description]', $description);

       if($isPublic) {
           $this->click('[for="folder-visibility"]');
       }

       $this->click('Save', '#globalModal');
       $this->waitForText('This folder is empty.');
   }

    public function seeFileSizeOnSpaceStream(BaseFile $file, $guid = 1)
    {
        $this->amOnSpace($guid);
        $this->waitForText($file->file_name);
        $this->see('Size: ' . Yii::$app->formatter->asShortSize($file->size, 1));
    }
}
