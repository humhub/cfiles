<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2017 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 *
 */

namespace humhub\modules\cfiles\models;

use Yii;
use humhub\modules\file\handler\BaseFileHandler;

class ZipImportHandler extends BaseFileHandler
{
    /**
     * The file handler link
     *
     * @see \yii\helpers\Html::renderTagAttributes() for details on how attributes are being rendered.
     * @see \humhub\modules\file\widgets\FileHandlerButtonDropdown
     * @return array the HTML attributes of the button.
     */
    public function getLinkAttributes()
    {
        return [
            'label' => Yii::t('CfilesModule.base', 'Import Zip'),
            'data-action-click' => 'file.upload',
            'data-action-target' => '#cfilesUploadZipFile',
        ];
    }
}
