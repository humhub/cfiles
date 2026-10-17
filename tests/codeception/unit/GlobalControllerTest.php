<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2026 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\cfiles\tests\codeception\unit;

use humhub\modules\cfiles\controllers\GlobalController;
use humhub\modules\cfiles\Events;
use humhub\modules\cfiles\models\ConfigureForm;
use humhub\modules\cfiles\Module;
use humhub\modules\cfiles\services\FolderContentService;
use humhub\modules\content\models\ContentContainerModuleState;
use humhub\modules\space\models\Space;
use humhub\modules\topic\models\Topic;
use humhub\widgets\menu\MenuLink;
use humhub\widgets\TopMenu;
use tests\codeception\_support\HumHubDbTestCase;
use Yii;
use yii\base\Event;
use yii\helpers\Url;
use yii\web\NotFoundHttpException;
use yii\web\Request;
use yii\web\UrlManager;

/**
 * The global files page `/files` ({@see GlobalController}) and its main navigation entry: both
 * exist only while the admin switched them on, and only for a logged-in user.
 */
class GlobalControllerTest extends HumHubDbTestCase
{
    /**
     * @var array the `$_SERVER` keys {@see open()} sets, as they were before the test
     */
    private array $server = [];

    public function _before()
    {
        parent::_before();
        Yii::$app->response->statusCode = 200;
        Yii::$app->response->headers->remove('Location');

        foreach (['REQUEST_METHOD', 'REQUEST_URI'] as $key) {
            $this->server[$key] = $_SERVER[$key] ?? null;
        }
    }

    public function _after()
    {
        foreach ($this->server as $key => $value) {
            if ($value === null) {
                unset($_SERVER[$key]);
            } else {
                $_SERVER[$key] = $value;
            }
        }
        Yii::$app->controller = null;

        parent::_after();
    }

    public function testThePageIsA404WhileSwitchedOff()
    {
        $this->becomeUser('User2');
        $this->switchOn(false);

        $this->expectException(NotFoundHttpException::class);
        $this->open();
    }

    public function testThePageRendersForAUserWhileSwitchedOn()
    {
        $this->becomeUser('User2');
        $this->switchOn(true);

        $html = $this->open();

        $this->assertSame(200, Yii::$app->response->statusCode);
        $this->assertStringContainsString('id="cfiles-global"', $html);
        $this->assertMatchesRegularExpression('#<cfiles-file-browser[^>]*id="cfiles-global"#', $html);
    }

    public function testThePageEmbedsTheTopLevelWithItsDefinitions()
    {
        $props = $this->props([]);

        $this->assertTrue($props['global']);
        $this->assertNull($props['space']);
        $this->assertNull($props['contentContainerId']);
        $this->assertSame(Url::to(['/cfiles/global/index']), $props['globalUrl']);
        $this->assertTrue($props['listing']['global']);
        $this->assertFalse($props['listing']['resultsMode']);
        $this->assertSame([1, 3], array_column($props['listing']['results'], 'id'));
        $this->assertSame(['q', 'spaceId', 'userId', 'topicId', 'type', 'modified', 'sort'], array_column($props['filters'], 'key'));
        // A space's level has no Space filter; its Topic is scoped by the island.
        $this->assertSame(['q', 'userId', 'topicId', 'type', 'modified', 'sort'], array_column($props['containerFilters'], 'key'));
        $this->assertSame(['q' => '', 'spaceId' => '', 'userId' => '', 'topicId' => '', 'type' => '', 'modified' => ''], $props['initialFilters']);
    }

