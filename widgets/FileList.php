<?php

namespace humhub\modules\cfiles\widgets;

use humhub\modules\cfiles\models\rows\AbstractFileSystemItemRow;
use humhub\modules\cfiles\models\rows\BaseFileRow;
use humhub\modules\cfiles\models\rows\FolderRow;
use humhub\modules\cfiles\models\rows\SpecialFolderRow;
use humhub\modules\cfiles\permissions\ManageFiles;
use Yii;
use humhub\modules\cfiles\models\rows\FileRow;
use humhub\modules\cfiles\models\File;
use humhub\modules\cfiles\models\FileSystemItem;
use yii\data\Pagination;

/**
 * Widget for rendering the file list.
 */
class FileList extends \yii\base\Widget
{

    /**
     * @var \humhub\modules\cfiles\models\Folder current folder
     */
    public $folder;

    /**
     * @var \humhub\modules\content\components\ContentContainerActiveRecord Current content container.
     */
    public $contentContainer;

    /**
     * @var array file order option used by file query.
     */
    public $filesOrder;

    /**
     * @var array folder order option used by folder query.
     */
    public $foldersOrder;

    /**
     * @var AbstractFileSystemItemRow[] All file items of the current folder sorted by item type.
     */
    protected $rows;

    /**
     * @var Pagination
     */
    protected $pagination;

    /**
     * @inheritdoc
     */
    public function run()
    {
        if($this->folder->isAllPostedFiles()) {
            $this->setPostedFilesRows();
        } else {
            $this->setSystemItemRows();
        }

        $canWrite = $this->contentContainer->can(ManageFiles::class);
        $itemsSelectable = !$this->folder->isAllPostedFiles() && ($canWrite || Yii::$app->getModule('cfiles')->isZipSupportEnabled());

        return $this->render('fileList', [
                    'rows' => $this->rows,
                    'pagination' => $this->pagination,
                    'folder' => $this->folder,
                    'itemsSelectable' => $itemsSelectable,
                    'itemsInFolder' => count($this->rows),
                    'canWrite' => $canWrite,
        ]);
    }

    protected function setPostedFilesRows()
    {
        $query = File::getPostedFiles($this->contentContainer);
        $countQuery = clone $query;
        $this->pagination = new Pagination(['totalCount' => $countQuery->count()]);

        $files = $query->offset($this->pagination->offset)->limit($this->pagination->limit)->all();

        $this->rows =  [];
        foreach ($files as $file) {
            $this->rows[] = new BaseFileRow(['parentFolder' => $this->folder, 'baseFile' => $file]);
        }
    }

    /**
     * Returns all file items of the current folder sorted by item type.
     * @return array
     */
    protected function setSystemItemRows()
    {
        $this->rows = [];

        foreach ($this->folder->getSpecialFolders() as $specialFolder) {
            $this->rows[] = new SpecialFolderRow(['item' => $specialFolder]);
        }

        foreach ($this->folder->getSubFolders() as $subFolder) {
            $this->rows[] = new FolderRow(['item' => $subFolder]);
        }

        foreach ($this->folder->getSubFiles() as $subFile) {
            $this->rows[] = new FileRow(['item' => $subFile]);
        }
    }

    /**
     * Returns a list of selected items
     * 
     * @return \humhub\modules\cfiles\models\FileSystemItem[]
     */
    public static function getSelectedItems()
    {
        $selectedItems = Yii::$app->request->post('selection');

        $items = [];

        // download selected items if there are some
        if (is_array($selectedItems)) {
            foreach ($selectedItems as $itemId) {
                $item = FileSystemItem::getItemById($itemId);
                if ($item !== null) {
                    $items[] = $item;
                }
            }
        }
        return $items;
    }

}
