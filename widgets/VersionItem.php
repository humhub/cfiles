<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2021 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\cfiles\widgets;

use humhub\components\Widget;
use humhub\modules\file\models\File as BaseFile;
use humhub\modules\file\models\FileHistory;
use humhub\modules\user\models\User;

/**
 * Widget for rendering file versions table.
 */
class VersionItem extends Widget
{

    /**
     * @var FileHistory|BaseFile
     */
    public $version;

    /**
     * @var string|null
     */
    public $revertUrl;

    /**
     * @var string|null
     */
    public $downloadUrl;

    /**
     * @var string|null
     */
    public $deleteUrl;

    /**
     * @inheritdoc
     */
    public function run() {
        if ($this->isCurrent()) {
            $rowOptions = ['class' => 'bg-warning'];
        } else {
            $rowOptions = ['id' => 'version_file_' . $this->version->id];
        }

        return $this->render('versionItem', [
            'options' => $rowOptions,
            'user' => $this->getUser(),
            'date' => $this->getDate(),
            'size' => $this->getSize(),
            'revertUrl' => $this->revertUrl,
            'downloadUrl' => $this->downloadUrl,
            'deleteUrl' => $this->deleteUrl,
        ]);
    }

    private function isCurrent(): bool
    {
        return $this->version instanceof BaseFile;
    }

    private function getUser(): User
    {
        return $this->isCurrent() ? $this->version->updatedBy : $this->version->createdBy;
    }

    private function getDate(): string
    {
        return $this->isCurrent() ? $this->version->updated_at : $this->version->created_at;
    }

    private function getSize(): string
    {
        return $this->version->size;
    }

}