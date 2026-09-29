<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2026 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\cfiles\tests\codeception\unit;

use humhub\components\listing\ListContext;
use humhub\components\listing\ListValidationException;
use humhub\modules\cfiles\components\FolderList;
use humhub\modules\cfiles\components\FolderListBuilder;
use humhub\modules\cfiles\models\File;
use humhub\modules\cfiles\models\Folder;
use humhub\modules\cfiles\Module;
use humhub\modules\cfiles\services\FolderContentService;
use humhub\modules\cfiles\services\FolderListingService;
use humhub\modules\content\models\Content;
use humhub\modules\file\models\File as BaseFile;
use humhub\modules\space\models\Space;
use humhub\modules\topic\models\Topic;
use tests\codeception\_support\HumHubDbTestCase;
use Yii;

/**
 * The items endpoint's list ({@see FolderList}): its parameters, the sort keys and the page's
 * filter definitions.
 */
class FolderListTest extends HumHubDbTestCase
{
    private Space $space;

    public function _before()
    {
        parent::_before();
        $this->becomeUser('Admin');
        $this->space = Space::findOne(1);
    }

    public function testAnUnknownParameterIsRefused()
    {
        try {
            $this->build(['view' => 'tiles']);
            $this->fail('An unknown parameter must be refused.');
        } catch (ListValidationException $e) {
            $this->assertArrayHasKey('view', $e->errors);
        }
    }

    public function testAnUnknownSortIsRefused()
    {
        try {
            $this->build(['sort' => '; DROP TABLE cfiles_file']);
            $this->fail('An unknown sort must be refused.');
        } catch (ListValidationException $e) {
            $this->assertArrayHasKey('sort', $e->errors);
        }
    }

    public function testNoParentIsTheTopLevel()
    {
        $folder = $this->addFolder('Top');
        $this->addFolder('Inside', $folder);

        $builder = $this->build([]);

        $this->assertNull($builder->folder());
        $this->assertSame(['Top'], array_column($builder->folderQuery()->all(), 'title'));
    }

    public function testParentNarrowsBothQueriesToThatFolder()
    {
        $folder = $this->addFolder('Parent');
        $this->addFolder('Child', $folder);
        $this->addFile('inside.txt', $folder);
        $this->addFile('outside.txt');

        $builder = $this->build(['parent' => (string)$folder->id]);

        $this->assertSame($folder->id, $builder->folder()->id);
        $this->assertSame(['Child'], array_column($builder->folderQuery()->all(), 'title'));
        $this->assertSame(['inside.txt'], array_map(static fn($file) => $file->baseFile->file_name, $builder->fileQuery()->all()));
    }

    public function testAParentOfAnotherContainerIsRefused()
    {
        $foreign = (new FolderContentService(Space::findOne(2)))->newFolder('Elsewhere', '');
        $this->assertTrue($foreign->save(), implode(' ', $foreign->getFirstErrors()));

        try {
            $this->build(['parent' => (string)$foreign->id]);
            $this->fail('A folder of another container must be refused.');
        } catch (ListValidationException $e) {
            $this->assertArrayHasKey('parent', $e->errors);
        }
    }

    public function testAnUnknownParentIsRefused()
    {
        foreach (['999999', 'abc'] as $parent) {
            try {
                $this->build(['parent' => $parent]);
                $this->fail('The parent "' . $parent . '" must be refused.');
            } catch (ListValidationException $e) {
                $this->assertArrayHasKey('parent', $e->errors);
            }
        }
    }

