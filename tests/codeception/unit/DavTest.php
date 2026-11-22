<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2026 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\cfiles\tests\codeception\unit;

use humhub\modules\cfiles\models\ConfigureForm;
use humhub\modules\cfiles\models\File;
use humhub\modules\cfiles\models\Folder;
use humhub\modules\cfiles\Module;
use humhub\modules\cfiles\services\FolderContentService;
use humhub\modules\content\models\Content;
use humhub\modules\dav\tests\codeception\_support\DavTestClient;
use humhub\modules\oauth\models\Grant;
use humhub\modules\oauth\Module as OAuthModule;
use humhub\modules\space\models\Space;
use humhub\modules\user\models\User;
use tests\codeception\_support\HumHubDbTestCase;
use Yii;
use yii\web\Response;

/**
 * The files under `/dav/files/` (see `components/dav`): the spaces of the user by their URL
 * and their own profile, each level as the file browser shows it, changed by its rules.
 */
class DavTest extends HumHubDbTestCase
{
    private Space $space;

    public function _before()
    {
        parent::_before();
        $this->becomeUser('Admin');

        $this->space = Space::findOne(1);
        if (!$this->space->moduleManager->isEnabled('cfiles')) {
            $this->space->moduleManager->enable('cfiles');
        }
    }

    public function _after()
    {
        OAuthModule::instance()->activeGrant = null;
        Grant::deleteAll();
        Yii::$app->getModule('cfiles')->settings->delete('enableWebDav');
        parent::_after();
    }

    private function dav(string $user = 'Admin', array $scopes = [Module::SCOPE_READ, Module::SCOPE_WRITE]): DavTestClient
    {
        return DavTestClient::forUser(User::findOne(['username' => $user]), $scopes);
    }

    private function file(string $name, string $content = 'test', ?Folder $folder = null): File
    {
        $path = Yii::getAlias('@runtime') . '/' . uniqid() . '-' . $name;
        file_put_contents($path, $content);
        $file = (new FolderContentService($this->space, $folder))->addFileFromPath($name, $path);
        unlink($path);
        $this->assertFalse($file->hasErrors(), implode(' ', $file->getFirstErrors()));

        return $file;
    }

    private function folder(string $title, ?Folder $parent = null, ?int $visibility = null): Folder
    {
        $folder = (new FolderContentService($this->space, $parent))->newFolder($title);
        if ($visibility !== null) {
            $folder->visibility = $visibility;
        }
        $this->assertTrue($folder->save(), implode(' ', $folder->getFirstErrors()));

        return $folder;
    }

    private function hrefs(Response $response): array
    {
        preg_match_all('#<d:href>([^<]+)</d:href>#', DavTestClient::body($response), $matches);

        return array_map('rawurldecode', $matches[1]);
    }

    public function testTheRootHoldsTheSpacesOfTheUserByTheirUrlAndTheirOwnProfile()
    {
        $admin = User::findOne(['username' => 'Admin']);
        $admin->moduleManager->enable('cfiles');

        $response = $this->dav()->request('PROPFIND', 'files/', ['Depth' => '1']);

        $this->assertSame(207, $response->statusCode);
        $hrefs = $this->hrefs($response);
        $this->assertContains('/dav/files/space-1/', $hrefs);
        $this->assertContains('/dav/files/~' . $admin->username . '/', $hrefs);
        // A space of others, which has the module too.
        Space::findOne(2)->moduleManager->enable('cfiles');
        $this->assertNotContains('/dav/files/space-2/', $this->hrefs($this->dav()->request('PROPFIND', 'files/', ['Depth' => '1'])));
        $this->assertSame(404, $this->dav()->request('PROPFIND', 'files/space-2/', ['Depth' => '0'])->statusCode);
    }

    public function testWebDavIsOnUntilAnAdministratorSwitchesItOff()
    {
        $this->assertTrue(Yii::$app->getModule('cfiles')->getEnableWebDav());
        $this->assertTrue((bool)(new ConfigureForm())->enableWebDav);

        $form = new ConfigureForm();
        $this->assertTrue($form->load(['ConfigureForm' => ['enableWebDav' => '0']]));
        $this->assertTrue($form->save());

        $this->assertFalse(Yii::$app->getModule('cfiles')->getEnableWebDav());
        $this->assertFalse((bool)(new ConfigureForm())->enableWebDav);
    }

    public function testSwitchedOffTheFilesAreNotServed()
    {
        Yii::$app->getModule('cfiles')->settings->set('enableWebDav', false);

        $this->assertNotContains('/dav/files/', $this->hrefs($this->dav()->request('PROPFIND', '', ['Depth' => '1'])));
        $this->assertSame(404, $this->dav()->request('PROPFIND', 'files/space-1/', ['Depth' => '0'])->statusCode);
    }

