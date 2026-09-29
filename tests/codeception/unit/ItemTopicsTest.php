<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2026 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\cfiles\tests\codeception\unit;

use humhub\components\listing\ListContext;
use humhub\modules\cfiles\components\FolderList;
use humhub\modules\cfiles\controllers\api\FileController;
use humhub\modules\cfiles\controllers\api\FolderController;
use humhub\modules\cfiles\models\File;
use humhub\modules\cfiles\models\Folder;
use humhub\modules\cfiles\serializers\FileSerializer;
use humhub\modules\cfiles\serializers\FolderSerializer;
use humhub\modules\cfiles\services\FolderContentService;
use humhub\modules\cfiles\services\FolderListingService;
use humhub\modules\content\models\Content;
use humhub\modules\space\models\Space;
use humhub\modules\topic\models\Topic;
use tests\codeception\_support\HumHubDbTestCase;
use Yii;
use yii\db\Command;

/**
 * The topics of a file or folder: `topics` of `PATCH api/v2/cfiles/file/<id>` and
 * `PATCH api/v2/cfiles/folder/<id>` (the topic ids of the item's container, or global ones;
 * `''` clears them), and the `topics` the serializers answer.
 */
class ItemTopicsTest extends HumHubDbTestCase
{
    private Space $space;

    public function _before()
    {
        parent::_before();
        $this->becomeUser('Admin');
        Yii::$app->response->statusCode = 200;

        $this->space = Space::findOne(1);
        if (!$this->space->moduleManager->isEnabled('cfiles')) {
            $this->space->moduleManager->enable('cfiles');
        }
    }

    public function testAFilesTopicsAreSaved()
    {
        $file = $this->addFile('report.txt');
        $budget = $this->addTopic('Budget', $this->space);
        $global = $this->addTopic('Everywhere', null);

        $answer = $this->updateFile($file, ['topics' => [(string)$budget->id, (string)$global->id]]);

        $this->assertSame(200, Yii::$app->response->statusCode);
        $this->assertEqualsCanonicalizing([$budget->id, $global->id], $this->topicIds($file->content));
        $this->assertEqualsCanonicalizing(['Budget', 'Everywhere'], array_column($answer['topics'], 'name'));
        $this->assertSame(['id', 'name', 'color'], array_keys($answer['topics'][0]));
    }

    public function testAFoldersTopicsAreSaved()
    {
        $folder = $this->addFolder('Brand');
        $budget = $this->addTopic('Budget', $this->space);

        $answer = $this->updateFolder($folder, ['topics' => [(string)$budget->id]]);

        $this->assertSame(200, Yii::$app->response->statusCode);
        $this->assertSame([$budget->id], $this->topicIds($folder->content));
        $this->assertSame([['id' => $budget->id, 'name' => 'Budget', 'color' => $budget->color ?: null]], $answer['topics']);
    }

    public function testATopicOfAnotherSpaceIsA422()
    {
        $file = $this->addFile('report.txt');
        $folder = $this->addFolder('Brand');
        $foreign = $this->addTopic('Elsewhere', Space::findOne(2));

        $answer = $this->updateFile($file, ['topics' => [(string)$foreign->id], 'description' => 'changed']);
        $this->assertSame(422, Yii::$app->response->statusCode);
        $this->assertArrayHasKey('topics', $answer['errors']);
        $this->assertSame([], $this->topicIds($file->content));
        $this->assertNotSame('changed', File::findOne($file->id)->description);

        Yii::$app->response->statusCode = 200;
        $answer = $this->updateFolder($folder, ['topics' => [(string)$foreign->id]]);
        $this->assertSame(422, Yii::$app->response->statusCode);
        $this->assertArrayHasKey('topics', $answer['errors']);
        $this->assertSame([], $this->topicIds($folder->content));
    }

    public function testAnIdOfNoTopicIsA422()
    {
        $file = $this->addFile('report.txt');

        $answer = $this->updateFile($file, ['topics' => ['999999']]);

        $this->assertSame(422, Yii::$app->response->statusCode);
        $this->assertArrayHasKey('topics', $answer['errors']);
    }

    public function testAnEmptyValueClearsTheTopics()
    {
        $file = $this->addFile('report.txt');
        $folder = $this->addFolder('Brand');
        $budget = $this->addTopic('Budget', $this->space);
        Topic::attach($file->content, [$budget]);
        Topic::attach($folder->content, [$budget]);

        $this->assertSame([], $this->updateFile($file, ['topics' => ''])['topics']);
        $this->assertSame([], $this->topicIds($file->content));

        $this->assertSame([], $this->updateFolder($folder, ['topics' => ''])['topics']);
        $this->assertSame([], $this->topicIds($folder->content));
    }

