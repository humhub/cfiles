<?php

namespace humhub\modules\cfiles\widgets;

use Yii;
use humhub\modules\cfiles\models\File;
use humhub\modules\cfiles\models\FileSystemItem;

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
     * @var boolean determines if the current user has write permissions. 
     */
    public $canWrite;

    /**
     * @var array file order option used by file query.
     */
    public $filesOrder;

    /**
     * @var array folder order option used by folder query.
     */
    public $foldersOrder;

    /**
     * @var array All file items of the current folder sorted by item type.
     */
    protected $items;

    /**
     * @inheritdoc
     */
    public function run()
    {
        $this->items = $this->getItems();

        return $this->render('fileList', [
                    'items' => $this->items,
                    'contentContainer' => $this->contentContainer,
                    'folder' => $this->folder,
                    'itemsSelectable' => !$this->folder->isAllPostedFiles(),
                    'itemsInFolder' => $this->hasItemsInFolder(),
                    'canWrite' => $this->canWrite
        ]);
    }

    /**
     * Determines if the there are any file items in the current folder.
     * @return boolean
     */
    protected function hasItemsInFolder()
    {
        return array_key_exists('specialFolders', $this->items) && sizeof($this->items['specialFolders']) > 0 || array_key_exists('folders', $this->items) && sizeof($this->items['folders']) > 0 || array_key_exists('files', $this->items) && sizeof($this->items['files']) > 0 || array_key_exists('postedFiles', $this->items) && sizeof($this->items['postedFiles']) > 0;
    }

    /**
     * Returns all file items of the current folder sorted by item type.
     * @return array
     */
    protected function getItems()
    {
        return ($this->folder->isAllPostedFiles()) ? File::getPostedFiles($this->contentContainer, $this->filesOrder) :
                $this->folder->getItems($this->filesOrder, $this->foldersOrder);
    }

    /**
     * Returns a list of selected items
     * 
     * @return \humhub\modules\cfiles\models\FileSystemItem[]
     * @throws HttpException
     */
    public static function getSelectedItems()
    {
        $selectedItems = Yii::$app->request->post('selected');

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
