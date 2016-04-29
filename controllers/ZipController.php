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
use humhub\modules\cfiles\models\FileSystemItem;

/**
 * Description of ZipController
 *
 * @author Sebastian Stumpf
 */
class ZipController extends UploadController
{

    /**
     * Action to generate the according folder and file structure from an uploaded zip file.
     *
     * @return multitype:multitype:
     */
    public function actionUploadArchive()
    {
        if (Setting::Get('disableZipSupport', 'cfiles')) {
            throw new HttpException(404, Yii::t('CfilesModule.base', 'Archive (zip) support is not enabled.'));
        }
        // cleanup all old files
        $this->cleanup();
        Yii::$app->response->format = 'json';
        $response = [];
        
        foreach (UploadedFile::getInstancesByName('files') as $cFile) {
            if (strtolower($cFile->extension) === 'zip') {
                $sourcePath = $this->getZipOutputPath() . DIRECTORY_SEPARATOR . 'zipped.zip';
                $extractionPath = $this->getZipOutputPath() . DIRECTORY_SEPARATOR . 'extracted';
                if ($cFile->saveAs($sourcePath, false)) {
                    $this->unpackArchive($response, $sourcePath, $extractionPath);
                    $this->generateModelsFromFilesystem($response, $this->getCurrentFolder()->id, $extractionPath);
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
     * Action to download a zip of the selected items.
     *
     * @return Ambigous <\humhub\modules\cfiles\controllers\type, string>
     */
    public function actionDownloadArchive()
    {
        if (Setting::Get('disableZipSupport', 'cfiles')) {
            throw new HttpException(404, Yii::t('CfilesModule.base', 'Archive (zip) support is not enabled.'));
        }
        
        $selectedItems = Yii::$app->request->post('selected');
        
        $items = [];
        // download selected items if there are some
        if (is_array($selectedItems)) {
            foreach ($selectedItems as $itemId) {
                $item = $this->module->getItemById($itemId);
                if ($item !== null) {
                    $items[] = $item;
                }
            }
        }         // download current folder if no items are selected
        else {
            // check validity of currentFolder
            $items = $this->getCurrentFolder();
            if (empty($items)) {
                throw new HttpException(404, Yii::t('CfilesModule.base', 'The folder with the id %id% does not exist.', [
                    '%id%' => (int) Yii::$app->request->get('fid')
                ]));
            }
        }
        
        // cleanup all old files
        $this->cleanup();
        // init output directory
        $outputPath = $this->getZipOutputPath();
        
        $zipTitle = $this->archive($items, $outputPath);
        
        $zipPath = $outputPath . DIRECTORY_SEPARATOR . $zipTitle;
        // check if the zip was created
        if (! file_exists($zipPath)) {
            throw new HttpException(500, Yii::t('CfilesModule.base', 'The archive could not be created.'));
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
    protected function archiveFolder($folderId, &$zipFile, $localPathPrefix)
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
            $this->archiveFile($file, $zipFile, $localPathPrefix);
        }
        foreach ($subFolders as $folder) {
            // go one level deeper in the loacalPath
            $folderPath = $localPathPrefix . DIRECTORY_SEPARATOR . $folder->title;
            // create new empty folder
            $zipFile->addEmptyDir($folderPath);
            // checkout subfolders recursively with adapted local path
            $this->archiveFolder($folder->id, $zipFile, $folderPath, $this->contentContainer);
        }
    }

    /**
     * Add a file to zip file.
     *
     * @param File $file
     *            the parent folder id which content should be added to the zip file
     * @param ZipArchive $zipFile
     *            The zip file to add the entries to
     * @param int $localPathPrefix
     *            where we currently are in the zip file
     * @param string $suffix
     *            suffix added to the file title. Use for example to make the title unique.
     * @param string $suffix
     *            see suffix
     */
    protected function archiveFile($file, &$zipFile, $localPathPrefix, $prefix = '', $suffix = '')
    {
        if ($file instanceof File) {
            $file = $file->baseFile;
        }
        
        $filePath = $file->getStoredFilePath();
        if (is_file($filePath)) {
            $zipFile->addFile($filePath, (empty($localPathPrefix) ? "" : $localPathPrefix . DIRECTORY_SEPARATOR) . $prefix . $file->title . $suffix);
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
    protected function unpackArchive(&$response, $sourcePath, $extractionPath)
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
    protected function generateModelsFromFilesystem(&$response, $parentFolderId, $folderPath)
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
                $this->generateModelsFromFilesystem($response, $folder->id, $filePath);
            } else {
                $this->generateModelFromFile($response, $parentFolderId, $folderPath, $file);
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
    protected function generateModelFromFile(&$response, $parentFolderId, $folderPath, $filename)
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
    protected function archiveAllPostedFiles(&$zipFile, $localPathPrefix)
    {
        $files = $this->getAllPostedFilesList();
        foreach ($files as $file) {
            $this->archiveFile($file, $zipFile, $localPathPrefix, \humhub\modules\cfiles\models\File::getUserById($file->created_by)->username.'_'.$file->created_at.'_');
        }
    }

    /**
     * Zip an array of items.
     * Usage:
     * archiveDirectory([$folder1, $file1, $file2, $folder5, ...], 'myArchive');
     *
     * @param
     *            array | Folder $items
     *            The items to be zipped. Accepts an array and a single Folder.
     * @param string $zipName
     *            name of the generrated zip
     * @return string the title of the generated zip file
     */
    protected function archive($items, $outDirPath)
    {
        if (is_array($items)) {
            $title = 'files.zip';
        } elseif ($items instanceof FileSystemItem) {
            $title = $items->title . '.zip';
            $items = array(
                $items
            );
        } else {
            throw new HttpException(500, Yii::t('CfilesModule.base', 'Invalid parameter.'));
        }
        $outZipPath = $outDirPath . DIRECTORY_SEPARATOR . $title;
        
        $z = new \ZipArchive();
        // overwrite existing zip files
        $code = $z->open($outZipPath, \ZIPARCHIVE::CREATE | \ZIPARCHIVE::OVERWRITE);
        if ($code !== true) {
            throw new HttpException(500, Yii::t('CfilesModule.base', 'Opening archive failed with error code %code%.', [
                '%code%' => $code
            ]));
        }
        foreach ($items as $item) {
            if ($item instanceof Folder) {
                $z->addEmptyDir($item->title);
                if ($item->id === self::ROOT_ID) {
                    $this->archiveFolder($item->id, $z, $item->title);
                    $allPostedFilesDirPath = $item->title . DIRECTORY_SEPARATOR . $this->getAllPostedFilesFolder()->title;
                    $this->archiveAllPostedFiles($z, $allPostedFilesDirPath);
                } elseif ($item->id == self::All_POSTED_FILES_ID) {
                    $this->archiveAllPostedFiles($z, $item->title);
                } else {
                    $this->archiveFolder($item->id, $z, $item->title);
                }
            } elseif ($item instanceof File || $item instanceof \humhub\modules\file\models\File) {
                $this->archiveFile($item, $z, "");
            }
        }
        $z->close();
        
        return $title;
    }

    /**
     * Get the output path of the user specified temporary folder used for packing and unpacking zip data for this user.
     *
     * @return string @runtime/temp/[guid]
     */
    protected function getZipOutputPath()
    {
        // init output directory
        $outputPath = $this->getBaseTempFolderPath();
        $outputPath .= DIRECTORY_SEPARATOR . \Yii::$app->user->guid;
        if (! is_dir($outputPath)) {
            mkdir($outputPath);
        }
        
        return $outputPath;
    }
    
    /**
     * Get the output path of the base temporary folder used for packing and unpacking zip data for all users.
     *
     * @return string @runtime/temp/[guid]
     */
    protected function getBaseTempFolderPath()
    {
        // init output directory
        $outputPath = Yii::getAlias('@runtime') . DIRECTORY_SEPARATOR . "temp";
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
        $files = BaseFileHelper::findFiles($this->getBaseTempFolderPath(), ['filter' => function($path) {return time()-filemtime($path) > 30 ? true : false;}, 'recursive' => true]);
        foreach($files as $file) {
            unlink($file);
        }
    }
}
