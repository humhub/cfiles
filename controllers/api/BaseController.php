<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\cfiles\controllers\api;

use humhub\components\api\BaseController as ApiBaseController;
use humhub\modules\cfiles\models\File;
use humhub\modules\cfiles\models\Folder;
use humhub\modules\cfiles\models\FileSystemItem;
use humhub\modules\cfiles\permissions\WriteAccess;
use humhub\modules\content\components\ContentContainerActiveRecord;
use humhub\modules\content\models\ContentContainer;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;

/**
 * Shared plumbing of the cfiles HTTP API (see `docs/develop/concept-api.md`).
 *
 * Everything the module's UI needs is here or in a subclass — the module ships no other
 * JSON surface, and the Vue island is the only first-party client.
 *
 * @since 1.0
 */
abstract class BaseController extends ApiBaseController
{
    /**
     * @inheritdoc
     *
     * The file browser is a browser UI, so it authenticates with the session cookie; token
     * methods contributed by the `rest` module work as well.
     */
    protected bool $enableSessionAuth = true;

    /**
     * The content container an endpoint is scoped to.
     *
     * Files belong to a container, and every level of its tree — including the top, which has
     * no folder record of its own — is addressed relative to it.
     *
     * The only gate here is that the module is enabled on the container. What the caller may
     * SEE is decided one level down by the `readable()` content scope, so an outsider asking
     * about a private space gets an empty listing rather than an error, and what the caller
     * may CHANGE is decided by {@see self::assertCanWrite()}. There is no single "can access
     * this container" call in the platform — that is a rule set on the controller stack, and
     * an API endpoint answering with what is readable needs neither.
     *
     * @throws NotFoundHttpException for an unknown container, or one without the module
     */
    protected function findContainer(int $id): ContentContainerActiveRecord
    {
        $container = ContentContainer::findOne(['id' => $id])?->polymorphicRelation;

        if (!$container instanceof ContentContainerActiveRecord
            || !$container->moduleManager->isEnabled('cfiles')) {
            throw new NotFoundHttpException();
        }

        return $container;
    }

    /**
     * The folder a request addresses within a container, or null for its top level.
     *
     * @throws NotFoundHttpException when the folder does not exist, is not readable, or
     *         belongs to a different container than the one addressed
     */
    protected function findParent(ContentContainerActiveRecord $container, $parentId): ?Folder
    {
        if ($parentId === null || $parentId === '' || (int)$parentId === 0) {
            return null;
        }

        $folder = $this->findFolder((int)$parentId);

        if ($folder->content->container->contentcontainer_id !== $container->contentcontainer_id) {
            throw new NotFoundHttpException();
        }

        return $folder;
    }

    /**
     * A folder the caller may see.
     *
     * @throws NotFoundHttpException when it does not exist or is not readable — the two are
     *         deliberately indistinguishable, so the API does not confirm the existence of
     *         content the caller may not see.
     */
    protected function findFolder(int $id): Folder
    {
        $folder = Folder::find()->readable()->where(['cfiles_folder.id' => $id])->one();

        if (!$folder instanceof Folder) {
            throw new NotFoundHttpException();
        }

        return $folder;
    }

    /**
     * A file the caller may see.
     *
     * @throws NotFoundHttpException see {@see self::findFolder()}
     */
    protected function findFile(int $id): File
    {
        $file = File::find()->readable()->where(['cfiles_file.id' => $id])->one();

        if (!$file instanceof File) {
            throw new NotFoundHttpException();
        }

        return $file;
    }

    /**
     * An item addressed as `{type, id}`, the way the whole API identifies one.
     *
     * @throws NotFoundHttpException for an unknown type or an item the caller may not see
     */
    protected function findItem(array $item): FileSystemItem
    {
        $id = (int)($item['id'] ?? 0);

        return match ($item['type'] ?? null) {
            'folder' => $this->findFolder($id),
            'file' => $this->findFile($id),
            default => throw new NotFoundHttpException(),
        };
    }

    /**
     * Whether the caller may add to, or change, this container's files at all — the
     * container-wide write permission, as opposed to per-item edit rights.
     */
    protected function canWrite(ContentContainerActiveRecord $container): bool
    {
        return $container->permissionManager->can(WriteAccess::class);
    }

    /**
     * @throws ForbiddenHttpException
     */
    protected function assertCanWrite(ContentContainerActiveRecord $container): void
    {
        if (!$this->canWrite($container)) {
            throw new ForbiddenHttpException();
        }
    }

    /**
     * @throws ForbiddenHttpException
     */
    protected function assertCanEdit(FileSystemItem $item): void
    {
        if (!$item->content->canEdit()) {
            throw new ForbiddenHttpException();
        }
    }
}
