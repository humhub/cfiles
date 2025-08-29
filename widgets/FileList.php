<?php

namespace humhub\modules\cfiles\widgets;

use humhub\modules\cfiles\models\File;
use humhub\modules\cfiles\models\Folder;
use humhub\modules\cfiles\models\rows\AbstractFileSystemItemRow;
use humhub\modules\cfiles\models\rows\BaseFileRow;
use humhub\modules\cfiles\models\rows\FolderRow;
use humhub\modules\cfiles\models\rows\SpecialFolderRow;
use humhub\modules\cfiles\models\rows\FileRow;
use humhub\modules\cfiles\Module;
use humhub\modules\cfiles\permissions\ManageFiles;
use humhub\modules\content\components\ContentContainerActiveRecord;
use Yii;
use yii\base\Widget;
use yii\data\Pagination;

/**
 * Widget for rendering the file list.
 */
class FileList extends Widget
{
    /**
     * @var Folder current folder
     */
    public $folder;

    /**
     * @var ContentContainerActiveRecord Current content container.
     */
    public $contentContainer;

    /**
     * @var AbstractFileSystemItemRow[] All file items of the current folder sorted by item type.
     */
    protected $rows;

    /**
     * @var string sort field
     */
    public $sort;

    /**
     * @var string sort order ASC/DESC
     */
    public $order;

    /**
     * @var Pagination
     */
    protected $pagination;

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        $this->initSortOrder();
    }

    protected function initSortOrder(): void
    {
        /* @var $module Module */
        $module = Yii::$app->getModule('cfiles');

        // Init with default module values
        $this->sort =  ($this->folder->isAllPostedFiles()) ? $module->defaultPostedFilesSort : $module->defaultSort;
        $this->order = ($this->folder->isAllPostedFiles()) ? $module->defaultPostedFilesOrder : $module->defaultOrder;

        // Overwrite with request sort order if given
        $this->sort  = Yii::$app->request->get('sort', $this->sort);
        $this->order = Yii::$app->request->get('order', $this->order);

        // Save sort settings if sorting was used and logged in user is given or try fetching user settings.
        if (!Yii::$app->user->isGuest) {
            $settings = $module->settings->user(Yii::$app->user->getIdentity());
            if (Yii::$app->request->get('sort')) {
                $settings->set('defaultSort', $this->sort);
                $settings->set('defaultOrder', $this->order);
            } else {
                $this->sort = $settings->get('defaultSort', $this->sort);
                $this->order = $settings->get('defaultOrder', $this->order);
            }
        }
    }

    /**
     * @inheritdoc
     */
    public function run()
    {
        if ($this->folder->isAllPostedFiles()) {
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
            'sort' => $this->sort,
            'order' => $this->order,
            'itemsSelectable' => $itemsSelectable,
            'itemsInFolder' => count($this->rows),
            'canWrite' => $canWrite,
        ]);
    }

    protected function setPostedFilesRows(): void
    {
        $query = File::getPostedFiles($this->contentContainer, BaseFileRow::translateOrder($this->sort, $this->order));
        $countQuery = clone $query;
        $this->pagination = new Pagination(['totalCount' => $countQuery->count()]);

        $files = $query->offset($this->pagination->offset)->limit($this->pagination->limit)->all();

        $this->rows =  [];
        foreach ($files as $file) {
            $this->rows[] = new BaseFileRow(['parentFolder' => $this->folder, 'baseFile' => $file]);
        }
    }

    protected function setSystemItemRows(): void
    {
        $this->rows = [];

        foreach ($this->folder->getSpecialFolders() as $specialFolder) {
            $this->rows[] = new SpecialFolderRow(['item' => $specialFolder]);
        }

        foreach ($this->folder->getSubFolders(FolderRow::translateOrder($this->sort, $this->order)) as $subFolder) {
            $this->rows[] = new FolderRow(['item' => $subFolder]);
        }

        foreach ($this->folder->getSubFiles(FileRow::translateOrder($this->sort, $this->order)) as $subFile) {
            $this->rows[] = new FileRow(['item' => $subFile]);
        }
    }
}