    public function testSortKeysOrderFoldersAndFiles()
    {
        $this->addFolder('A folder');
        $this->addFolder('B folder');
        $this->addFile('a.txt', null, 'x');
        $this->addFile('b.txt', null, 'xxxxxx');

        $descending = $this->build(['sort' => 'nameDesc']);
        $this->assertSame(['B folder', 'A folder'], $this->folderTitles($descending));
        $this->assertSame(['b.txt', 'a.txt'], $this->fileNames($descending));

        // Folders have no size: they keep their name order.
        $smallest = $this->build(['sort' => 'smallest']);
        $this->assertSame(['A folder', 'B folder'], $this->folderTitles($smallest));
        $this->assertSame(['a.txt', 'b.txt'], $this->fileNames($smallest));

        $largest = $this->build(['sort' => 'largest']);
        $this->assertSame(['A folder', 'B folder'], $this->folderTitles($largest));
        $this->assertSame(['b.txt', 'a.txt'], $this->fileNames($largest));

        $this->assertSame('largest', $largest->sort);
    }

    public function testAStoredSortAppliesWithoutASort()
    {
        $this->addFolder('A folder');
        $this->addFolder('B folder');

        $stored = $this->build([], 'nameDesc');
        $this->assertSame('nameDesc', $stored->sort);
        $this->assertSame(['B folder', 'A folder'], $this->folderTitles($stored));

        // `default` is the module's order, whatever is stored.
        $default = $this->build(['sort' => 'default'], 'nameDesc');
        $this->assertSame('default', $default->sort);
        $this->assertSame(['A folder', 'B folder'], $this->folderTitles($default));

        // A stored key the list does not know (any more) is no sort.
        $this->assertSame('default', $this->build([], 'downloadCount')->sort);
    }

    /**
     * Lists are side-effect free: remembering the sort is the browser endpoints' business
     * ({@see \humhub\modules\cfiles\services\BrowserPreferences}).
     */
    public function testBuildingChangesNoUserSetting()
    {
        $this->build(['sort' => 'newest']);
        $this->build(['sort' => 'default']);

        /** @var Module $module */
        $module = Yii::$app->getModule('cfiles');
        $settings = $module->settings->user(Yii::$app->user->getIdentity());

        $this->assertNull($settings->get('sort'));
        $this->assertNull($settings->get('defaultSort'));
    }

    public function testAnUnreadableParentIsRefused()
    {
        $folder = $this->addFolder('Trashed');
        Content::updateAll(['state' => Content::STATE_DELETED], ['id' => $folder->content->id]);

        try {
            $this->build(['parent' => (string)$folder->id]);
            $this->fail('An unreadable folder must be refused.');
        } catch (ListValidationException $e) {
            $this->assertArrayHasKey('parent', $e->errors);
        }
    }

    /**
     * Equal sort values end on the id, so pages neither repeat nor skip rows.
     */
    public function testPagesOfEqualRowsNeitherRepeatNorSkip()
    {
        foreach (['e.txt', 'b.txt', 'g.txt', 'a.txt', 'f.txt', 'c.txt', 'd.txt'] as $name) {
            $this->addFile($name);
        }
        // The same size (all "test") and the same time for every file.
        BaseFile::updateAll(['updated_at' => '2026-01-01 00:00:00'], ['object_model' => File::class]);
        Content::updateAll(['updated_at' => '2026-01-01 00:00:00', 'created_at' => '2026-01-01 00:00:00'], ['object_model' => File::class]);

        $builder = $this->build(['sort' => 'largest']);
        $this->assertSame(SORT_ASC, $builder->fileQuery()->orderBy['cfiles_file.id'] ?? null);
        $this->assertSame(SORT_ASC, $builder->folderQuery()->orderBy['cfiles_folder.id'] ?? null);

        foreach (['largest', 'newest'] as $sort) {
            $seen = [];
            for ($page = 1; $page <= 3; $page++) {
                $payload = (new FolderListingService($this->build(['sort' => $sort])))->payload($page, 3);
                $seen = array_merge($seen, array_column($payload['results'], 'id'));
            }
            $ids = array_map(static fn($file) => $file->id, $this->build(['sort' => $sort])->fileQuery()->all());

            $this->assertCount(7, $seen);
            $this->assertSame(array_values(array_unique($seen)), $seen);
            // In id order, the order they were added in.
            $sorted = $ids;
            sort($sorted);
            $this->assertSame($sorted, $ids);
        }
    }

