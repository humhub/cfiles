<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2015 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */
namespace humhub\modules\cfiles\controllers;

use Yii;
use yii\web\HttpException;
use yii\web\UploadedFile;
use humhub\modules\cfiles\models\File;
use humhub\modules\cfiles\models\Folder;
use humhub\modules\content\models\Content;
use humhub\modules\content\components\ContentActiveRecord;
use humhub\modules\comment\models\Comment;
use yii\helpers\FileHelper;
use humhub\models\Setting;
use yii\helpers\BaseFileHelper;

/**
 * Description of ZipController
 *
 * @author Sebastian Stumpf
 */
class ZipController extends BrowseController
{

    public function actionDownloadZippedFolder()
    {
        // cleanup all old files
        $this->cleanup();
        // init output directory
        $outputPath = $this->getZipOutputPath();
        
        // check validity of currentFolder
        $currentFolder = $this->getCurrentFolder();
        if (empty($currentFolder)) {
            throw new HttpException(404, Yii::t('CfilesModule.controllers_ZipController', 'The folder with the id %id% does not exist.', [
                '%id%' => (int) Yii::$app->request->get('fid')
            ]));
        }
        
        // zip the current folder
        $zipTitle = $this->zipDir($currentFolder, $outputPath);
        
        $zipPath = $outputPath . DIRECTORY_SEPARATOR . $zipTitle;
        
        // check if the zip was created
        if (! file_exists($zipPath)) {
            throw new HttpException(404, Yii::t('CfilesModule.controllers_ZipController', 'The archive could not be created.'));
        }
        
        // deliver the zip
        $options = [
            'inline' => false,
            'mimeType' => FileHelper::getMimeTypeByExtension($zipPath)
        ];
        if (! Setting::Get('useXSendfile', 'file')) {
            Yii::$app->response->sendFile($zipPath, $zipTitle, $options);
        } else {
            if (strpos($_SERVER['SERVER_SOFTWARE'], 'nginx') === 0) {
                // set nginx specific X-Sendfile header name
                $options['xHeader'] = 'X-Accel-Redirect';
                // make path relative to docroot
                $docroot = rtrim($_SERVER['DOCUMENT_ROOT'], DIRECTORY_SEPARATOR);
                if (substr($zipPath, 0, strlen($docroot)) == $docroot) {
                    $zipPath = substr($zipPath, strlen($docroot));
                }
            }
            Yii::$app->response->xSendFile($zipPath, null, $options);
        }
    }

    /**
     * Add files and sub-directories in a folder to zip file.
     *
     * @param int $folderId
     *            the parent folder id which content should be added to the zip file
     * @param ZipArchive $zipFile
     *            The zip file to add the entries to
     * @param int $localPathPrefix
     *            where we currently are in the zip file
     */
    protected function folderToZip($folderId, &$zipFile, $localPathPrefix)
    {
        $subFiles = File::find()->contentContainer($this->contentContainer)
            ->readable()
            ->where([
            'parent_folder_id' => $folderId
        ])
            ->all();
        $subFolders = Folder::find()->contentContainer($this->contentContainer)
            ->readable()
            ->where([
            'parent_folder_id' => $folderId
        ])
            ->all();
        
        foreach ($subFiles as $file) {
            $filePath = $file->baseFile->getPath() . DIRECTORY_SEPARATOR . $file->title;
            if (is_file($filePath)) {
                $zipFile->addFile($filePath, $localPathPrefix . DIRECTORY_SEPARATOR . $file->title);
            }
        }
        foreach ($subFolders as $folder) {
            // go one level deeper in the loacalPath
            $folderPath = $localPathPrefix . DIRECTORY_SEPARATOR . $folder->title;
            // create new empty folder
            $zipFile->addEmptyDir($folderPath);
            // checkout subfolders recursively with adapted local path
            $this->folderToZip($folder->id, $zipFile, $folderPath, $this->contentContainer);
        }
    }

    /**
     * Add all posted files virtual directory content to zip file.
     *
     * @param ZipArchive $zipFile
     *            The zip file to add the entries to
     * @param int $localPathPrefix
     *            where we currently are in the zip file
     */
    protected function allPostedFilesToZip(&$zipFile, $localPathPrefix)
    {
        $files = $this->getAllPostedFiles();
        foreach ($files as $file) {
            $filePath = $file->getPath() . DIRECTORY_SEPARATOR . $file->title;
            if (is_file($filePath)) {
                $zipFile->addFile($filePath, $localPathPrefix . DIRECTORY_SEPARATOR . $file->title);
            }
        }
    }

    /**
     * Zip a folder (include itself).
     * Usage:
     * zipDir('/path/to/sourceDir', '/path/to/destDir');
     *
     * @param Folder $sourceFolder
     *            The folder to be zipped. If null, the root folder will be zipped.
     * @param string $outDirPath
     *            Path of output directory.
     * @return string the title of the generated zip file
     */
    protected function zipDir($sourceFolder, $outDirPath)
    {
        $folder = $sourceFolder;
        $outZipPath = $outDirPath . DIRECTORY_SEPARATOR . $folder->title . '.zip';
        $z = new \ZipArchive();
        // overwrite existing zip files
        $z->open($outZipPath, \ZipArchive::OVERWRITE);
        $z->addEmptyDir($folder->title);
        if ($folder->id === self::ROOT_ID) {
            $this->folderToZip($folder->id, $z, $folder->title);
            $allPostedFilesDirPath = $folder->title . DIRECTORY_SEPARATOR . $this->virtualAllPostedFilesFolder->title;
            $this->allPostedFilesToZip($z, $allPostedFilesDirPath);
        } elseif ($folder->id === self::All_POSTED_FILES_ID) {
            $this->allPostedFilesToZip($z, $folder->title);
        } else {
            $this->folderToZip($folder->id, $z, $folder->title);
        }
        $z->close();
        
        return $folder->title . '.zip';
    }

    protected function getZipOutputPath()
    {
        // init output directory
        $outputPath = Yii::getAlias('@runtime') . DIRECTORY_SEPARATOR . "temp";
        if (! is_dir($outputPath)) {
            mkdir($outputPath);
        }
        $outputPath .= DIRECTORY_SEPARATOR . \Yii::$app->user->guid;
        if (! is_dir($outputPath)) {
            mkdir($outputPath);
        }
        
        return $outputPath;
    }

    protected function cleanup()
    {
        BaseFileHelper::removeDirectory($this->getZipOutputPath());
    }

    protected function getCurrentFolder()
    {
        $id = (int) Yii::$app->request->get('fid');
        
        if ($id === self::ROOT_ID) {
            return $this->virtualRootFolder;
        } elseif ($id === self::All_POSTED_FILES_ID) {
            return $this->virtualAllPostedFilesFolder;
        } else {
            return parent::getCurrentFolder();
        }
    }
}