    public function testAMemberSpaceOfTheUrlIsEmbedded()
    {
        $this->becomeUser('User2');
        $space = Space::findOne(3);
        $brand = (new FolderContentService($space))->newFolder('Brand', '');
        $this->assertTrue($brand->save());

        $props = $this->props(['space' => '3', 'spaceId' => '3']);

        $this->assertSame(3, $props['space']['id']);
        $this->assertSame('Space 3', $props['space']['name']);
        $this->assertSame((int)$space->contentcontainer_id, $props['contentContainerId']);
        $this->assertArrayNotHasKey('global', $props['listing']);
        $this->assertTrue($props['listing']['canWrite']);
        $this->assertSame(['Brand'], array_column($props['listing']['results'], 'title'));
        // The global page's own filter is not the space's, but kept for the way back.
        $this->assertSame('3', $props['initialFilters']['spaceId']);
        // For no space in particular: the island scopes it to the space it opens.
        $topic = array_column($props['containerFilters'], null, 'key')['topicId'];
        $this->assertArrayNotHasKey('containerId', $topic['props'] ?? []);
    }

    /**
     * Topics of several spaces: inside one, the page is filtered by those of that space (and the
     * global ones) — the space's list refuses the others —, and hands the island all of them,
     * for the way back.
     */
    public function testInsideASpaceTheTopicsOfOtherSpacesAreLeftOutButKept()
    {
        $this->becomeUser('User2');
        $space = Space::findOne(3);
        $own = new Topic(['name' => 'Budget', 'contentcontainer_id' => $space->contentcontainer_id]);
        $this->assertTrue($own->save());
        $foreign = new Topic(['name' => 'Elsewhere', 'contentcontainer_id' => Space::findOne(1)->contentcontainer_id]);
        $this->assertTrue($foreign->save());
        $tagged = (new FolderContentService($space))->newFolder('Tagged', '');
        $this->assertTrue($tagged->save());
        $this->assertTrue((new FolderContentService($space))->newFolder('Plain', '')->save());
        Topic::attach($tagged->content, [$own]);
        $value = $own->id . ',' . $foreign->id;

        $props = $this->props(['space' => '3', 'topicId' => $value]);

        $this->assertTrue($props['listing']['resultsMode']);
        $this->assertSame(['Tagged'], array_column($props['listing']['results'], 'title'));
        $this->assertSame($value, $props['initialFilters']['topicId']);

        // None of the space's: the level, unfiltered.
        $props = $this->props(['space' => '3', 'topicId' => (string)$foreign->id]);
        $this->assertFalse($props['listing']['resultsMode']);
        $this->assertSame((string)$foreign->id, $props['initialFilters']['topicId']);
    }

    public function testAFolderOfTheSpaceIsEmbedded()
    {
        $this->becomeUser('User2');
        $space = Space::findOne(3);
        $brand = (new FolderContentService($space))->newFolder('Brand', '');
        $this->assertTrue($brand->save());

        $props = $this->props(['space' => '3', 'fid' => (string)$brand->id]);

        $this->assertSame($brand->id, $props['listing']['folder']['id']);
    }

    public function testAFolderOfAnotherSpaceOpensTheSpacesTopLevel()
    {
        $this->becomeUser('User2');
        $foreign = (new FolderContentService(Space::findOne(1)))->newFolder('Elsewhere', '');
        $this->assertTrue($foreign->save());

        $props = $this->props(['space' => '3', 'fid' => (string)$foreign->id]);

        $this->assertSame(3, $props['space']['id']);
        $this->assertNull($props['listing']['folder']);
    }

    /**
     * User2 is no member of space 2; a space without the module, or one that does not exist,
     * is no way in either.
     */
    public function testASpaceOutsideTheScopeFallsBackToTheTopLevel()
    {
        foreach (['2', '4', '999', 'x'] as $id) {
            $props = $this->props(['space' => $id]);

            $this->assertNull($props['space'], 'space=' . $id);
            $this->assertTrue($props['listing']['global'], 'space=' . $id);
        }
    }

    public function testAValueTheListRefusesIsDropped()
    {
        $props = $this->props(['q' => 'logo', 'type' => 'archive', 'sort' => 'bogus']);

        $this->assertTrue($props['listing']['resultsMode']);
        $this->assertSame('default', $props['listing']['sort']);
        $this->assertSame('logo', $props['initialFilters']['q']);
        $this->assertSame('', $props['initialFilters']['type']);
    }