    /**
     * A write without `topics` (a rename) keeps them — also the parent folder, which every
     * write inside it saves.
     */
    public function testAWriteWithoutTopicsKeepsThem()
    {
        $folder = $this->addFolder('Brand');
        $file = $this->addFile('report.txt', $folder);
        $budget = $this->addTopic('Budget', $this->space);
        Topic::attach($file->content, [$budget]);
        Topic::attach($folder->content, [$budget]);

        $this->assertSame(['Budget'], array_column($this->updateFile($file, ['title' => 'renamed.txt'])['topics'], 'name'));
        $this->assertSame([$budget->id], $this->topicIds($file->content));
        $this->assertSame([$budget->id], $this->topicIds($folder->content));

        $this->updateFolder($folder, ['title' => 'Renamed']);
        $this->assertSame([$budget->id], $this->topicIds($folder->content));
    }

    public function testTheSerializersAnswerTheTopics()
    {
        $folder = $this->addFolder('Brand');
        $file = $this->addFile('report.txt', $folder);
        $budget = $this->addTopic('Budget', $this->space);
        Topic::attach($file->content, [$budget]);

        $this->assertSame(['Budget'], array_column(FileSerializer::file(File::findOne($file->id))['topics'], 'name'));
        $this->assertSame([], FolderSerializer::folder(Folder::findOne($folder->id))['topics']);
    }

    /**
     * A page of the listing carries the topics of every row, and asks for them once.
     */
    public function testTheRowsOfAListingCarryTheirTopicsFromOneQuery()
    {
        $budget = $this->addTopic('Budget', $this->space);
        $brand = $this->addTopic('Brand', $this->space);
        $folder = $this->addFolder('Brand');
        $tagged = $this->addFile('tagged.txt');
        $this->addFile('plain.txt');
        Topic::attach($folder->content, [$brand]);
        Topic::attach($tagged->content, [$budget, $brand]);

        $db = Yii::$app->db;
        $commandClass = $db->commandClass;
        $db->commandClass = RecordingCommand::class;
        RecordingCommand::$queries = [];
        try {
            $rows = (new FolderListingService(
                (new FolderList())->build([], ListContext::forCurrentUser(null, $this->space)),
            ))->payload()['results'];
        } finally {
            $db->commandClass = $commandClass;
        }
        $topicQueries = array_filter(RecordingCommand::$queries, static fn(string $sql) => str_contains($sql, 'content_tag_relation'));

        $byTitle = array_column($rows, 'topics', 'title');
        $this->assertSame(['Brand'], array_column($byTitle['Brand'], 'name'));
        $this->assertEqualsCanonicalizing(['Budget', 'Brand'], array_column($byTitle['tagged.txt'], 'name'));
        $this->assertSame([], $byTitle['plain.txt']);
        $this->assertCount(1, $topicQueries);
    }

    private function updateFile(File $file, array $body): array
    {
        Yii::$app->request->setBodyParams($body);

        return (new FileController('file', Yii::$app->getModule('cfiles')))->actionUpdate($file->id);
    }

    private function updateFolder(Folder $folder, array $body): array
    {
        Yii::$app->request->setBodyParams($body);

        return (new FolderController('folder', Yii::$app->getModule('cfiles')))->actionUpdate($folder->id);
    }

    /**
     * @return int[] the ids of the content's topics, as stored
     */
    private function topicIds(Content $content): array
    {
        $ids = array_map('intval', Topic::findByContent(Content::findOne($content->id))->select('content_tag.id')->column());
        sort($ids);

        return $ids;
    }

    private function addTopic(string $name, ?Space $space): Topic
    {
        $topic = new Topic(['name' => $name, 'contentcontainer_id' => $space?->contentcontainer_id]);
        $this->assertTrue($topic->save(), implode(' ', $topic->getFirstErrors()));

        return $topic;
    }

    private function addFolder(string $title): Folder
    {
        $folder = (new FolderContentService($this->space))->newFolder($title, '');
        $this->assertTrue($folder->save(), implode(' ', $folder->getFirstErrors()));

        return $folder;
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

/**
 * A command that records the SQL of every query it runs, for counting them.
 */
class RecordingCommand extends Command
{
    /**
     * @var string[]
     */
    public static array $queries = [];

    /**
     * @inheritdoc
     */
    protected function queryInternal($method, $fetchMode = null)
    {
        self::$queries[] = $this->getRawSql();

        return parent::queryInternal($method, $fetchMode);
    }
}
