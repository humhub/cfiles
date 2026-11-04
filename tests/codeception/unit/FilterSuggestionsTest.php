<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2026 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\cfiles\tests\codeception\unit;

use humhub\components\listing\ListContext;
use humhub\modules\cfiles\components\FilterSuggestions;
use humhub\modules\cfiles\components\FolderList;
use humhub\modules\cfiles\components\GlobalFolderList;
use humhub\modules\cfiles\models\File;
use humhub\modules\cfiles\services\FolderContentService;
use humhub\modules\content\models\Content;
use humhub\modules\content\models\ContentContainerModuleState;
use humhub\modules\space\models\Space;
use humhub\modules\topic\models\Topic;
use humhub\modules\user\models\User;
use tests\codeception\_support\HumHubDbTestCase;
use Yii;
use yii\helpers\Url;

/**
 * What the picker filters of the file browser and the global files page suggest
 * ({@see FilterSuggestions}): the spaces, authors and topics of the latest files the user may read.
 *
 * User2 is a member of the spaces 1, 3 and 4; space 2 is public, User2 no member of it. The
 * module is enabled in the spaces 1, 2 and 3.
 */
class FilterSuggestionsTest extends HumHubDbTestCase
{
    private int $minute = 0;

    public function _before()
    {
        parent::_before();

        ContentContainerModuleState::deleteAll(['module_id' => 'cfiles']);
        Yii::$app->getModule('cfiles')->settings->delete('moduleManager.defaultState.Space');
        foreach ([1, 2, 3] as $spaceId) {
            $record = new ContentContainerModuleState([
                'module_id' => 'cfiles',
                'contentcontainer_id' => Space::findOne($spaceId)->contentcontainer_id,
                'module_state' => ContentContainerModuleState::STATE_ENABLED,
            ]);
            $this->assertTrue($record->save());
        }
    }

    public function testSuggestsTheSpacesOfThePageWithTheLatestFilesFirst()
    {
        $this->becomeUser('Admin');
        $this->addFile('a.txt', 1);
        $this->addFile('b.txt', 3);
        $this->addFile('public.txt', 2);
        $this->becomeUser('User2');

        $this->assertSame([3, 1], FilterSuggestions::spaces($this->context(), 6), 'member spaces with the module only, the latest file first');
        $this->assertSame([3], FilterSuggestions::spaces($this->context(), 1));
    }

    public function testSuggestsSpacesWithoutFilesByName()
    {
        $this->becomeUser('User2');

        $expected = Space::find()->where(['id' => [1, 3]])->orderBy(['name' => SORT_ASC])->select('id')->column();

        $this->assertSame(array_map('intval', $expected), FilterSuggestions::spaces($this->context(), 6));
    }

    public function testSuggestsTheAuthorsOfTheLatestFilesOnce()
    {
        $this->becomeUser('Admin');
        $this->addFile('old.txt', 1);
        $this->becomeUser('User2');
        $this->addFile('newer.txt', 3);
        $this->becomeUser('Admin');
        $this->addFile('newest.txt', 1);
        $this->addFile('hidden.txt', 2);
        $this->becomeUser('User2');

        $admin = User::findOne(['username' => 'admin'])->id;
        $user2 = User::findOne(['username' => 'User2'])->id;

        $this->assertSame([$admin, $user2], FilterSuggestions::authors($this->context(), 6), 'each once, by their latest file, none of a space outside the page');
    }

    public function testSuggestsTheAuthorsOfTheBrowsedSpaceOnly()
    {
        $this->becomeUser('Admin');
        $this->addFile('one.txt', 1);
        $this->becomeUser('User2');
        $this->addFile('three.txt', 3);

        $this->assertSame([User::findOne(['username' => 'admin'])->id], FilterSuggestions::authors($this->context(1), 6));
    }

    public function testSuggestsTheTopicsOfTheLatestFiles()
    {
        $this->becomeUser('Admin');
        $budget = $this->topic('Budget', 1);
        $brand = $this->topic('Brand', 1);
        Topic::attach($this->addFile('old.txt', 1)->content, [$budget]);
        Topic::attach($this->addFile('new.txt', 1)->content, [$brand, $budget]);
        $this->addFile('untagged.txt', 1);
        $this->becomeUser('User2');

        $suggested = FilterSuggestions::topics($this->context(), 6);

        $this->assertEqualsCanonicalizing([$budget->id, $brand->id], $suggested, 'each once');
        $this->assertCount(2, $suggested);
    }

    public function testTheDefinitionsNameTheEndpointAndTheListsAnswerIt()
    {
        $this->becomeUser('Admin');
        $this->addFile('a.txt', 1);
        $this->becomeUser('User2');

        $global = array_column((new GlobalFolderList())->definitions($this->context()), null, 'key');
        foreach (['spaceId', 'userId', 'topicId'] as $key) {
            $this->assertSame(Url::to(['/cfiles/api/global/suggestions', 'filter' => $key]), $global[$key]['props']['suggestionsUrl'], $key);
        }
        $this->assertArrayNotHasKey('props', $global['type'], 'a filter without suggestions names none');
        $this->assertArrayNotHasKey('suggestions', $global['spaceId']['props'], 'nothing computed with the page');

        $containerId = Space::findOne(3)->contentcontainer_id;
        $inSpace = array_column((new FolderList())->definitions($this->context(3)), null, 'key');
        $this->assertSame(Url::to(['/cfiles/api/folder/suggestions', 'containerId' => $containerId, 'filter' => 'userId']), $inSpace['userId']['props']['suggestionsUrl']);

        $this->assertSame([1, 3], array_column((new GlobalFolderList())->suggestions('spaceId', $this->context()), 'id'));
        $this->assertSame([(int)User::findOne(['username' => 'admin'])->id], array_column((new GlobalFolderList())->suggestions('userId', $this->context()), 'id'));
        $this->assertSame([], (new FolderList())->suggestions('userId', $this->context(3)), 'no file in space 3');
        $this->assertNull((new GlobalFolderList())->suggestions('type', $this->context()), 'no such filter');
    }

    private function context(?int $spaceId = null): ListContext
    {
        return ListContext::forCurrentUser(null, $spaceId !== null ? Space::findOne($spaceId) : null);
    }

    private function topic(string $name, int $spaceId): Topic
    {
        $topic = new Topic(['name' => $name, 'contentcontainer_id' => Space::findOne($spaceId)->contentcontainer_id]);
        $this->assertTrue($topic->save());

        return $topic;
    }

    /**
     * A file, changed a minute after the one added before.
     */
    private function addFile(string $name, int $spaceId): File
    {
        $path = Yii::getAlias('@runtime') . '/' . $name;
        file_put_contents($path, 'test');

        $file = (new FolderContentService(Space::findOne($spaceId)))->addFileFromPath($name, $path);
        $this->assertFalse($file->hasErrors(), implode(' ', $file->getFirstErrors()));

        $changed = date('Y-m-d H:i:s', strtotime('2026-01-01 12:00:00') + 60 * ++$this->minute);
        Content::updateAll(['created_at' => $changed, 'updated_at' => $changed], ['id' => $file->content->id]);

        return $file;
    }
}
