<?php
namespace cfiles;

use humhub\modules\cfiles\helpers\RestDefinitions;
use humhub\modules\cfiles\models\File;
use humhub\modules\cfiles\models\Folder;

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
 * @method void pause()
 *
 * @SuppressWarnings(PHPMD)
*/
class ApiTester extends \ApiTester
{
    use _generated\ApiTesterActions;

    /**
     * Define custom actions here
     */

    public function createFolder($title, $params = [])
    {
        $params = array_merge([
            'containerId' => 1,
            'description' => '',
            'target_id' => null,
            'visibility' => 1,
        ], $params);

        $this->amGoingTo('create a sample folder');
        $this->sendPost('cfiles/folders/container/' . $params['containerId'], [
            'target_id' => $params['target_id'],
            'Folder' => [
                'title' => $title,
                'description' => $params['description'],
                'visibility' => $params['visibility'],
            ],
        ]);
    }

    public function uploadFiles($files, $params = [])
    {
        if (empty($files)) {
            return;
        }

        $params = array_merge([
            'containerId' => 1,
            'folder_id' => 2, // Root folder with id=1 is created by default before create a new folder
        ], $params);

        if (!is_array($files)) {
            $files = [$files];
        }

        foreach ($files as $f => $file) {
            $files[$f] = codecept_data_dir($file);
        }

        $this->amGoingTo('create a sample file');
        $this->sendPost('cfiles/files/container/' . $params['containerId'], ['folder_id' => $params['folder_id']], ['files' => $files]);
    }

    public function createSampleFolder()
    {
        $this->createFolder('Sample folder title');
        $this->seeLastCreatedFolderDefinition();
    }

    public function createSampleFile()
    {
        $this->createSampleFolder();
        $this->uploadFiles('test.txt');
        $this->seeSuccessMessage('Files successfully uploaded!');
    }

    public function getFileDefinitionById($fileId)
    {
        $file = File::findOne(['id' => $fileId]);
        return ($file ? RestDefinitions::getFile($file) : []);
    }

    public function seeLastCreatedFileDefinition()
    {
        $file = File::find()
            ->orderBy(['id' => SORT_DESC])
            ->one();
        $fileDefinition = ($file ? RestDefinitions::getFile($file) : []);
        $this->seeSuccessResponseContainsJson($fileDefinition);
    }

    public function seeFileDefinitionById($fileId)
    {
        $this->seeSuccessResponseContainsJson($this->getFileDefinitionById($fileId));
    }

    public function seePaginationFilesResponse($url, $fileIds)
    {
        $fileDefinitions = [];
        foreach ($fileIds as $fileId) {
            $fileDefinitions[] = $this->getFileDefinitionById($fileId);
        }

        $this->seePaginationGetResponse($url, $fileDefinitions);
    }

    public function getFolderDefinitionById($folderId)
    {
        $folder = Folder::findOne(['id' => $folderId]);
        return ($folder ? RestDefinitions::getFolder($folder) : []);
    }

    public function seeLastCreatedFolderDefinition()
    {
        $folder = Folder::find()
            ->orderBy(['id' => SORT_DESC])
            ->one();
        $folderDefinition = ($folder ? RestDefinitions::getFolder($folder) : []);
        $this->seeSuccessResponseContainsJson($folderDefinition);
    }

    public function seeFolderDefinitionById($folderId)
    {
        $this->seeSuccessResponseContainsJson($this->getFolderDefinitionById($folderId));
    }

    public function seePaginationFoldersResponse($url, $folderIds)
    {
        $folderDefinitions = [];
        foreach ($folderIds as $folderId) {
            $folderDefinitions[] = $this->getFolderDefinitionById($folderId);
        }

        $this->seePaginationGetResponse($url, $folderDefinitions);
    }

}
