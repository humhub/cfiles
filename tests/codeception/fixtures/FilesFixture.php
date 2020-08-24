<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2017 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 *
 */

namespace humhub\modules\cfiles\tests\codeception\fixtures;

use humhub\modules\cfiles\models\File;
use yii\test\ActiveFixture;

class FilesFixture extends ActiveFixture
{
    public $modelClass = File::class;
    public $dataFile = '@cfiles/tests/codeception/fixtures/data/file.php';
    
     public $depends = [
         FolderFixtrue::class
    ];
}