    public function testALevelListsItsFoldersAndFiles()
    {
        $folder = $this->folder('Reports');
        $this->file('plan.txt', 'the plan');
        $this->file('inside.txt', 'x', $folder);

        $hrefs = $this->hrefs($this->dav()->request('PROPFIND', 'files/space-1/', ['Depth' => '1']));

        $this->assertContains('/dav/files/space-1/Reports/', $hrefs);
        $this->assertContains('/dav/files/space-1/plan.txt', $hrefs);
        $this->assertNotContains('/dav/files/space-1/inside.txt', $hrefs);
    }

    public function testAFileIsServedWithItsHashAsETag()
    {
        $file = $this->file('plan.txt', 'the plan');
        $file->baseFile->setStoredFileContent('the plan', true);

        $response = $this->dav()->request('GET', 'files/space-1/plan.txt');

        $this->assertSame(200, $response->statusCode);
        $this->assertSame('the plan', DavTestClient::body($response));
        $this->assertSame('"' . sha1('the plan') . '"', $response->headers->get('ETag'));
        $this->assertSame('text/plain', $response->headers->get('Content-Type'));
        $this->assertSame((int)$file->baseFile->size, (int)$response->headers->get('Content-Length'));
    }

    public function testAPutAddsAFileWithTheVisibilityOfItsFolder()
    {
        $this->folder('Private', null, Content::VISIBILITY_PRIVATE);

        $response = $this->dav()->request('PUT', 'files/space-1/Private/notes.txt', [], 'my notes');

        $this->assertSame(201, $response->statusCode);
        $this->assertSame('"' . sha1('my notes') . '"', $response->headers->get('ETag'));
        $file = (new FolderContentService($this->space, Folder::findOne(['title' => 'Private'])))->findFile('notes.txt');
        $this->assertNotNull($file);
        $this->assertSame('my notes', $file->baseFile->store->getContent());
        $this->assertSame(8, (int)$file->baseFile->size);
        $this->assertSame(Content::VISIBILITY_PRIVATE, (int)$file->content->visibility);
        $this->assertSame((int)Yii::$app->user->id, (int)$file->content->created_by);
    }

    public function testAFileStoredWithoutAHashHasAnETagAllTheSame()
    {
        $this->file('plan.txt', 'the plan');

        $etag = $this->dav()->request('GET', 'files/space-1/plan.txt')->headers->get('ETag');

        $this->assertMatchesRegularExpression('/^"[0-9a-f]{40}"$/', (string)$etag);
    }

    public function testAPutOverAFileIsANewVersionOfIt()
    {
        $file = $this->file('plan.txt', 'version 1');

        $response = $this->dav()->request('PUT', 'files/space-1/plan.txt', [], 'version 2');

        $this->assertSame(204, $response->statusCode);
        $this->assertSame('"' . sha1('version 2') . '"', $response->headers->get('ETag'));
        $file = File::findOne($file->id);
        $this->assertSame('version 2', $file->baseFile->store->getContent());
        $this->assertCount(1, $file->baseFile->historyFiles);
        $this->assertCount(1, File::find()->contentContainer($this->space)->all());
    }

    public function testOnlyWhoMayChangeAFileWritesANewVersion()
    {
        $this->file('plan.txt', 'by the admin');

        // A member who may add files, but not change those of others.
        $response = $this->dav('User2')->request('PUT', 'files/space-1/plan.txt', [], 'by the member');

        $this->assertSame(403, $response->statusCode);
        $this->assertSame('by the admin', (new FolderContentService($this->space))->findFile('plan.txt')->baseFile->store->getContent());
    }

    public function testAFileAndAFolderNeverShareAName()
    {
        $this->folder('Reports');
        $this->file('plan.txt');

        $this->assertSame(409, $this->dav()->request('PUT', 'files/space-1/Reports', [], 'x')->statusCode);
        $this->assertSame(405, $this->dav()->request('MKCOL', 'files/space-1/plan.txt')->statusCode);
    }

    public function testMkcolAddsAFolder()
    {
        $this->assertSame(201, $this->dav()->request('MKCOL', 'files/space-1/Reports')->statusCode);

        $this->assertNotNull((new FolderContentService($this->space))->findFolder('Reports'));
    }

    public function testANameThePlatformWouldChangeIsRefused()
    {
        $this->assertSame(403, $this->dav()->request('PUT', 'files/space-1/a*b.txt', [], 'x')->statusCode);
        $this->assertNull((new FolderContentService($this->space))->findFile('a_b.txt'));
    }

