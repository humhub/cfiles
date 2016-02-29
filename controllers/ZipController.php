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
use humhub\modules\file\libs\ImageConverter;

/**
 * Description of ZipController
 *
 * @author Sebastian Stumpf
 */
class ZipController extends BrowseController
{

    /**
     * Action to generate the according folder and file structure from an uploaded zip file.
     * 
     * @return multitype:multitype:
     */
    public function actionUploadZippedFolder()
    {
        // cleanup all old files
        $this->cleanup();
        Yii::$app->response->format = 'json';
        $response = [];
        
        foreach (UploadedFile::getInstancesByName('files') as $cFile) {
            if (strtolower($cFile->extension) === 'zip') {
                $sourcePath = $this->getZipOutputPath() . DIRECTORY_SEPARATOR . 'zipped.zip';
                $extractionPath = $this->getZipOutputPath() . DIRECTORY_SEPARATOR . 'extracted';
                if ($cFile->saveAs($sourcePath, false)) {
                    $this->zipToFolder($response, $sourcePath, $extractionPath);
                    $this->folderToModels($response, $this->getCurrentFolder()->id, $extractionPath);
                } else {
                    $response['errormessages'][] = Yii::t('CfilesModule.base', 'Archive %filename% could not be extracted.', [
                        '%filename%' => $cFile->name
                    ]);
                }
            } else {
                $response['errormessages'][] = Yii::t('CfilesModule.base', '%filename% has invalid extension and was skipped.', [
                    '%filename%' => $cFile->name
                ]);
            }
        }
        
        $response['files'] = $this->files;
        return $response;
    }