    public function testDefinitionsOfferTheSortKeysWithoutTheDefault()
    {
        $definitions = $this->definitions();
        $sort = $definitions['sort'];

        $this->assertSame('select', $sort['type']);
        $this->assertSame('Sort by', $sort['label']);
        $this->assertSame(
            ['name', 'nameDesc', 'newest', 'oldest', 'largest', 'smallest'],
            array_column($sort['options'], 'value'),
        );
        $this->assertSame('Name (A–Z)', $sort['options'][0]['label']);
    }

    public function testDefinitionsComeInTheOrderOfTheBar()
    {
        $this->assertSame(['q', 'userId', 'topicId', 'type', 'modified', 'sort'], array_keys($this->definitions()));
    }

    public function testTheTopicOffersTheTopicsOfTheContainer()
    {
        $topic = $this->definitions()['topicId'];

        $this->assertSame('topic', $topic['type']);
        $this->assertSame('Topic', $topic['label']);
        $this->assertTrue($topic['multiple']);
        $this->assertSame((int)$this->space->contentcontainer_id, $topic['props']['containerId']);
    }

    public function testTopicNarrowsFoldersAndFilesInTheWholeContainer()
    {
        $topic = $this->addTopic('Budget', $this->space);
        $parent = $this->addFolder('Parent');
        $tagged = $this->addFolder('Tagged', $parent);
        $this->addFolder('Untagged');
        $file = $this->addFile('tagged.txt', $parent);
        $this->addFile('untagged.txt');
        Topic::attach($tagged->content, [$topic]);
        Topic::attach($file->content, [$topic]);

        $builder = $this->build(['topicId' => (string)$topic->id]);

        $this->assertTrue($builder->resultsMode);
        $this->assertSame(['Tagged'], $this->folderTitles($builder));
        $this->assertSame(['tagged.txt'], $this->fileNames($builder));
    }

    public function testATopicOfAnotherSpaceIsRefused()
    {
        $foreign = $this->addTopic('Elsewhere', Space::findOne(2));

        try {
            $this->build(['topicId' => (string)$foreign->id]);
            $this->fail('A topic of another space must be refused.');
        } catch (ListValidationException $e) {
            $this->assertArrayHasKey('topicId', $e->errors);
        }
    }

    public function testAGlobalTopicIsAccepted()
    {
        $global = $this->addTopic('Everywhere', null);
        $file = $this->addFile('global.txt');
        Topic::attach($file->content, [$global]);

        $builder = $this->build(['topicId' => [(string)$global->id]]);

        $this->assertSame(['global.txt'], $this->fileNames($builder));
    }

    public function testDefinitionsOfTheFilters()
    {
        $definitions = $this->definitions();

        $this->assertSame('text', $definitions['q']['type']);
        $this->assertSame('Search', $definitions['q']['label']);
        $this->assertSame('Search', $definitions['q']['placeholder']);

        $this->assertSame('user', $definitions['userId']['type']);
        $this->assertSame('Author', $definitions['userId']['label']);

        $this->assertSame('select', $definitions['type']['type']);
        $this->assertSame('File Type', $definitions['type']['label']);
        $this->assertSame('File Type', $definitions['type']['placeholder']);
        $this->assertSame(
            ['image' => 'Images', 'document' => 'Documents', 'spreadsheet' => 'Spreadsheets', 'presentation' => 'Presentations', 'media' => 'Audio & Video'],
            array_column($definitions['type']['options'], 'label', 'value'),
        );

        $this->assertSame('select', $definitions['modified']['type']);
        $this->assertSame('Modified', $definitions['modified']['label']);
        $this->assertSame('Modified', $definitions['modified']['placeholder']);
        $this->assertSame(
            ['7d' => 'Last 7 days', '30d' => 'Last 30 days', '12m' => 'Last 12 months', 'older' => 'Older'],
            array_column($definitions['modified']['options'], 'label', 'value'),
        );

        foreach ($definitions as $definition) {
            $this->assertSame('primary', $definition['placement']);
        }
    }