    public function testAValueTheListRefusesIsDroppedInsideASpace()
    {
        $props = $this->props(['space' => '3', 'q' => 'logo', 'type' => 'archive']);

        $this->assertTrue($props['listing']['resultsMode']);
        $this->assertSame(['q' => 'logo', 'spaceId' => '', 'userId' => '', 'topicId' => '', 'type' => '', 'modified' => ''], $props['initialFilters']);
    }

    /**
     * A space the page cannot open is dropped from the URL — with its folder — so the island
     * does not ask for it again; everything else the link carries stays.
     */
    public function testASpaceOutsideTheScopeRedirectsToTheTopLevel()
    {
        $this->becomeUser('User2');

        $response = $this->index(['space' => '2', 'fid' => '5', 'q' => 'logo', 'sort' => 'name']);

        $this->assertSame(302, $response->statusCode);
        $this->assertSame(Url::to(['/cfiles/global/index', 'q' => 'logo', 'sort' => 'name'], true), $response->headers->get('Location'));
    }

    public function testAFolderTheSpaceDoesNotHoldRedirectsToTheSpace()
    {
        $this->becomeUser('User2');
        $foreign = (new FolderContentService(Space::findOne(1)))->newFolder('Elsewhere', '');
        $this->assertTrue($foreign->save());

        $response = $this->index(['space' => '3', 'fid' => (string)$foreign->id, 'q' => 'logo']);

        $this->assertSame(302, $response->statusCode);
        $this->assertSame(Url::to(['/cfiles/global/index', 'space' => '3', 'q' => 'logo'], true), $response->headers->get('Location'));
    }

    public function testAFolderWithoutASpaceRedirectsToTheTopLevel()
    {
        $response = $this->index(['fid' => '5']);

        $this->assertSame(302, $response->statusCode);
        $this->assertSame(Url::to(['/cfiles/global/index'], true), $response->headers->get('Location'));
    }

    public function testALevelThePageCanOpenIsRendered()
    {
        $this->becomeUser('User2');
        $brand = (new FolderContentService(Space::findOne(3)))->newFolder('Brand', '');
        $this->assertTrue($brand->save());

        $this->assertIsString($this->index(['space' => '3', 'fid' => (string)$brand->id]));
        $this->assertSame(200, Yii::$app->response->statusCode);
    }

    public function testOnlyAnAdminGetsTheSettings()
    {
        $this->assertNull($this->props([])['settingsUrl']);

        $this->becomeUser('Admin');
        $this->assertSame($this->module()->getConfigUrl(), $this->props([])['settingsUrl']);
    }

    /**
     * Switched off, the page does not exist for a guest either — not a login redirect.
     */
    public function testThePageIsA404ForAGuestWhileSwitchedOff()
    {
        $this->logout();
        $this->switchOn(false);

        $this->expectException(NotFoundHttpException::class);
        $this->open();
    }

    public function testAGuestIsSentToTheLogin()
    {
        $this->logout();
        $this->switchOn(true);

        $this->assertNull($this->open());
        $this->assertSame(302, Yii::$app->response->statusCode);
        $this->assertStringContainsString('login', (string)Yii::$app->response->headers->get('Location'));
    }

    /**
     * The test app runs without pretty URLs, so the module's rules are tried on a manager of
     * their own.
     */
    public function testThePageHasThePrettyUrl()
    {
        $config = require dirname(__DIR__, 3) . '/config.php';
        $manager = new UrlManager([
            'enablePrettyUrl' => true,
            'showScriptName' => false,
            'baseUrl' => '',
            'rules' => $config['urlManagerRules'],
        ]);

        $this->assertSame('/files', $manager->createUrl(['/cfiles/global/index']));

        $request = new Request(['pathInfo' => 'files']);
        $this->assertSame(['cfiles/global/index', []], $manager->parseRequest($request));
    }

