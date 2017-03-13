<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2017 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\cfiles\libs;

use Yii;
use ZipArchive;
use yii\base\Exception;
use yii\helpers\BaseFileHelper;
use humhub\modules\file\models\File;
use humhub\modules\cfiles\models\File as CFile;
use humhub\modules\cfiles\models\Folder;
use humhub\modules\cfiles\models\FileSystemItem;

/**
 * ZIPCreator creates ZIP Archives with given FileSystemItems
 *
 * @author Luke
 */
class ZIPCreator
{

    /**
     * @var ZipArchive the zip archive
     */
    protected $archive;

    /**
     * @var string the filename of temp file
     */
    protected $tempFile;

    /**
     * Creates a new ZIP
     * 
     * @throws \yii\base\Exception
     */
    public function __construct()
    {
        #$this->cleanup();

        $this->archive = new ZipArchive();
        $this->tempFile = $this->getTempPath() . DIRECTORY_SEPARATOR . time();

        $code = $this->archive->open($this->tempFile, ZIPARCHIVE::CREATE | ZIPARCHIVE::OVERWRITE);
        if ($code !== true) {
            throw new \yii\base\Exception(Yii::t('CfilesModule.base', 'Opening archive failed with error code %code%.', ['%code%' => $code]));
        }
    }

    /**
     * Closes and writes the ZIP File
     */
    public function close()
    {
        Yii::info('Written ZIP: ' . $this->tempFile . '(Number of files: ' . $this->archive->numFiles . ' - Status: ' . $this->archive->status . ')', 'cfiles');
        $this->archive->close();
    }

    /**
     * Returns the temporary generated ZIP File name
     * 
     * @return string the ZIP filename
     */
    public function getZipFile()
    {
        return $this->tempFile;
    }

    /**
     * Adds file or folder to the ZIP
     * 
     * @param FileSystemItem $item the item
     * @param string $path path in the ZIP archive
     * @throws Exception
     */
    public function add(FileSystemItem $item, $path = '')
    {
        if ($item instanceof CFile) {
            $this->addFile($item, $path);
        } elseif ($item instanceof Folder) {
            $this->addFolder($item, $path);
        } else {
            throw new Exception("Invalid file system item given to add to ZIP!");
        }
    }

    /**
     * Add a file to zip file.
     *
     * @param File $file the parent folder id which content should be added to the zip file
     * @param string $path where we currently are in the zip file
     * @param string $fileName alternative fileName otherwise use db filename
     */
    public function addFile($file, $path = '', $fileName = null)
    {
        if ($file instanceof CFile) {
            $file = $file->baseFile;
        }

        if ($fileName === null) {
            $filePath = $path . DIRECTORY_SEPARATOR . $file->file_name;
        } else {
            $filePath = $path . DIRECTORY_SEPARATOR . $fileName;
        }
        $filePath = $this->fixPath($filePath);


        Yii::info("Added file to ZIP Archive: " . $filePath, 'cfiles');

        $realFilePath = $file->store->get();
        if (is_file($realFilePath)) {
            $this->archive->addFile($realFilePath, $filePath);
        }
    }

    /**
     * Add files and sub-directories in a folder to zip file.
     *
     * @param int $folderId the parent folder id which content should be added to the zip file
     * @param string $path where we currently are in the zip file
     */
    public function addFolder(Folder $folder, $path = '')
    {
        $path = $this->fixPath($path . DIRECTORY_SEPARATOR . $folder->title);

        Yii::info("Added folder:" . $path, 'cfiles');

        $this->archive->addEmptyDir($path);

        if ($folder->isAllPostedFiles()) {
            // Special Handling for "Files from Stream" folder
            foreach (CFile::getPostedFiles($folder->content->container)['postedFiles'] as $baseFile) {
                $baseFileName = CFile::getUserById($baseFile->created_by)->username . '_' . $baseFile->created_at . '_' . $baseFile->file_name;
                $this->addFile($baseFile, $path, $baseFileName);
            }
            return;
        }

        $subFiles = CFile::find()->where(['parent_folder_id' => $folder->id])->all();
        $subFolders = Folder::find()->where(['parent_folder_id' => $folder->id])->all();

        foreach ($subFiles as $file) {
            $this->addFile($file, $path);
        }

        foreach ($subFolders as $folder) {
            // checkout subfolders recursively with adapted local path
            $this->addFolder($folder, $path);
        }
    }

    /**
     * Cleanup all previously created zip files.
     */
    protected function cleanup()
    {
        $files = BaseFileHelper::findFiles($this->getTempPath(), [
                    'filter' => function ($path) {
                        return time() - filemtime($path) > 30 ? true : false;
                    },
                    'recursive' => true
        ]);
        foreach ($files as $file) {
            unlink($file);
        }
    }

    /**
     * Get the output path of the base temporary folder used for packing and unpacking zip data for all users.
     *
     * @return string @runtime/temp/[guid]
     */
    protected function getTempPath()
    {
        // init output directory
        $outputPath = Yii::getAlias('@runtime/cfiles-temp');
        if (!is_dir($outputPath)) {
            mkdir($outputPath);
        }
        return $outputPath;
    }

    /**
     * Fixes ZIP location path, removes trailling slash
     * 
     * @param string $path
     * @return string the fixed path
     */
    protected function fixPath($path)
    {
        return ltrim($path, DIRECTORY_SEPARATOR);
    }

}
