<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\cfiles\controllers\api;

use humhub\components\listing\ListContext;
use humhub\components\listing\ListValidationException;
use humhub\modules\cfiles\components\FolderList;
use humhub\modules\cfiles\Module;
use humhub\modules\cfiles\serializers\FileSerializer;
use humhub\modules\cfiles\services\BrowserPreferences;
use humhub\modules\cfiles\serializers\FolderSerializer;
use humhub\modules\cfiles\services\FolderContentService;
use humhub\modules\cfiles\services\FolderListingService;
use humhub\modules\file\models\File as BaseFile;
use Yii;
use yii\filters\VerbFilter;
use yii\helpers\ArrayHelper;
use yii\web\UploadedFile;

/**
 * Reading a level of a container's file tree, and adding to it.
 *
 * Everything here is addressed as a container plus an optional `parentFolderId`, because that
 * is what a level actually is: there is no folder record standing in for the top, so a folder
 * id alone cannot name every level. Changing an item that already exists is the other way
 * round — it has an id of its own, see {@see FileController} and {@see ItemController}.
 *
 * @since 1.0
 */
class FolderController extends BaseController
{
    /**
     * @inheritdoc
     *
     * A guest may open the browser of a public space, so the levels and pages after the first,
     * which the page embeds, have to be readable for them too. The listing is guest-safe
     * through the `readable()` content scope, the same one that page render runs.
     */
    protected array $guestAllowedActions = ['items'];