    public function testTheMenuEntryIsThereWhileSwitchedOn()
    {
        $this->becomeUser('User2');
        $this->switchOn(true);

        $entry = $this->menuEntry();

        $this->assertInstanceOf(MenuLink::class, $entry);
        $this->assertSame('Files', $entry->getLabel());
        $this->assertSame(Url::to(['/cfiles/global/index']), $entry->getUrl());
        // Not on the page, not active.
        $this->assertFalse($entry->getIsActive());
    }

    public function testTheMenuEntryIsActiveOnTheGlobalPage()
    {
        $this->becomeUser('User2');
        $this->switchOn(true);

        Yii::$app->controller = new GlobalController('global', $this->module());

        $this->assertTrue($this->menuEntry()->getIsActive());
    }

    public function testTheMenuEntryIsAbsentWhileSwitchedOff()
    {
        $this->becomeUser('User2');
        $this->switchOn(false);

        $this->assertNull($this->menuEntry());
    }

    public function testTheMenuEntryIsAbsentForAGuest()
    {
        $this->logout();
        $this->switchOn(true);

        $this->assertNull($this->menuEntry());
    }

    public function testTheFormLoadsAndSavesTheSwitch()
    {
        $this->assertFalse((bool)(new ConfigureForm())->showGlobalMenuItem);

        $form = new ConfigureForm();
        $this->assertTrue($form->load(['ConfigureForm' => ['showGlobalMenuItem' => '1']]));
        $this->assertTrue($form->save());

        $this->assertTrue($this->module()->getShowGlobalMenuItem());
        $this->assertTrue((bool)(new ConfigureForm())->showGlobalMenuItem);

        $form = new ConfigureForm();
        $form->load(['ConfigureForm' => ['showGlobalMenuItem' => '0']]);
        $this->assertTrue($form->save());
        $this->assertFalse($this->module()->getShowGlobalMenuItem());
    }

    /**
     * What the page hands the island, for User2 — a member of spaces 1, 3 and 4 — with the
     * module on in spaces 1, 2 and 3.
     */
    private function props(array $query): array
    {
        if (Yii::$app->user->isGuest) {
            $this->becomeUser('User2');
        }
        $this->switchOn(true);
        ContentContainerModuleState::deleteAll(['module_id' => 'cfiles']);
        foreach ([1, 2, 3] as $spaceId) {
            (new ContentContainerModuleState([
                'module_id' => 'cfiles',
                'contentcontainer_id' => Space::findOne($spaceId)->contentcontainer_id,
                'module_state' => ContentContainerModuleState::STATE_ENABLED,
            ]))->save();
        }
        Yii::$app->request->setQueryParams($query);

        return (new GlobalController('global', $this->module()))->pageProps();
    }

    /**
     * The page's action for the URL, as {@see props()} sets up the spaces.
     *
     * @return string|\yii\web\Response the page, or the redirect to its canonical URL
     */
    private function index(array $query)
    {
        $this->props([]);
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/files';
        Yii::$app->request->setQueryParams($query);

        return Yii::$app->runAction('cfiles/global/index', $query);
    }

    private function open(): ?string
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/files';
        Yii::$app->request->setQueryParams([]);

        return Yii::$app->runAction('cfiles/global/index');
    }

    /**
     * The entry the module's handler adds to a fresh main navigation — the handler is called on
     * a menu of its own, so the entry is the one it added, however the test app wired events.
     */
    private function menuEntry(): ?MenuLink
    {
        $menu = new TopMenu();
        foreach ($menu->getEntries() as $entry) {
            $menu->removeEntry($entry);
        }

        Events::onTopMenuInit(new Event(['sender' => $menu]));

        return $menu->getEntryById('cfiles-global');
    }

    private function switchOn(bool $on): void
    {
        $this->module()->settings->set('showGlobalMenuItem', $on);
    }

    private function module(): Module
    {
        return Yii::$app->getModule('cfiles');
    }
}
