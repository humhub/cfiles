<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2017 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 *
 */

/**
 * Created by PhpStorm.
 * User: buddha
 * Date: 27.08.2017
 * Time: 20:30
 */

namespace humhub\modules\cfiles\libs;


use Yii;
use yii\helpers\BaseFileHelper;

class ZipUtil
{

    /**
     * Cleanup all previously created zip files.
     */
    protected function cleanup()
    {
        BaseFileHelper::removeDirectory($this->getTempPath());
    }

    /**
     * Get the output path of the user specified temporary folder used for packing and unpacking zip data for this user.
     *
     * @return string @runtime/temp/[guid]
     */
    protected function getZipOutputPath()
    {
        // init output directory
        $outputPath = $this->getTempPath();
        $outputPath .= DIRECTORY_SEPARATOR . \Yii::$app->user->guid;
        if (!is_dir($outputPath)) {
            mkdir($outputPath);
        }

        return $outputPath;
    }

    /**
     * Get the output path of the base temporary folder used for packing and unpacking zip data for all users.
     *
     * @return string @runtime/temp/[guid]
     */
    protected function getTempPath()
    {
        // init output directory
        $outputPath = Yii::getAlias('@runtime/cfiles-temp');
        if (!is_dir($outputPath)) {
            mkdir($outputPath);
        }
        return $outputPath;
    }

    /**
     * Fixes ZIP location path, removes trailling slash
     *
     * @param string $path
     * @return string the fixed path
     */
    protected function fixPath($path)
    {
        return ltrim($path, DIRECTORY_SEPARATOR);
    }
}