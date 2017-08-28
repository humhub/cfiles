<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2017 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 *
 */

/**
 * Created by PhpStorm.
 * User: buddha
 * Date: 27.08.2017
 * Time: 20:26
 */

namespace humhub\modules\cfiles\libs;


use Yii;
use yii\web\UploadedFile;
use humhub\modules\cfiles\models\Folder;

class ZipExtractor extends ZipUtil
{

    /**
     * @param Folder $targetFolder
     * @param $file
     */
    public function extract(Folder $targetFolder, UploadedFile $file)
    {
        $this->cleanup();
        if (strtolower($file->extension) === 'zip') {
            $sourcePath = $this->getZipOutputPath() . '/zipped.zip';
            $extractionPath = $this->getZipOutputPath() . '/extracted';

            if ($file->saveAs($sourcePath, false)) {
                $this->unpackArchive($sourcePath, $extractionPath);
                $this->generateModelsFromFilesystem($targetFolder, $extractionPath);
            } else {
                $targetFolder->addError('upload', Yii::t('CfilesModule.base', 'Archive %filename% could not be extracted.', ['%filename%' => $file->name]));
            }
        } else {
            $targetFolder->addError('upload', Yii::t('CfilesModule.base', '%filename% has invalid extension and was skipped.', ['%filename%' => $file->name]));
        }

        return $targetFolder;
    }

    /**
     * Unzips an archive from sourcePath to extractionPath.
     *
     * @param string $sourcePath
     * @param string $extractionPath
     */
    protected function unpackArchive($sourcePath, $extractionPath)
    {
        $zip = new \ZipArchive();
        $zip->open($sourcePath);
        $zip->extractTo($extractionPath);
        $zip->close();
    }

    /**
     * Generate the cfolder and cfile structure in the database of a given folder and all its subfolders recursively.
     *
     * @param Folder $targetFolder the folders parent folder.
     * @param string $folderPath the path of the folder.
     * @param Folder|null $root the $root folder which is used for adding errors if not given $targetFolder will be used as $root
     */
    protected function generateModelsFromFilesystem(Folder $targetFolder, $folderPath, Folder $root = null)
    {

        // remove unwanted parent folder references from the scanned files
        $files = array_diff(scandir($folderPath), ['..','.']);

        if(!$root) {
            $root = $targetFolder;
        }

        foreach ($files as $file) {
            $filePath = $folderPath . DIRECTORY_SEPARATOR . $file;
            if (is_dir($filePath)) {
                $folder = $targetFolder->findFolderByName($file);

                if(!$folder) {
                    $folder = $targetFolder->newFolder($file);
                    if(!$folder->save()) {
                        $root->addError('upload',  Yii::t('CfilesModule.base', 'An error occurred while creating folder {folder}.', ['folder' => $file]));
                        continue;
                    }
                }

                $this->generateModelsFromFilesystem($folder, $filePath, $root);
            } else {
                $result = $this->generateModelFromFile($targetFolder, $folderPath, $file);
                if($result->hasErrors()) {
                    $root->addError('upload',  Yii::t('CfilesModule.base', 'An error occurred while unpacking {filename}.', ['filename' => $file]));
                }
            }
        }
    }

    /**
     * Create a cfile model and create and connect it with its basefile File model from a given data file, connect it with its parent folder.
     * TODO: This method has a lot in common with BrowseController/actionUpload, common logic needs to be extracted and reused
     *
     * @param Folder $targetFolder the files pid.
     * @param string $folderPath the path of the folder the file data lies in.
     * @param string $filename the files name.
     */
    protected function generateModelFromFile(Folder $targetFolder, $folderPath, $filename)
    {
        $filePath = $folderPath . DIRECTORY_SEPARATOR . $filename;
        return $targetFolder->addFileFromPath($filename, $filePath);
    }
}