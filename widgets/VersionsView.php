<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2021 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\cfiles\widgets;

use humhub\components\Widget;
use humhub\modules\cfiles\models\File;
use humhub\modules\file\models\File as BaseFile;
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
     * @var BaseFile[]
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
            'totalCount' => $this->file->baseFile->getVersionsQuery()->count()
        ]);

        $this->isLastPage = ($pagination->page >= $pagination->pageCount - 1);

        $this->versions = $this->file->baseFile
            ->getVersionsQuery()
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

        foreach ($this->versions as $versionBaseFile) {
            $html .= VersionItem::widget([
                'file' => $versionBaseFile,
                'isCurrent' => ($this->file->baseFile->id == $versionBaseFile->id),
                'revertUrl' => $this->file->getVersionsUrl($versionBaseFile->id),
                'deleteUrl' => $this->file->getDeleteVersionUrl($versionBaseFile->id),
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