<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2026 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\cfiles\tests\codeception\unit;

use humhub\components\listing\ListContext;
use humhub\components\listing\ListEvent;
use humhub\components\listing\ListValidationException;
use humhub\modules\cfiles\components\GlobalFolderList;
use humhub\modules\cfiles\models\File;
use humhub\modules\cfiles\models\Folder;
use humhub\modules\cfiles\services\FolderContentService;
use humhub\modules\cfiles\services\FolderListingService;
use humhub\modules\cfiles\services\GlobalListingService;
use humhub\modules\content\models\Content;
use humhub\modules\content\models\ContentContainerModuleState;
use humhub\modules\space\models\Membership;
use humhub\modules\space\models\Space;
use humhub\modules\topic\models\Topic;
use tests\codeception\_support\HumHubDbTestCase;
use Yii;

/**
 * The global files page's list ({@see GlobalFolderList}): one tile per member space with the
 * module, or — with a filter — the hits across all of them.
 *
 * User2 is a member of the spaces 1, 3 and 4; space 2 is public, space 5 private, and User2 a
 * member of neither. The module is enabled in the spaces 1 and 3 (and in 2, which is no member
 * space), not in 4.
 */
class GlobalFolderListTest extends HumHubDbTestCase
{
    public function _before()
    {
        parent::_before();

        ContentContainerModuleState::deleteAll(['module_id' => 'cfiles']);
        $this->setDefaultState(null);
        foreach ([1, 2, 3] as $spaceId) {
            $this->setState($spaceId, ContentContainerModuleState::STATE_ENABLED);
        }

        $this->becomeUser('User2');
    }

    public function testTheTilesAreTheMemberSpacesWithTheModule()
    {
        $payload = $this->payload();

        $this->assertFalse($payload['resultsMode']);
        $this->assertSame([1, 3], array_column($payload['results'], 'id'));
        $this->assertSame(['space', 'space'], array_column($payload['results'], 'type'));
    }

    public function testATileDescribesItsSpace()
    {
        $space = Space::findOne(1);

        $tile = $this->payload()['results'][0];

        $this->assertSame($space->id, $tile['id']);
        $this->assertSame($space->contentcontainer_id, $tile['contentContainerId']);
        $this->assertSame($space->guid, $tile['guid']);
        $this->assertSame($space->name, $tile['name']);
        $this->assertArrayHasKey('imageUrl', $tile);
        $this->assertArrayHasKey('color', $tile);
        $this->assertSame(0, $tile['itemCount']);
    }

    public function testAnArchivedMemberSpaceIsNoTile()
    {
        Space::updateAll(['status' => Space::STATUS_ARCHIVED], ['id' => 3]);

        $this->assertSame([1], array_column($this->payload()['results'], 'id'));
    }

    /**
     * A module enabled by default for spaces is enabled in every space without a state of its
     * own — but not in one that switched it off.
     */
    public function testADisabledMemberSpaceIsNoTile()
    {
        Space::updateAll(['status' => Space::STATUS_DISABLED], ['id' => 3]);

        $this->assertSame([1], array_column($this->payload()['results'], 'id'));
    }

    public function testASpaceTheUserOnlyAppliedToIsNoTile()
    {
        Membership::updateAll(['status' => Membership::STATUS_APPLICANT], ['space_id' => 3, 'user_id' => 3]);

        $this->assertSame([1], array_column($this->payload()['results'], 'id'));
    }

    public function testATileLinksTheSpaceAndItsFileBrowser()
    {
        $space = Space::findOne(1);

        $tile = $this->payload()['results'][0];

        $this->assertSame($space->getUrl(true), $tile['url']);
        $this->assertSame($space->createUrl('/cfiles/browse/index'), $tile['browseUrl']);
    }

    /**
     * A restriction on EVENT_BUILD narrows the spaces before the items are built, so the hits
     * respect it as the tiles do.
     */
    public function testABuildHandlerNarrowingTheSpacesNarrowsTilesAndHits()
    {
        $this->becomeUser('Admin');
        $this->addFile('logo-1.png', 1);
        $this->addFile('logo-3.png', 3);
        $this->becomeUser('User2');

        $build = function (array $params): array {
            $list = new GlobalFolderList();
            $list->on(GlobalFolderList::EVENT_BUILD, static function (ListEvent $event) {
                $event->builder->spaceQuery()->andWhere(['space.id' => 3]);
            });

            return (new GlobalListingService($list->build($params, ListContext::forCurrentUser())))->payload();
        };

        $this->assertSame([3], array_column($build([])['results'], 'id'));
        $this->assertSame(['logo-3.png'], array_column($build(['q' => 'logo'])['results'], 'title'));
    }

    public function testASpaceWithoutAStateFollowsTheDefault()
    {
        $this->setDefaultState(ContentContainerModuleState::STATE_ENABLED);
        $this->assertSame([1, 3, 4], array_column($this->payload()['results'], 'id'));

        $this->setState(3, ContentContainerModuleState::STATE_DISABLED);
        $this->assertSame([1, 4], array_column($this->payload()['results'], 'id'));

        $this->setDefaultState(ContentContainerModuleState::STATE_FORCE_ENABLED);
        $this->assertSame([1, 4], array_column($this->payload()['results'], 'id'), 'a stored state wins, as in the module manager');
    }

