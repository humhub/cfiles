<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2017 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 *
 */

namespace humhub\modules\cfiles\tests\codeception\fixtures;

use humhub\modules\cfiles\models\Folder;
use yii\test\ActiveFixture;

class FolderFixtrue extends ActiveFixture
{
    public $modelClass = Folder::class;
    public $dataFile = '@cfiles/tests/codeception/fixtures/data/folder.php';
   
}