    public function testInvalidFilterValuesAreRefused()
    {
        foreach ([['type' => 'archive'], ['modified' => '5y'], ['q' => ['a']], ['userId' => 'abc'], ['userId' => '999999'], ['userId' => '5']] as $params) {
            try {
                $this->build($params);
                $this->fail('The value of "' . key($params) . '" must be refused.');
            } catch (ListValidationException $e) {
                $this->assertArrayHasKey(key($params), $e->errors);
            }
        }
    }

    public function testSearchMatchesTitlesNamesAndDescriptions()
    {
        $this->addFolder('Brand assets');
        $described = $this->addFolder('Other');
        Folder::updateAll(['description' => 'the brand book'], ['id' => $described->id]);
        $this->addFolder('Unrelated');
        $this->addFile('brand-logo.png');
        $this->addFile('notes.txt', null, 'test', 'about the brand');
        $this->addFile('invoice.pdf');

        $builder = $this->build(['q' => 'brand']);

        $this->assertSame(['Brand assets', 'Other'], $this->folderTitles($builder));
        $this->assertSame(['brand-logo.png', 'notes.txt'], $this->fileNames($builder));
    }

    public function testTypeKeepsTheFilesOfItsGroupAndNoFolders()
    {
        $this->addFolder('Photos');
        foreach (['a.jpg', 'B.PNG', 'c.pdf', 'd.xlsx', 'e.pptx', 'f.mp4', 'g.mp3', 'h.zip', 'jpg'] as $name) {
            $this->addFile($name);
        }

        $this->assertSame([], $this->folderTitles($this->build(['type' => 'image'])));
        $this->assertSame(['a.jpg', 'B.PNG'], $this->fileNames($this->build(['type' => 'image'])));
        $this->assertSame(['c.pdf'], $this->fileNames($this->build(['type' => 'document'])));
        $this->assertSame(['d.xlsx'], $this->fileNames($this->build(['type' => 'spreadsheet'])));
        $this->assertSame(['e.pptx'], $this->fileNames($this->build(['type' => 'presentation'])));
        $this->assertSame(['f.mp4', 'g.mp3'], $this->fileNames($this->build(['type' => 'media'])));
    }

    public function testModifiedNarrowsBothByTheLastChange()
    {
        $recent = $this->addFolder('Recent');
        $old = $this->addFolder('Old');
        $this->touch($recent, date('Y-m-d H:i:s', strtotime('-2 days')));
        $this->touch($old, date('Y-m-d H:i:s', strtotime('-2 years')));

        $files = [
            'week.txt' => '-6 days',
            'month.txt' => '-20 days',
            'year.txt' => '-200 days',
            'ancient.txt' => '-13 months',
        ];
        foreach ($files as $name => $offset) {
            $this->touch($this->addFile($name), date('Y-m-d H:i:s', strtotime($offset)));
        }

        $this->assertSame(['Recent'], $this->folderTitles($this->build(['modified' => '7d'])));
        $this->assertSame(['week.txt'], $this->fileNames($this->build(['modified' => '7d'])));
        $this->assertSame(['month.txt', 'week.txt'], $this->fileNames($this->build(['modified' => '30d', 'sort' => 'name'])));
        $this->assertSame(['month.txt', 'week.txt', 'year.txt'], $this->fileNames($this->build(['modified' => '12m', 'sort' => 'name'])));
        $this->assertSame(['Old'], $this->folderTitles($this->build(['modified' => 'older'])));
        $this->assertSame(['ancient.txt'], $this->fileNames($this->build(['modified' => 'older'])));
    }

    public function testWithoutAFilterTheLevelIsListedAsBefore()
    {
        $brand = $this->addFolder('Brand');
        $this->addFile('deep.txt', $brand);
        $this->addFile('top.txt');

        $builder = $this->build([]);

        $this->assertFalse($builder->resultsMode);
        $this->assertSame(['top.txt'], $this->fileNames($builder));
    }

