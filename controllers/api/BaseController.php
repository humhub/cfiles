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
