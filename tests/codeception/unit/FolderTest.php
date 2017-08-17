<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2017 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 *
 */

namespace humhub\modules\cfiles\tests\codeception\unit;

use Yii;
use humhub\modules\cfiles\models\Folder;
use humhub\modules\space\models\Space;
use tests\codeception\_support\HumHubDbTestCase;


/**
 * Created by PhpStorm.
 * User: buddha
 * Date: 16.07.2017
 * Time: 20:52
 */
class FolderTest extends HumHubDbTestCase
{
    public function testCreateRoot()
    {
        $this->becomeUser('Admin');
        $space = Space::findOne(1);
        $rootFolder = Folder::initRoot($space);

        $this->assertTrue($rootFolder instanceof Folder);
        // Prevent double root initialization
        $this->assertFalse(Folder::initRoot($space));
    }
}