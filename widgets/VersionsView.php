<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2021 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\cfiles\widgets;

use humhub\components\Widget;
use humhub\modules\cfiles\models\File;

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
     * @inheritdoc
     */
    public function run() {
        return $this->render('versionsView', [
            'file' => $this->file,
            'versions' => $this->file->baseFile->getVersions(),
            'currentVersion' => $this->file->baseFile->id,
        ]);
    }

}