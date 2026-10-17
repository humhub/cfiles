<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2026 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\cfiles\tests\codeception\unit;

use humhub\modules\cfiles\models\File;
use humhub\modules\cfiles\models\Folder;
use humhub\modules\cfiles\services\FolderContentService;
use humhub\modules\space\models\Space;
use tests\codeception\_support\HumHubDbTestCase;
use Yii;

/**
 * The file model's own behaviour.
 */
class FileTest extends HumHubDbTestCase
{
    private Space $space;

    public function _before()
    {
        parent::_before();
        $this->becomeUser('Admin');
        $this->space = Space::findOne(1);
    }

    /**
     * A file's URL opens the level of the browser it lies in. What notifications, search
     * results and the content permalink link to.
     */
    public function testAFileLinksToTheFolderItLiesIn()
    {
        $folder = (new FolderContentService($this->space))->newFolder('Projects', '');
        $this->assertTrue($folder->save());

        $file = $this->addFile('inside.txt', $folder);

        $this->assertSame($folder->getUrl(true), $file->getUrl(true));
        $this->assertSame($folder->getUrl(true), $file->content->getUrl(true));
    }

    /**
     * The top level has no folder record to link to, so a file there links to the browser's
     * top level instead of to nothing.
     */
    public function testAFileAtTheTopLevelLinksToTheTopLevel()
    {
        $file = $this->addFile('top.txt');

        $this->assertSame($this->space->createUrl('/cfiles/browse/index', [], true), $file->getUrl(true));
        $this->assertSame($this->space->createUrl('/cfiles/browse/index', [], true), $file->content->getUrl(true));
    }

    private function addFile(string $name, ?Folder $parent = null): File
    {
        $path = Yii::getAlias('@runtime') . '/' . $name;
        file_put_contents($path, 'test');

        $file = (new FolderContentService($this->space, $parent))->addFileFromPath($name, $path);
        $this->assertFalse($file->hasErrors(), implode(' ', $file->getFirstErrors()));

        return $file;
    }
}