    public function testAFilterSearchesTheOpenFolderAndAllItsSubfolders()
    {
        $brand = $this->addFolder('Brand');
        $logos = $this->addFolder('Logos', $brand);
        $old = $this->addFolder('Old logos', $logos);
        $other = $this->addFolder('Other');
        $this->addFile('logo-top.png');
        $this->addFile('logo-brand.png', $brand);
        $this->addFile('logo-deep.png', $logos);
        $this->addFile('logo-deeper.png', $old);
        $this->addFile('logo-other.png', $other);

        $inBrand = $this->build(['parent' => (string)$brand->id, 'q' => 'logo', 'sort' => 'name']);
        $this->assertTrue($inBrand->resultsMode);
        $this->assertSame(['Logos', 'Old logos'], $this->folderTitles($inBrand));
        $this->assertSame(['logo-brand.png', 'logo-deep.png', 'logo-deeper.png'], $this->fileNames($inBrand));

        // The top level searches everything.
        $everywhere = $this->build(['q' => 'logo', 'sort' => 'name']);
        $this->assertSame(
            ['logo-brand.png', 'logo-deep.png', 'logo-deeper.png', 'logo-other.png', 'logo-top.png'],
            $this->fileNames($everywhere),
        );
    }

    /**
     * What a row shows as its last change (the content's) is what "newest" sorts by and what
     * `modified` filters by — not the stored file's own timestamp.
     */
    public function testNewestSortsByTheLastChangeTheRowsShow()
    {
        $older = $this->addFile('older.txt');
        $newer = $this->addFile('newer.txt');
        $this->touch($older, '2026-01-02 00:00:00');
        $this->touch($newer, '2026-01-03 00:00:00');
        // The stored files' own times say the opposite.
        BaseFile::updateAll(['updated_at' => '2026-02-01 00:00:00'], ['id' => $older->baseFile->id]);
        BaseFile::updateAll(['updated_at' => '2026-01-01 00:00:00'], ['id' => $newer->baseFile->id]);

        $this->assertSame(['newer.txt', 'older.txt'], $this->fileNames($this->build(['sort' => 'newest'])));
        $this->assertSame(['older.txt', 'newer.txt'], $this->fileNames($this->build(['sort' => 'oldest'])));
    }

    public function testAPrivateFolderCutsOffItsSubtreeForANonMember()
    {
        $brand = $this->addFolder('Brand');
        $hidden = $this->addFolder('Hidden', $brand);
        $inside = $this->addFolder('Inside', $hidden);
        $files = [
            $this->addFile('logo-visible.png', $brand),
            $this->addFile('logo-hidden.png', $hidden),
            $this->addFile('logo-inside.png', $inside),
        ];
        // Everything public but the one folder in between.
        foreach ([$brand, $inside, ...$files] as $item) {
            Content::updateAll(['visibility' => Content::VISIBILITY_PUBLIC], ['id' => $item->content->id]);
        }
        Content::updateAll(['visibility' => Content::VISIBILITY_PRIVATE], ['id' => $hidden->content->id]);

        // User1 is no member of the space: public content only.
        $this->becomeUser('User1');

        $this->assertSame(['logo-visible.png'], $this->fileNames($this->build(['q' => 'logo'])));
        $this->assertSame([], $this->folderTitles($this->build(['q' => 'Inside'])));

        // A member sees the whole subtree.
        $this->becomeUser('Admin');
        $this->assertSame(['logo-hidden.png', 'logo-inside.png', 'logo-visible.png'], $this->fileNames($this->build(['q' => 'logo'])));
    }

    public function testAuthorNarrowsFoldersAndFilesToTheirCreator()
    {
        $brand = $this->addFolder('Brand');
        $theirs = $this->addFolder('Theirs', $brand);
        $this->addFolder('Mine');
        $theirFile = $this->addFile('theirs.txt', $brand);
        $this->addFile('mine.txt', $brand);
        // Created by User2 (id 3), a member of the space.
        foreach ([$theirs, $theirFile] as $item) {
            Content::updateAll(['created_by' => 3], ['id' => $item->content->id]);
        }

        $builder = $this->build(['userId' => '3']);

        $this->assertTrue($builder->resultsMode);
        $this->assertSame(['Theirs'], $this->folderTitles($builder));
        $this->assertSame(['theirs.txt'], $this->fileNames($builder));
        $this->assertSame(['Brand', 'Mine'], $this->folderTitles($this->build(['userId' => '1', 'sort' => 'name'])));
    }

