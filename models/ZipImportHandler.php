<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2017 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 *
 */

namespace humhub\modules\cfiles\models;

use humhub\modules\file\handler\BaseFileHandler;
use humhub\modules\ui\icon\widgets\Icon;
use Yii;

class ZipImportHandler extends BaseFileHandler
{
    public string $icon = 'file-archive-o';

    /**
     * The file handler link
     *
     * @return array the HTML attributes of the button.
     * @see \humhub\modules\file\widgets\FileHandlerButtonDropdown
     * @see \yii\helpers\Html::renderTagAttributes() for details on how attributes are being rendered.
     */
    public function getLinkAttributes()
    {
        return [
            'label' => Icon::get($this->icon) . Yii::t('CfilesModule.base', 'Import Zip'),
            'data-action-click' => 'file.upload',
            'data-action-target' => '#cfilesUploadZipFile',
        ];
    }
}