    public function testAFileTypeThePlatformDoesNotAllowIsRefused()
    {
        Yii::$app->getModule('file')->settings->set('allowedExtensions', 'txt');

        $this->assertSame(403, $this->dav()->request('PUT', 'files/space-1/run.exe', [], 'MZ')->statusCode);
        $this->assertSame(201, $this->dav()->request('PUT', 'files/space-1/notes.txt', [], 'notes')->statusCode);
        $this->assertNull((new FolderContentService($this->space))->findFile('run.exe'));
    }

    public function testAnEmptyFileOfAnAllowedTypeIsAccepted()
    {
        // Finder and Office create a file empty before they write it.
        Yii::$app->getModule('file')->settings->set('allowedExtensions', 'docx');

        $this->assertSame(201, $this->dav()->request('PUT', 'files/space-1/report.docx', [], '')->statusCode);
        $this->assertSame(403, $this->dav()->request('PUT', 'files/space-1/report.exe', [], '')->statusCode);
    }

    public function testAMoveIntoAnotherContainerIsACopyAndTheOriginalGoesToTheTrash()
    {
        $admin = User::findOne(['username' => 'Admin']);
        $admin->moduleManager->enable('cfiles');
        $file = $this->file('plan.txt', 'the plan');

        $response = $this->dav()->request('MOVE', 'files/space-1/plan.txt', ['Destination' => '/dav/files/~' . $admin->username . '/plan.txt']);

        $this->assertSame(201, $response->statusCode);
        $this->assertSame(Content::STATE_DELETED, (int)File::findOne($file->id)->content->state);
        $moved = (new FolderContentService($admin))->findFile('plan.txt');
        $this->assertNotNull($moved);
        $this->assertSame('the plan', $moved->baseFile->store->getContent());
    }

    public function testAMoveKeepsTheItemAndTakesTheNameOfItsDestination()
    {
        $folder = $this->folder('Reports');
        $file = $this->file('plan.txt');

        $response = $this->dav()->request('MOVE', 'files/space-1/plan.txt', ['Destination' => '/dav/files/space-1/Reports/plan-2026.txt']);

        $this->assertSame(201, $response->statusCode);
        $file = File::findOne($file->id);
        $this->assertSame((int)$folder->id, (int)$file->parent_folder_id);
        $this->assertSame('plan-2026.txt', $file->getTitle());
    }

    public function testAMoveInTheSameFolderRenames()
    {
        $file = $this->file('plan.txt');
        $folder = $this->folder('Reports');

        $this->assertSame(201, $this->dav()->request('MOVE', 'files/space-1/plan.txt', ['Destination' => '/dav/files/space-1/final.txt'])->statusCode);
        $this->assertSame(201, $this->dav()->request('MOVE', 'files/space-1/Reports', ['Destination' => '/dav/files/space-1/Archive'])->statusCode);

        $this->assertSame('final.txt', File::findOne($file->id)->getTitle());
        $this->assertSame('Archive', Folder::findOne($folder->id)->title);
    }

    public function testAFolderCannotMoveIntoItself()
    {
        $folder = $this->folder('Reports');
        $this->folder('2026', $folder);

        $response = $this->dav()->request('MOVE', 'files/space-1/Reports', ['Destination' => '/dav/files/space-1/Reports/2026/Reports']);

        $this->assertGreaterThanOrEqual(400, $response->statusCode);
        $this->assertNull(Folder::findOne($folder->id)->parent_folder_id);
    }

    public function testACopyIsNamedAfterItsDestination()
    {
        $file = $this->file('plan.txt', 'the plan');

        $response = $this->dav()->request('COPY', 'files/space-1/plan.txt', ['Destination' => '/dav/files/space-1/plan copy.txt']);

        $this->assertSame(201, $response->statusCode);
        $level = new FolderContentService($this->space);
        $this->assertSame('the plan', $level->findFile('plan copy.txt')->baseFile->store->getContent());
        $this->assertSame('plan.txt', File::findOne($file->id)->getTitle());
    }

    public function testADeleteMovesToTheTrash()
    {
        $file = $this->file('plan.txt');

        $this->assertSame(204, $this->dav()->request('DELETE', 'files/space-1/plan.txt')->statusCode);

        $this->assertSame(Content::STATE_DELETED, (int)File::findOne($file->id)->content->state);
        $this->assertSame(404, $this->dav()->request('GET', 'files/space-1/plan.txt')->statusCode);
    }

    public function testTheTopLevelOfASpaceCannotBeDeletedOrMoved()
    {
        $this->assertSame(403, $this->dav()->request('DELETE', 'files/space-1/')->statusCode);
        $this->assertSame(403, $this->dav()->request('MOVE', 'files/space-1/', ['Destination' => '/dav/files/space-1-renamed/'])->statusCode);
    }
}