    /**
     * The users a guest could pick from are searched in an endpoint without guest access: no
     * Author for guests, and a guest's value is refused.
     */
    public function testAuthorIsNotAvailableToGuests()
    {
        $this->logout();

        $this->assertArrayNotHasKey('userId', $this->definitions());
        try {
            $this->build(['userId' => '1']);
            $this->fail('A guest\'s author must be refused.');
        } catch (ListValidationException $e) {
            $this->assertArrayHasKey('userId', $e->errors);
        }
    }

    public function testAnUnreadableFolderCutsOffItsWholeSubtree()
    {
        $brand = $this->addFolder('Brand');
        $hidden = $this->addFolder('Hidden', $brand);
        $inside = $this->addFolder('Inside', $hidden);
        $this->addFile('logo-visible.png', $brand);
        $this->addFile('logo-hidden.png', $hidden);
        $this->addFile('logo-inside.png', $inside);
        Content::updateAll(['state' => Content::STATE_DELETED], ['id' => $hidden->content->id]);

        $builder = $this->build(['q' => 'logo']);

        $this->assertSame(['logo-visible.png'], $this->fileNames($builder));
        $this->assertSame([], $this->folderTitles($this->build(['q' => 'Inside'])));
    }

    public function testThePathOfAHitRunsFromTheOpenFolderToItsParent()
    {
        $brand = $this->addFolder('Brand');
        $logos = $this->addFolder('Logos', $brand);
        $old = $this->addFolder('Old', $logos);

        $inBrand = $this->build(['parent' => (string)$brand->id, 'q' => 'x']);
        $this->assertSame([], $inBrand->pathOf($brand->id));
        $this->assertSame([['type' => 'folder', 'id' => $logos->id, 'title' => 'Logos']], $inBrand->pathOf($logos->id));
        $this->assertSame(
            [['type' => 'folder', 'id' => $logos->id, 'title' => 'Logos'], ['type' => 'folder', 'id' => $old->id, 'title' => 'Old']],
            $inBrand->pathOf($old->id),
        );

        $top = $this->build(['q' => 'x']);
        $this->assertSame([], $top->pathOf(null));
        $this->assertSame([['type' => 'folder', 'id' => $brand->id, 'title' => 'Brand']], $top->pathOf($brand->id));
    }

    public function testTheBuilderOfOneContainerNamesIt()
    {
        $this->assertSame($this->space, $this->build([])->container());
        $this->assertSame([$this->space], $this->build([])->containers);
    }

    public function testABuilderOfSeveralContainersHasNoSingleContainer()
    {
        $builder = $this->builderOver([$this->space, Space::findOne(3)]);

        $this->expectException(\LogicException::class);
        $builder->container();
    }

