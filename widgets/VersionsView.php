<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2021 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\cfiles\widgets;

use humhub\components\Widget;
use humhub\modules\cfiles\models\File;
use humhub\modules\file\models\FileHistory;
use yii\data\Pagination;

/**
 * Widget for rendering file versions table.
 */
class VersionsView extends Widget
{

    /**
     * @var File
     */
    public $file;

    /**
     * @var int
     */
    public $page = 1;

    /**
     * @var int
     */
    public $pageSize = 10;

    /**
     * @var FileHistory[]
     */
    private $versions;

    /**
     * @var bool
     */
    private $isLastPage = true;

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        $this->initVersions();
    }

    /**
     * Initialise versions
     */
    private function initVersions()
    {
        $pagination = new Pagination([
            'page' => $this->page - 1,
            'pageSize' => $this->pageSize,
            'totalCount' => $this->file->baseFile->getHistoryFiles()->count()
        ]);

        $this->isLastPage = ($pagination->page >= $pagination->pageCount - 1);

        $this->versions = $this->file->baseFile
            ->getHistoryFiles()
            ->offset($pagination->offset)
            ->limit($pagination->limit)
            ->all();
    }

    /**
     * @inheritdoc
     */
    public function run()
    {
        return $this->render('versionsView', [
            'versionsRowsHtml' => $this->renderVersions(),
            'nextPageVersionsUrl' => $this->getNextPageVersionsUrl(),
        ]);
    }

    public function renderVersions(): string
    {
        $html = '';

        if ($this->page == 1) {
            $html .= VersionItem::widget([
                'version' => $this->file->baseFile,
                'revertUrl' => false,
                'downloadUrl' => $this->file->baseFile->getUrl(),
                'deleteUrl' => false,
            ]);
        }

        foreach ($this->versions as $version) {
            $html .= VersionItem::widget([
                'version' => $version,
                'revertUrl' => $this->file->getVersionsUrl($version->id),
                'downloadUrl' => $version->getFileUrl(),
                'deleteUrl' => $this->file->getDeleteVersionUrl($version->id),
            ]);
        }

        return $html;
    }

    public function isLastPage(): bool
    {
        return $this->isLastPage;
    }

    private function getNextPageVersionsUrl(): string
    {
        return $this->isLastPage() ? ''
            : $this->file->content->container->createUrl('/cfiles/version/page', ['id' => $this->file->id]);
    }

}