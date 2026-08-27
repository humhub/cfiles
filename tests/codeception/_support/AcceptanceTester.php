<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2017 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 *
 */

namespace cfiles;

use humhub\modules\file\models\File as BaseFile;
use Yii;

/**
 * Inherited Methods
 * @method void wantToTest($text)
 * @method void wantTo($text)
 * @method void execute($callable)
 * @method void expectTo($prediction)
 * @method void expect($prediction)
 * @method void amGoingTo($argumentation)
 * @method void am($role)
 * @method void lookForwardTo($achieveValue)
 * @method void comment($description)
 * @method \Codeception\Lib\Friend haveFriend($name, $actorClass = null)
 *
 * @SuppressWarnings(PHPMD)
*/
class AcceptanceTester extends \AcceptanceTester
{
    use _generated\AcceptanceTesterActions;

    public function enableCfilesOnSpace($guid = 1)
    {
        $this->enableModule($guid, 'cfiles');

        $this->amOnSpace($guid);
        $this->expectTo('see files in the space nav');
        $this->waitForText('Files', 30, '.layout-nav-container');

        $this->amOnFilesBrowser();
    }

    public function seeFileSizeOnSpaceStream(BaseFile $file, $guid = 1)
    {
        $this->amOnSpace($guid);
        $this->waitForText($file->file_name);
        $this->see('Size: ' . Yii::$app->formatter->asShortSize($file->size, 1));
    }
}