    public function testABuilderNeedsAContainer()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->builderOver([]);
    }

    public function testASubtreeOverSeveralContainersHoldsTheItemsOfEach()
    {
        $other = Space::findOne(3);
        $outside = Space::findOne(4);
        $this->addFolder('Brand');
        $this->addFile('top-1.txt');
        $design = $this->addFolder('Design', null, $other);
        $this->addFile('deep-3.txt', $design, space: $other);
        $this->addFolder('Elsewhere', null, $outside);
        $this->addFile('elsewhere.txt', null, space: $outside);

        $builder = $this->builderOver([$this->space, $other]);
        $builder->scopeToSubtree();
        $builder->folderQuery()->orderBy(['cfiles_folder.title' => SORT_ASC]);
        $builder->fileQuery()->orderBy(['file.file_name' => SORT_ASC]);

        $this->assertTrue($builder->resultsMode);
        $this->assertSame(['Brand', 'Design'], $this->folderTitles($builder));
        $this->assertSame(['deep-3.txt', 'top-1.txt'], $this->fileNames($builder));
    }

    public function testAnUnreadableFolderCutsOffItsSubtreeInItsOwnContainerOnly()
    {
        $other = Space::findOne(3);
        $hidden = $this->addFolder('Hidden');
        $this->addFile('logo-hidden.png', $hidden);
        $shown = $this->addFolder('Hidden', null, $other);
        $this->addFile('logo-shown.png', $shown, space: $other);
        Content::updateAll(['state' => Content::STATE_DELETED], ['id' => $hidden->content->id]);

        $builder = $this->builderOver([$this->space, $other]);
        $builder->scopeToSubtree();

        $this->assertSame(['logo-shown.png'], $this->fileNames($builder));
        $this->assertSame([$shown->id], array_column($builder->folderQuery()->all(), 'id'));
    }

    /**
     * User1 is a member of space 3, not of space 1: a private folder of space 1 is cut off with
     * its subtree, a private folder of their own space 3 is not.
     */
    public function testAPrivateFolderOfAForeignSpaceIsCutOffForAMemberOfTheOther()
    {
        $other = Space::findOne(3);
        $foreign = $this->addFolder('Private', null);
        $foreignFile = $this->addFile('logo-foreign.png', $foreign);
        $own = $this->addFolder('Private', null, $other);
        $ownFile = $this->addFile('logo-own.png', $own, space: $other);
        foreach ([$foreign, $own] as $folder) {
            Content::updateAll(['visibility' => Content::VISIBILITY_PRIVATE], ['id' => $folder->content->id]);
        }
        // The file inside would be readable on its own: it is cut off by its folder.
        foreach ([$foreignFile, $ownFile] as $file) {
            Content::updateAll(['visibility' => Content::VISIBILITY_PUBLIC], ['id' => $file->content->id]);
        }

        $this->becomeUser('User1');
        $builder = $this->builderOver([$this->space, $other]);
        $builder->scopeToSubtree();

        $this->assertSame([$own->id], array_column($builder->folderQuery()->all(), 'id'));
        $this->assertSame(['logo-own.png'], $this->fileNames($builder));
    }

    public function testWithThePrefixAnUnknownContainerIsRefused()
    {
        $builder = $this->builderOver([$this->space, Space::findOne(3)]);
        $builder->prefixContainer = true;
        $builder->scopeToSubtree();

        $this->expectException(\LogicException::class);
        $builder->pathOf(null, Space::findOne(4)->contentcontainer_id);
    }

    public function testWithoutThePrefixTheContainerIdIsIgnored()
    {
        $builder = $this->builderOver([$this->space, Space::findOne(3)]);
        $builder->scopeToSubtree();

        $this->assertSame([], $builder->pathOf(null, 99999));
        $this->assertSame([], $builder->pathOf(null));
    }

    public function testWithThePrefixAPathStartsWithTheSpaceOfTheItem()
    {
        $other = Space::findOne(3);
        $brand = $this->addFolder('Brand', null, $other);
        $logos = $this->addFolder('Logos', $brand, $other);
        $top = $this->addFolder('Top');

        $builder = $this->builderOver([$this->space, $other]);
        $builder->prefixContainer = true;
        $builder->scopeToSubtree();

        $space3 = ['type' => 'space', 'id' => $other->id, 'contentContainerId' => $other->contentcontainer_id, 'guid' => $other->guid, 'title' => $other->name];
        $space1 = ['type' => 'space', 'id' => $this->space->id, 'contentContainerId' => $this->space->contentcontainer_id, 'guid' => $this->space->guid, 'title' => $this->space->name];
        $this->assertSame([$space3], $builder->pathOf(null, $other->contentcontainer_id));
        $this->assertSame([$space1], $builder->pathOf(null, $this->space->contentcontainer_id));
        $this->assertSame(
            [$space3, ['type' => 'folder', 'id' => $brand->id, 'title' => 'Brand'], ['type' => 'folder', 'id' => $logos->id, 'title' => 'Logos']],
            $builder->pathOf($logos->id, $other->contentcontainer_id),
        );
        $this->assertSame(
            [$space1, ['type' => 'folder', 'id' => $top->id, 'title' => 'Top']],
            $builder->pathOf($top->id, $this->space->contentcontainer_id),
        );
    }

    public function testWithoutThePrefixAPathHasNoSpace()
    {
        $other = Space::findOne(3);
        $brand = $this->addFolder('Brand', null, $other);

        $builder = $this->builderOver([$this->space, $other]);
        $builder->scopeToSubtree();

        $this->assertSame([], $builder->pathOf(null, $other->contentcontainer_id));
        $this->assertSame(
            [['type' => 'folder', 'id' => $brand->id, 'title' => 'Brand']],
            $builder->pathOf($brand->id, $other->contentcontainer_id),
        );
    }

    /**
     * The filters that switch the list into results mode are one list, which a further
     * filter (the author) joins.
     */
    public function testEachFilterOfTheBarSwitchesToResultsMode()
    {
        $this->assertSame(['q', 'userId', 'topicId', 'type', 'modified'], FolderList::RESULT_FILTERS);

        $topic = $this->addTopic('Budget', $this->space);
        foreach (['q' => 'x', 'userId' => '1', 'type' => 'image', 'modified' => '7d', 'topicId' => (string)$topic->id] as $key => $value) {
            $this->assertTrue($this->build([$key => $value])->resultsMode, $key);
        }
        $this->assertFalse($this->build(['sort' => 'name'])->resultsMode);
    }

    /**
     * @return array<string, array> the page's definitions by key, in their order
     */
    private function definitions(): array
    {
        $definitions = (new FolderList())->definitions(ListContext::forCurrentUser(null, $this->space));

        return array_column($definitions, null, 'key');
    }

    private function build(array $params, ?string $storedSort = null): FolderListBuilder
    {
        return (new FolderList(['storedSort' => $storedSort]))->build($params, ListContext::forCurrentUser(null, $this->space));
    }

    private function builderOver(array $containers): FolderListBuilder
    {
        return new FolderListBuilder($containers, ListContext::forCurrentUser());
    }

    private function folderTitles(FolderListBuilder $builder): array
    {
        return array_column($builder->folderQuery()->all(), 'title');
    }

    private function fileNames(FolderListBuilder $builder): array
    {
        return array_map(static fn($file) => $file->baseFile->file_name, $builder->fileQuery()->all());
    }

    private function addTopic(string $name, ?Space $space): Topic
    {
        $topic = new Topic(['name' => $name, 'contentcontainer_id' => $space?->contentcontainer_id]);
        $this->assertTrue($topic->save(), implode(' ', $topic->getFirstErrors()));

        return $topic;
    }

    private function addFolder(string $title, ?Folder $parent = null, ?Space $space = null): Folder
    {
        $folder = (new FolderContentService($space ?? $this->space, $parent))->newFolder($title, '');

        $this->assertTrue($folder->save(), implode(' ', $folder->getFirstErrors()));

        return $folder;
    }

    private function addFile(string $name, ?Folder $parent = null, string $content = 'test', string $description = '', ?Space $space = null): File
    {
        $path = Yii::getAlias('@runtime') . '/' . $name;
        file_put_contents($path, $content);

        $service = new FolderContentService($space ?? $this->space, $parent);
        $file = $service->addFileFromPath($name, $path);

        $this->assertFalse($file->hasErrors(), implode(' ', $file->getFirstErrors()));

        if ($description !== '') {
            File::updateAll(['description' => $description], ['id' => $file->id]);
        }

        return $file;
    }

    private function touch(Folder|File $item, string $updatedAt): void
    {
        Content::updateAll(['updated_at' => $updatedAt], ['id' => $item->content->id]);
    }
}