    /**
     * @inheritdoc
     */
    protected array $scopes = [
        'items' => Module::SCOPE_READ,
        'suggestions' => Module::SCOPE_READ,
        'conflicts' => Module::SCOPE_READ,
        '*' => Module::SCOPE_WRITE,
    ];

    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return ArrayHelper::merge(parent::behaviors(), [
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'items' => ['GET', 'HEAD'],
                    'suggestions' => ['GET', 'HEAD'],
                    'update' => ['PATCH'],
                    'create' => ['POST'],
                    'upload' => ['POST'],
                    'conflicts' => ['GET', 'HEAD'],
                ],
            ],
        ]);
    }

    /**
     * One level of the tree: the folder itself (null at the top), the path down to it, and a
     * page of its contents with folders sorted ahead of files.
     *
     * Parameters are those of {@see FolderList} (`parentFolderId`, the filters `q`, `userId`, `type`,
     * `modified`, and `sort`) plus `page`/`pageSize`; with a filter set the page holds the hits in the folder
     * and all its subfolders (`resultsMode`, a `path` per item);
     * any other is refused (`422`), as is a `parentFolderId` that is not a readable folder of this
     * container. Without `sort` the user's last choice applies, a `sort` sent is remembered
     * ({@see BrowserPreferences}). Without `pageSize` the page is that of the user's view.
     *
     * @param int|string $containerId the content container id
     */
    public function actionItems($containerId)
    {
        $container = $this->findContainer((int)$containerId);
        // The container travels in the path; the rule puts it among the query parameters.
        $params = array_diff_key($this->listParams(), ['containerId' => true]);

        $context = ListContext::forCurrentUser(null, $container);
        $preferences = new BrowserPreferences($context->user);

        try {
            $list = (new FolderList(['storedSort' => $preferences->sort()]))->build($params, $context);
        } catch (ListValidationException $e) {
            return $this->validationErrors($e->errors);
        }

        // A sort sent is the user's choice from now on; `default` forgets it. Remembered here,
        // not by the list, which has no side effects. Deliberately also a sort that came with a
        // shared link (the page URL's, sent on the next load): it is what the reader now sees.
        $sort = $params[FolderList::SORT_PARAM] ?? null;
        if (is_string($sort) && $sort !== '') {
            $preferences->setSort($sort === FolderList::SORT_DEFAULT ? null : $sort);
        }

        $request = Yii::$app->request;

        return (new FolderListingService($list))->payload(
            (int)$request->get('page', 1),
            $request->get('pageSize') === null ? null : (int)$request->get('pageSize'),
        );
    }

    /**
     * What a picker filter of the file browser suggests before anything is typed
     * (`filter`: the key of the space's author or topic filter): `{results: [...]}`, at most 6
     * items in the shape of the filter's picker search — those of the space's latest files the
     * user may read ({@see \humhub\modules\cfiles\components\FilterSuggestions}). `422` for a key
     * that names no such filter. Requested by the filter's control when it first opens.
     */
    public function actionSuggestions($containerId)
    {
        $container = $this->findContainer((int)$containerId);
        $filter = (string)Yii::$app->request->get('filter', '');

        $suggestions = (new FolderList())->suggestions($filter, ListContext::forCurrentUser(null, $container));
        if ($suggestions === null) {
            return $this->validationErrors(['filter' => [Yii::t('CfilesModule.base', 'Unknown filter.')]]);
        }

        return ['results' => $suggestions];
    }

    /**
     * Creates a folder at the addressed level (`parentFolderId`, none for the top level) with
     * `title`, `description` and `visibility` (`private` or `public`).
     */
    public function actionCreate($containerId)
    {
        $container = $this->findContainer((int)$containerId);
        $this->assertCanWrite($container);

        $parent = $this->findParent($container, Yii::$app->request->getBodyParam('parentFolderId'));

        $visibility = $this->visibilityParam();
        if ($visibility !== null && !$visibility->isValid()) {
            return $this->validationErrors($visibility->errors);
        }

        $folder = (new FolderContentService($container, $parent))->newFolder();
        $folder->setAttributes($this->writableAttributes(), false);
        if ($visibility !== null) {
            $folder->visibility = $visibility->value;
        }

        if (!$folder->save()) {
            return $this->validationErrors($folder);
        }

        Yii::$app->response->statusCode = 201;

        // A new folder has no topics yet: nothing to look up.
        return FolderSerializer::folder($folder, null, []);
    }

    /**
     * Renames a folder, changes its description, its visibility (`private` or `public`) or its
     * topics (`topics`, see {@see self::topicsParam()}).
     */
    public function actionUpdate($id)
    {
        $folder = $this->findFolder((int)$id);
        $this->assertCanEdit($folder);

        $topics = $this->topicsParam($folder);
        if ($topics !== null && !$topics->isValid()) {
            return $this->validationErrors($topics->errors);
        }

        $visibility = $this->visibilityParam();
        if ($visibility !== null && !$visibility->isValid()) {
            return $this->validationErrors($visibility->errors);
        }

        $folder->setAttributes($this->writableAttributes(), false);
        if ($visibility !== null) {
            $folder->visibility = $visibility->value;
        }
        if ($topics !== null) {
            $folder->topics = $topics->present ? $topics->value : [];
        }

        if (!$folder->save()) {
            return $this->validationErrors($folder);
        }

        return FolderSerializer::folder($folder);
    }

    /**
     * @var int how many names one conflict check may carry
     */
    public const MAX_NAMES = 100;

    /**
     * Which of `names` an item of the level already has — asked before an upload, so the user
     * can decide what happens to those: replaced (`replace` of the upload), kept both under an
     * indexed name, or skipped. Per name taken `{name, type, id, canReplace}`: `name` as asked,
     * `canReplace` whether the caller may upload a new version of that file — never of a folder.
     *
     * Only for those who may add to the container: the answer is about an upload. Items in the
     * trash or not yet published hold no name (see FolderContentService).
     */
    public function actionConflicts($containerId)
    {
        $container = $this->findContainer((int)$containerId);
        $this->assertCanWrite($container);

        $request = Yii::$app->request;
        $parent = $this->findParent($container, $request->get('parentFolderId'));
        $names = $request->get('names');

        if (!is_array($names) || count($names) > self::MAX_NAMES) {
            Yii::$app->response->statusCode = 422;

            return ['errors' => ['names' => [
                Yii::t('CfilesModule.base', 'A list of at most {count} names is required.', ['count' => self::MAX_NAMES]),
            ]]];
        }

        $level = new FolderContentService($container, $parent);
        $conflicts = [];

        foreach (array_unique(array_map('strval', $names)) as $name) {
            if (($file = $level->findFile($name)) !== null) {
                $conflicts[] = ['name' => $name, 'type' => 'file', 'id' => (int)$file->id, 'canReplace' => (bool)$file->content->canEdit()];
            } elseif (($folder = $level->findFolder($name)) !== null) {
                $conflicts[] = ['name' => $name, 'type' => 'folder', 'id' => (int)$folder->id, 'canReplace' => false];
            }
        }

        return ['conflicts' => $conflicts];
    }

    /**
     * Uploads one or more files to the addressed level - or, with `guids`, files platform files
     * that already exist there (what a file handler such as "New spreadsheet" created).
     *
     * A name already taken in the level is indexed (`report(1).pdf`) — unless it is named in
     * `replace`: then the upload is stored as a new version of the file of that name, the
     * previous content kept as an older one, where the caller may change that file. Never a
     * silent overwrite: the browser asks first (see actionConflicts()).
     *
     * Answers per file rather than failing the whole request on one bad upload: a batch in
     * which a single file is rejected should still land the others, and the client has to be
     * able to say which one did not make it. Like the core's upload (`POST /api/v2/file`) with
     * `200` whenever the request carried a file, even if none could be added; a request
     * without any is a `422`.
     */
    public function actionUpload($containerId)
    {
        $container = $this->findContainer((int)$containerId);
        $this->assertCanWrite($container);

        $parent = $this->findParent($container, Yii::$app->request->post('parentFolderId'));

        $guids = Yii::$app->request->post('guids');
        if (!empty($guids)) {
            return $this->attach(new FolderContentService($container, $parent), (array)$guids);
        }

        $uploads = UploadedFile::getInstancesByName('files');

        if ($uploads === []) {
            Yii::$app->response->statusCode = 422;

            return ['errors' => ['files' => [Yii::t('CfilesModule.base', 'No file uploaded.')]]];
        }

        $content = new FolderContentService($container, $parent);
        $created = [];
        $errors = [];

        $replace = array_map('strval', (array)Yii::$app->request->post('replace', []));

        foreach ($uploads as $upload) {
            $file = $content->addUploadedFile($upload, in_array($upload->name, $replace, true));

            if ($file->hasErrors() || $file->baseFile->hasErrors()) {
                $errors[] = [
                    'fileName' => $upload->name,
                    'messages' => array_merge(
                        array_values($file->getFirstErrors()),
                        array_values($file->baseFile->getFirstErrors()),
                    ),
                ];
                continue;
            }

            // A new file has no topics yet: nothing to look up.
            $created[] = FileSerializer::file($file, []);
        }

        return ['results' => $created, 'errors' => $errors];
    }

    /**
     * Files the platform files of the given guids into a level, see {@see FolderContentService::addExistingFile()}.
     */
    private function attach(FolderContentService $content, array $guids): array
    {
        $created = [];
        $errors = [];

        foreach ($guids as $guid) {
            $baseFile = BaseFile::findOne(['guid' => (string)$guid]);
            $file = $baseFile === null ? null : $content->addExistingFile($baseFile);

            if ($file === null) {
                $errors[] = [
                    'fileName' => $baseFile?->file_name ?? (string)$guid,
                    'messages' => [Yii::t('CfilesModule.base', 'The file could not be added.')],
                ];
                continue;
            }

            if ($file->hasErrors() || $file->baseFile->hasErrors()) {
                $errors[] = [
                    'fileName' => $file->baseFile->file_name,
                    'messages' => array_merge(
                        array_values($file->getFirstErrors()),
                        array_values($file->baseFile->getFirstErrors()),
                    ),
                ];
                continue;
            }

            $created[] = FileSerializer::file($file, []);
        }

        return ['results' => $created, 'errors' => $errors];
    }

    /**
     * The attributes a folder write accepts as they are; `visibility` and `topics` are parsed
     * on their own ({@see self::visibilityParam()}, {@see self::topicsParam()}).
     *
     * An allowlist rather than `load()`: mass assignment would otherwise reach
     * `parent_folder_id`, which is what the move endpoint is for, with its own permission
     * rules.
     */
    private function writableAttributes(): array
    {
        $request = Yii::$app->request;
        $attributes = [];

        foreach (['title', 'description'] as $name) {
            if ($request->getBodyParam($name) !== null) {
                $attributes[$name] = (string)$request->getBodyParam($name);
            }
        }

        return $attributes;
    }
}