    public function testAModuleNotAvailableForSpacesHasNoTiles()
    {
        $this->setDefaultState(ContentContainerModuleState::STATE_NOT_AVAILABLE);

        $this->assertSame([], $this->payload()['results']);
    }

    public function testTheItemCountIsTheReadableTopLevelItemsOfEachSpace()
    {
        $this->becomeUser('Admin');
        $top = $this->addFolder('Top', 1);
        $this->addFolder('Inside', 1, $top);
        $this->addFile('top.txt', 1);
        $this->addFile('inside.txt', 1, $top);
        $deleted = $this->addFile('deleted.txt', 1);
        Content::updateAll(['state' => Content::STATE_DELETED], ['id' => $deleted->content->id]);
        $this->addFile('other.txt', 3);
        $this->becomeUser('User2');

        $this->assertSame([2, 1], array_column($this->payload()['results'], 'itemCount'));
    }

    public function testTheTilesSortByName()
    {
        Space::updateAll(['name' => 'Alpha'], ['id' => 3]);

        $this->assertSame([3, 1], array_column($this->payload()['results'], 'id'));
        $this->assertSame([1, 3], array_column($this->payload(['sort' => 'nameDesc'])['results'], 'id'));
        $this->assertSame([3, 1], array_column($this->payload(['sort' => 'newest'])['results'], 'id'), 'no other order for tiles');
        $this->assertSame('nameDesc', $this->payload(['sort' => 'nameDesc'])['sort']);
    }

    public function testTheTilesArePaged()
    {
        $payload = $this->payload([], 2, 1);

        $this->assertSame([3], array_column($payload['results'], 'id'));
        $this->assertSame(2, $payload['total']);
        $this->assertSame(2, $payload['page']);
        $this->assertSame(2, $payload['pages']);
        $this->assertSame(1, $payload['pageSize']);
    }

    public function testSpaceIdNarrowsTheTiles()
    {
        $payload = $this->payload(['spaceId' => '3']);

        $this->assertFalse($payload['resultsMode'], 'a space alone is no result filter');
        $this->assertSame([3], array_column($payload['results'], 'id'));
    }

    public function testSpaceIdNarrowsTheResults()
    {
        $this->becomeUser('Admin');
        $this->addFile('logo-1.png', 1);
        $this->addFile('logo-3.png', 3);
        $this->becomeUser('User2');

        $this->assertSame(['logo-1.png', 'logo-3.png'], array_column($this->payload(['q' => 'logo', 'sort' => 'name'])['results'], 'title'));
        $this->assertSame(['logo-3.png'], array_column($this->payload(['q' => 'logo', 'spaceId' => '3'])['results'], 'title'));
    }

    public function testASpaceTheUserCannotSeeIsRefused()
    {
        try {
            $this->build(['spaceId' => '5']);
            $this->fail('A space the user may not see must be refused.');
        } catch (ListValidationException $e) {
            $this->assertArrayHasKey('spaceId', $e->errors);
        }
    }

    public function testAVisibleSpaceOutsideTheScopeListsNothing()
    {
        $this->becomeUser('Admin');
        $this->addFile('logo-2.png', 2);
        $this->becomeUser('User2');

        $this->assertSame([], $this->payload(['spaceId' => '2'])['results']);

        $results = $this->payload(['spaceId' => '2', 'q' => 'logo']);
        $this->assertTrue($results['resultsMode']);
        $this->assertSame([], $results['results']);
        $this->assertSame(0, $results['total']);
    }

    public function testTheResultsSpanTheSpacesAndStartTheirPathWithTheSpace()
    {
        $this->becomeUser('Admin');
        $brand = $this->addFolder('Brand', 3);
        $this->addFile('logo-top.png', 1);
        $this->addFile('logo-brand.png', 3, $brand);
        $this->becomeUser('User2');

        $payload = $this->payload(['q' => 'logo', 'sort' => 'name']);

        $space1 = Space::findOne(1);
        $space3 = Space::findOne(3);
        $entry = static fn(Space $space) => ['type' => 'space', 'id' => $space->id, 'contentContainerId' => $space->contentcontainer_id, 'guid' => $space->guid, 'title' => $space->name];

        $this->assertTrue($payload['resultsMode']);
        $this->assertSame(
            ['logo-brand.png' => [$entry($space3), ['type' => 'folder', 'id' => $brand->id, 'title' => 'Brand']], 'logo-top.png' => [$entry($space1)]],
            array_column($payload['results'], 'path', 'title'),
        );
    }

    public function testAnUnreadableFolderCutsOffItsSubtree()
    {
        $this->becomeUser('Admin');
        $hidden = $this->addFolder('Hidden', 1);
        $this->addFile('logo-hidden.png', 1, $hidden);
        $this->addFile('logo-visible.png', 3);
        Content::updateAll(['state' => Content::STATE_DELETED], ['id' => $hidden->content->id]);
        $this->becomeUser('User2');

        $this->assertSame(['logo-visible.png'], array_column($this->payload(['q' => 'logo'])['results'], 'title'));
    }

