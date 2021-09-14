<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2021 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\cfiles\widgets;

use humhub\components\Widget;
use humhub\modules\cfiles\models\FileSystemItem;

/**
 * Widget for rendering file versions table.
 */
class VersionsView extends Widget
{

    /**
     * @var FileSystemItem
     */
    public $file;

    /**
     * @inheritdoc
     */
    public function run() {
        return $this->render('versionsView', [
            'file' => $this->file,
            'versions' => $this->file->getVersionsQuery()->all(),
            'currentVersion' => $this->file->getCurrentVersionId(),
        ]);
    }

}