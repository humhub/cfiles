<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2026 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\cfiles\tests\codeception\unit;

use humhub\modules\cfiles\serializers\FileSerializer;
use humhub\modules\cfiles\services\FolderContentService;
use humhub\modules\space\models\Space;
use tests\codeception\_support\HumHubDbTestCase;
use yii\web\UploadedFile;

/**
 * `icon` is the Tabler glyph name the Vue browser renders directly (`ti ti-<icon>`) - this
 * pins the extension it names to the name {@see \humhub\libs\MimeHelper::getIconNameByExtension()}
 * returns, so a regression there shows up here too.
 */
class FileSerializerTest extends HumHubDbTestCase
{
    public function testIconIsTheFileTypesTablerGlyph()
    {
        $this->becomeUser('Admin');
        $space = Space::findOne(1);

        $file = (new FolderContentService($space))->addUploadedFile(new UploadedFile([
            'name' => 'report.pdf',
            'size' => 1024,
            'type' => 'application/pdf',
        ]));

        $this->assertFalse($file->hasErrors(), implode(' ', $file->getFirstErrors()));

        $this->assertSame('file-type-pdf', FileSerializer::file($file)['icon']);
        // The same glyph the stream entry draws (see WallEntryFile::getIcon()) - one mapping,
        // read by both the API payload and the server-rendered entry.
        $this->assertSame('file-type-pdf', $file->getIcon());
    }

    public function testIconFallsBackToFileForAnUnknownExtension()
    {
        $this->becomeUser('Admin');
        $space = Space::findOne(1);

        $file = (new FolderContentService($space))->addUploadedFile(new UploadedFile([
            'name' => 'notes.xyz',
            'size' => 1024,
            'type' => 'application/octet-stream',
        ]));

        $this->assertFalse($file->hasErrors(), implode(' ', $file->getFirstErrors()));

        $this->assertSame('file', FileSerializer::file($file)['icon']);
        $this->assertSame('file', $file->getIcon());
    }
}
