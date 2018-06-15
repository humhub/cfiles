<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2017 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 *
 */

namespace cfiles;

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
        $this->waitForText($text, null, '#fileList');
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
        $this->waitForText($menuItem,null, '#contextMenuFile');
        $this->click($menuItem, '#contextMenuFile');
    }

    public function clickFolderContext($id, $menuItem)
    {
        $this->rightClickFolder($id);
        $this->waitForText($menuItem,null, '#contextMenuFolder');
        $this->click($menuItem, '#contextMenuFolder');
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
}
