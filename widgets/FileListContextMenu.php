<?php

namespace humhub\modules\cfiles\widgets;

use humhub\modules\cfiles\permissions\ManageFiles;
use Yii;

/**
 * Widget for rendering the file list context menu.
 */
class FileListContextMenu extends \yii\base\Widget
{
    /**
     * Current folder model instance.
     * @var \humhub\modules\cfiles\models\Folder
     */
    public $folder;

    /**
     * @inheritdoc
     */
    public function run()
    {
        return $this->render('fileListContextMenu', [
            'menus' => $this->getMenus(),
        ]);
    }

    private function getMenus(): array
    {
        return [
            'contextMenuFolder' => $this->getMenuFolder(),
            'contextMenuFile' => $this->getMenuFile(),
            'contextMenuImage' => $this->getMenuImage(),
            'contextMenuAllPostedFiles' => $this->getMenuAllPostedFiles(),
        ];
    }

    private function getMenuFolder(): array
    {
        $menu = [
            $this->getMenu(Yii::t('CfilesModule.base', 'Open'), 'download', 'folder-open'),
            $this->getMenu(Yii::t('CfilesModule.base', 'Display Url'), 'show-url', 'link'),
            'separator',
            $this->getEditableMenu(Yii::t('CfilesModule.base', 'Edit'), 'edit-folder', 'pencil'),
            $this->getEditableMenu(Yii::t('CfilesModule.base', 'Delete'), 'delete', 'trash'),
        ];

        if ($this->canWrite()) {
            $menu[] = $this->getEditableMenu(Yii::t('CfilesModule.base', 'Move'), 'move-files', 'arrows');
        }

        if ($this->zipEnabled()) {
            $menu[] = $this->getMenu(Yii::t('CfilesModule.base', 'Download ZIP'), 'zip', 'file-archive-o');
        }

        return $menu;
    }

    private function getMenuFile(): array
    {
        $menu = [
            $this->getMenu(Yii::t('CfilesModule.base', 'Download'), 'download', 'cloud-download'),
            $this->getMenu(Yii::t('CfilesModule.base', 'Show Post'), 'show-post', 'window-maximize'),
            $this->getMenu(Yii::t('CfilesModule.base', 'Display Url'), 'show-url', 'link'),
        ];

        if (!$this->folder->isAllPostedFiles()) {
            $menu[] = 'separator';
            $menu[] = $this->getEditableMenu(Yii::t('CfilesModule.base', 'Edit'), 'edit-file', 'pencil');
            $menu[] = $this->getEditableMenu(Yii::t('CfilesModule.base', 'Delete'), 'delete', 'trash');
            if ($this->canWrite()) {
                $menu[] = $this->getEditableMenu(Yii::t('CfilesModule.base', 'Move'), 'move-files', 'arrows');
            }
        }

        return $menu;
    }

    private function getMenuImage(): array
    {
        return $this->getMenuFile();
    }

    private function getMenuAllPostedFiles(): array
    {
        return [
            $this->getMenu(Yii::t('CfilesModule.base', 'Open'), 'download', 'folder-open'),
            $this->getMenu(Yii::t('CfilesModule.base', 'Display Url'), 'show-url', 'link'),
        ];
    }

    private function canWrite(): bool
    {
        return $this->folder->content->container->can(ManageFiles::class);
    }

    private function zipEnabled(): bool
    {
        return !Yii::$app->getModule('cfiles')->settings->get('disableZipSupport');
    }

    private function getMenu(string $label, string $action, string $icon, bool $editable = false): array
    {
        return [
            'label' => $label,
            'action' => $action,
            'icon' => $icon,
            'editable' => $editable,
        ];
    }

    private function getEditableMenu(string $label, string $action, string $icon): array
    {
        return $this->getMenu($label, $action, $icon, true);
    }

}