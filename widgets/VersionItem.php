<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2021 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\cfiles\widgets;

use humhub\components\Widget;
use humhub\modules\file\models\File as BaseFile;

/**
 * Widget for rendering file versions table.
 */
class VersionItem extends Widget
{

    /**
     * @var BaseFile
     */
    public $file;

    /**
     * @var bool Is current version
     */
    public $isCurrent = false;

    /**
     * @var string|null
     */
    public $revertUrl;

    /**
     * @var string|null
     */
    public $deleteUrl;

    /**
     * @inheritdoc
     */
    public function run() {
        $rowOptions = ['id' => 'version_file_' . $this->file->id];

        if ($this->isCurrent) {
            $rowOptions['class'] = 'bg-warning';
        }

        return $this->render('versionItem', [
            'options' => $rowOptions,
            'file' => $this->file,
            'revertUrl' => $this->revertUrl,
            'downloadUrl' => $this->file->getUrl(),
            'deleteUrl' => $this->deleteUrl,
        ]);
    }

}