    public function testTopicNarrowsTheResults()
    {
        $this->becomeUser('Admin');
        $tagged = $this->addFile('tagged.txt', 1);
        $this->addFile('untagged.txt', 1);
        $topic = new Topic(['name' => 'Budget', 'contentcontainer_id' => Space::findOne(1)->contentcontainer_id]);
        $this->assertTrue($topic->save());
        Topic::attach($tagged->content, [$topic]);
        $this->becomeUser('User2');

        $payload = $this->payload(['topicId' => (string)$topic->id]);

        $this->assertTrue($payload['resultsMode']);
        $this->assertSame(['tagged.txt'], array_column($payload['results'], 'title'));
    }

    public function testEachFilterButTheSpaceSwitchesToResultsMode()
    {
        foreach (['q' => 'x', 'userId' => '1', 'type' => 'image', 'modified' => '7d'] as $key => $value) {
            $this->assertTrue($this->build([$key => $value])->resultsMode, $key);
        }
        $this->assertFalse($this->build(['spaceId' => '1', 'sort' => 'name'])->resultsMode);
    }

    public function testWithoutAnyMemberSpaceTheListIsEmpty()
    {
        ContentContainerModuleState::deleteAll(['module_id' => 'cfiles']);

        $this->assertSame([], $this->payload()['results']);
        $this->assertSame(0, $this->payload()['total']);
        $this->assertSame([], $this->payload(['q' => 'x'])['results']);
    }

    public function testBothModesAnswerTheKeysOfTheFolderListing()
    {
        $keys = [...array_keys($this->folderPayload()), 'global', 'space'];
        sort($keys);

        foreach ([[], ['q' => 'x']] as $params) {
            $payload = $this->payload($params);
            $actual = array_keys($payload);
            sort($actual);

            $this->assertSame($keys, $actual);
            $this->assertTrue($payload['global']);
            $this->assertNull($payload['space']);
            $this->assertNull($payload['folder']);
            $this->assertSame([], $payload['path']);
            $this->assertSame([], $payload['likeStates']);
        }
    }

    public function testAnUnknownParameterIsRefused()
    {
        try {
            $this->build(['parent' => '1']);
            $this->fail('An unknown parameter must be refused.');
        } catch (ListValidationException $e) {
            $this->assertArrayHasKey('parent', $e->errors);
        }
    }

    public function testDefinitionsComeInTheOrderOfTheBar()
    {
        $definitions = array_column((new GlobalFolderList())->definitions(ListContext::forCurrentUser()), null, 'key');

        $this->assertSame(['q', 'spaceId', 'userId', 'topicId', 'type', 'modified', 'sort'], array_keys($definitions));
        $this->assertSame('space', $definitions['spaceId']['type']);
        $this->assertSame('Space', $definitions['spaceId']['label']);
        $this->assertSame('member', $definitions['spaceId']['props']['scope']);
        $this->assertSame('topic', $definitions['topicId']['type']);
        $this->assertArrayNotHasKey('containerId', $definitions['topicId']['props'] ?? []);
    }

    private function build(array $params)
    {
        return (new GlobalFolderList())->build($params, ListContext::forCurrentUser());
    }

    private function payload(array $params = [], int $page = 1, ?int $pageSize = null): array
    {
        return (new GlobalListingService($this->build($params)))->payload($page, $pageSize);
    }

    private function folderPayload(): array
    {
        $builder = (new \humhub\modules\cfiles\components\FolderList())->build([], ListContext::forCurrentUser(null, Space::findOne(1)));

        return (new FolderListingService($builder))->payload();
    }

    private function setState(int $spaceId, int $state): void
    {
        $containerId = Space::findOne($spaceId)->contentcontainer_id;
        ContentContainerModuleState::deleteAll(['module_id' => 'cfiles', 'contentcontainer_id' => $containerId]);
        $record = new ContentContainerModuleState(['module_id' => 'cfiles', 'contentcontainer_id' => $containerId, 'module_state' => $state]);
        $this->assertTrue($record->save());
    }

    private function setDefaultState(?int $state): void
    {
        $settings = Yii::$app->getModule('cfiles')->settings;
        if ($state === null) {
            $settings->delete('moduleManager.defaultState.Space');
        } else {
            $settings->set('moduleManager.defaultState.Space', $state);
        }
    }

    private function addFolder(string $title, int $spaceId, ?Folder $parent = null): Folder
    {
        $folder = (new FolderContentService(Space::findOne($spaceId), $parent))->newFolder($title, '');

        $this->assertTrue($folder->save(), implode(' ', $folder->getFirstErrors()));

        return $folder;
    }

    private function addFile(string $name, int $spaceId, ?Folder $parent = null): File
    {
        $path = Yii::getAlias('@runtime') . '/' . $name;
        file_put_contents($path, 'test');

        $file = (new FolderContentService(Space::findOne($spaceId), $parent))->addFileFromPath($name, $path);

        $this->assertFalse($file->hasErrors(), implode(' ', $file->getFirstErrors()));

        return $file;
    }
}
