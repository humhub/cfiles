<?php

namespace humhub\modules\cfiles\widgets;

use humhub\modules\cfiles\models\FileSystemItem;
use humhub\modules\cfiles\models\Folder;
use humhub\modules\cfiles\models\rows\FileSystemItemRow;
use humhub\modules\cfiles\permissions\ManageFiles;
use humhub\modules\content\widgets\WallEntryControls;
use humhub\modules\ui\menu\DropdownDivider;
use humhub\modules\ui\menu\MenuLink;
use Yii;

/**
 * Widget for rendering the file list context menu.
 */
class FileListContextMenu extends WallEntryControls
{
    /**
     * @var Folder Current folder model instance
     */
    public $folder;

    /**
     * @var FileSystemItemRow File or Folder row object
     */
    public $row;

    /**
     * @inheritdoc
     */
    public $template = 'fileListContextMenu';

    /**
     * @inheritdoc
     */
    public function init()
    {
        if ($this->disabledWallEntryControls()) {
            $this->initRenderOptions();
            return;
        }

        $this->object = $this->row->item;
        $this->wallEntryWidget = $this->row->getContext();
        parent::init();
    }

    private function disabledWallEntryControls(): bool
    {
        if (!isset($this->row->item) || !($this->row->item instanceof FileSystemItem)) {
            return true;
        }

        if (in_array($this->row->getType(), ['folder', 'folder-posted'])) {
            return true;
        }

        return false;
    }

    public function initControls()
    {
        $this->renderOptions->disableControlsEntryEdit();
        $this->renderOptions->disableControlsEntryPermalink();
        $this->renderOptions->disableControlsEntryDelete();
        $this->renderOptions->disableControlsEntryPin();
        $this->renderOptions->disableControlsEntryMove();
        $this->renderOptions->disableControlsEntryArchive();

        switch ($this->row->getType()) {
            case 'image':
                $this->initMenuImage();
                break;
            case 'folder':
                $this->initMenuFolder();
                return; // Don't init core menu entries for folders
            case 'folder-posted':
                $this->initMenuAllPostedFiles();
                return; // Don't init core menu entries for folders
            default:
                $this->initMenuFile();
        }

        if (!$this->disabledWallEntryControls()) {
            parent::initControls();
        }
    }

    private function initMenuFolder()
    {
        $this->addMenu(Yii::t('CfilesModule.base', 'Open'), 'download', 'folder-open', 10);
        $this->addMenu(Yii::t('CfilesModule.base', 'Display Url'), 'show-url', 'link', 20);
        $this->addEntry(new DropdownDivider(['sortOrder' => 25]));
        if ($this->isEditableRow()) {
            $this->addMenu(Yii::t('CfilesModule.base', 'Edit'), 'edit-folder', 'pencil', 30);
            $this->addMenu(Yii::t('CfilesModule.base', 'Delete'), 'delete', 'trash', 40);
        }

        if ($this->canWrite()) {
            $this->addMenu(Yii::t('CfilesModule.base', 'Move'), 'move-files', 'arrows', 50);
        }

        if ($this->zipEnabled()) {
            $this->addMenu(Yii::t('CfilesModule.base', 'Download ZIP'), 'zip', 'file-archive-o', 60);
        }
    }

    private function initMenuFile()
    {
        $this->addMenu(Yii::t('CfilesModule.base', 'Download'), 'download', 'cloud-download', 10);
        $this->addMenu(Yii::t('CfilesModule.base', 'Show Post'), 'show-post', 'window-maximize', 20);
        $this->addMenu(Yii::t('CfilesModule.base', 'Display Url'), 'show-url', 'link', 30);

        if (!$this->folder->isAllPostedFiles() && $this->isEditableRow()) {
            $this->addEntry(new DropdownDivider(['sortOrder' => 35]));
            $this->addMenu(Yii::t('CfilesModule.base', 'Edit'), 'edit-file', 'pencil', 40);
            $this->addMenu(Yii::t('CfilesModule.base', 'Delete'), 'delete', 'trash', 50);
            if ($this->canWrite()) {
                $this->addMenu(Yii::t('CfilesModule.base', 'Move'), 'move-files', 'arrows', 60);
            }
            $this->addMenu(Yii::t('CfilesModule.base', 'Versions'), 'versions', 'history', 70);
        }
    }

    private function initMenuImage()
    {
        $this->initMenuFile();
    }

    private function initMenuAllPostedFiles()
    {
        $this->addMenu(Yii::t('CfilesModule.base', 'Open'), 'download', 'folder-open', 10);
        $this->addMenu(Yii::t('CfilesModule.base', 'Display Url'), 'show-url', 'link', 20);
    }

    private function isEditableRow(): bool
    {
        return $this->row->item->canEdit();
    }

    private function canWrite(): bool
    {
        return $this->isEditableRow() && $this->folder->content->container->can(ManageFiles::class);
    }

    private function zipEnabled(): bool
    {
        return !Yii::$app->getModule('cfiles')->settings->get('disableZipSupport');
    }

    private function addMenu(string $label, string $action, string $icon, int $sortOrder = 1)
    {
        $this->addEntry(new MenuLink([
            'label' => $label,
            'url' => '#',
            'icon' => $icon,
            'sortOrder' => $sortOrder,
            'htmlOptions' => ['data-action' => $action],
        ]));
    }

}