    /**
     * Action to download a folder defined by request param "fid" as a zip file.
     *
     * @throws HttpException
     */
    public function actionDownloadZippedFolder()
    {
        // cleanup all old files
        $this->cleanup();
        // init output directory
        $outputPath = $this->getZipOutputPath();
        
        // check validity of currentFolder
        $currentFolder = $this->getCurrentFolder();
        if (empty($currentFolder)) {
            throw new HttpException(404, Yii::t('CfilesModule.base', 'The folder with the id %id% does not exist.', [
                '%id%' => (int) Yii::$app->request->get('fid')
            ]));
        }
        
        // zip the current folder
        $zipTitle = $this->zipDir($currentFolder, $outputPath);
        
        $zipPath = $outputPath . DIRECTORY_SEPARATOR . $zipTitle;
        
        // check if the zip was created
        if (! file_exists($zipPath)) {
            throw new HttpException(404, Yii::t('CfilesModule.base', 'The archive could not be created.'));
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
            $filePath = $file->baseFile->getPath() . DIRECTORY_SEPARATOR . 'file';
            if (version_compare(Yii::$app->version, '1.1', 'lt')) {
                $filePath = $file->baseFile->getPath() . DIRECTORY_SEPARATOR . $file->title;
            }
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
     * Unzip an archive.
     *
     * @param Response $response
     *            the response errors will be parsed to.
     * @param string $sourcePath            
     * @param string $extractionPath            
     */
    protected function zipToFolder(&$response, $sourcePath, $extractionPath)
    {
        $zip = new \ZipArchive();
        $zip->open($this->getZipOutputPath() . DIRECTORY_SEPARATOR . 'zipped.zip');
        $zip->extractTo($extractionPath);
    }

    /**
     * Generate the cfolder and cfile structure in the database of a given folder and all its subfolders recursively.
     *
     * @param Response $response
     *            the response errors will be parsed to.
     * @param int $parentFolderId
     *            the folders parent folder id.
     * @param string $folderPath
     *            the path of the folder.
     */
    protected function folderToModels(&$response, $parentFolderId, $folderPath)
    {
        // remove unwanted parent folder references from the scanned files
        $files = array_diff(scandir($folderPath), array(
            '..',
            '.'
        ));
        $response['debug_files'] = $files;
        $response['debug_pfid'] = $parentFolderId;
        $response['debug_fpath'] = $folderPath;
        foreach ($files as $file) {
            $filePath = $folderPath . DIRECTORY_SEPARATOR . $file;
            if (is_dir($filePath)) {
                // create a new folder
                $folder = new Folder();
                $folder->content->container = $this->contentContainer;
                $folder->parent_folder_id = $parentFolderId;
                $folder->title = $file;
                // check if a folder with the given parent id and title exists
                $query = Folder::find()->contentContainer($this->contentContainer)
                    ->readable()
                    ->where([
                    'cfiles_folder.title' => $file,
                    'cfiles_folder.parent_folder_id' => $parentFolderId
                ]);
                $similarFolder = $query->one();
                // if a similar folder exists, add an error to the model. Must be done here, cause we need access to the content container
                if (! empty($similarFolder)) {
                    $response['infomessages'][] = Yii::t('CfilesModule.base', 'The folder %filename% already exists. Contents have been overwritten.', [
                        '%filename%' => $file
                    ]);
                    $folder = $similarFolder;
                } elseif (! $folder->save()) { // if there is no folder with the same name, try to save the current folder
                    $response['errormessages'][] = Yii::t('CfilesModule.base', ' The folder %filename% could not be saved.', [
                        '%filename%' => $file
                    ]);
                }
                $this->files[] = [
                    'fileList' => $this->renderFileList()
                ];
                $this->folderToModels($response, $folder->id, $filePath);
            } else {
                $this->fileToModel($response, $parentFolderId, $folderPath, $file);
            }
        }
    }

    /**
     * Create a cfile model and create and connect it with its basefile File model from a given data file, connect it with its parent folder.
     * TODO: This method has a lot in common with BrowseController/actionUpload, common logic needs to be extracted and reused
     *
     * @param Response $response
     *            the response errors will be parsed to.
     * @param int $parentFolderId
     *            the files pid.
     * @param string $folderPath
     *            the path of the folder the file data lies in.
     * @param string $filename
     *            the files name.
     */
    protected function fileToModel(&$response, $parentFolderId, $folderPath, $filename)
    {
        $filepath = $folderPath . DIRECTORY_SEPARATOR . $filename;
        
        // check if the file already exists in this dir
        $filesQuery = File::find()->joinWith('baseFile')
            ->readable()
            ->andWhere([
            'title' => File::sanitizeFilename($filename),
            'parent_folder_id' => $parentFolderId
        ]);
        $file = $filesQuery->one();
        
        // if not, initialize new File
        if (empty($file)) {
            $file = new File();
            $humhubFile = new \humhub\modules\file\models\File();
        } else { // else replace the existing file
            $humhubFile = $file->baseFile;
            // logging file replacement
            $response['infomessages'][] = Yii::t('CfilesModule.base', '%title% was replaced by a newer version.', [
                '%title%' => $file->title
            ]);
            $response['log'] = true;
        }
        
        // populate the file
        $humhubFile->mime_type = FileHelper::getMimeType($filepath);
        if ($humhubFile->mime_type == 'image/jpeg') {
            ImageConverter::TransformToJpeg($filepath, $filepath);
        }
        $humhubFile->size = filesize($filepath);
        $humhubFile->file_name = $filename;
        
        $humhubFile->newFileContent = stream_get_contents(fopen($filepath, 'r'));
        
        if ($humhubFile->save()) {
            
            $file->content->container = $this->contentContainer;
            $file->parent_folder_id = $parentFolderId;
            
            if ($file->save()) {
                $humhubFile->object_model = $file->className();
                $humhubFile->object_id = $file->id;
                $humhubFile->save();
                $this->files[] = array_merge($humhubFile->getInfoArray(), [
                    'fileList' => $this->renderFileList()
                ]);
            } else {
                $count = 0;
                $messages = "";
                // show multiple occurred errors
                foreach ($file->errors as $key => $message) {
                    $messages .= ($count ++ ? ' | ' : '') . $message[0];
                }
                $response['errormessages'][] = Yii::t('CfilesModule.base', 'Could not save file %title%. ', [
                    '%title%' => $file->title
                ]) . $messages;
                $response['log'] = true;
            }
        } else {
            $count = 0;
            $messages = "";
            // show multiple occurred errors
            foreach ($humhubFile->errors as $key => $message) {
                $messages .= ($count ++ ? ' | ' : '') . $message[0];
            }
            $response['errormessages'][] = Yii::t('CfilesModule.base', 'Could not save file %title%. ', [
                '%title%' => $humhubFile->filename
            ]) . $messages;
            $response['log'] = true;
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
        $code = $z->open($outZipPath, \ZipArchive::OVERWRITE);
        if($code !== true) {
            throw new HttpException(500, Yii::t('CfilesModule.base', 'Opening Zip failed with error code %code%.', ['%code%' => $code]));
        }
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

    /**
     * Get the output path of the temporary folder used for packing and unpacking zip data.
     *
     * @return string @runtime/temp/[guid]
     */
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

    /**
     * Cleanup all files in the zip output path.
     */
    protected function cleanup()
    {
        BaseFileHelper::removeDirectory($this->getZipOutputPath());
    }

    /**
     * Initializes the current folder if request param fid is 0.
     *
     * @see \humhub\modules\cfiles\controllers\BrowseController::getCurrentFolder()
     */
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
