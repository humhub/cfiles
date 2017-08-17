<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2017 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 *
 */

return [
    'modules' => ['cfiles'],
    'fixtures' => [
        'default',
        'calendar_entry' => \humhub\modules\calendar\tests\codeception\fixtures\FilesFixture::class
    ]
];



