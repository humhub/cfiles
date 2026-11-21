<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\cfiles\controllers\api;

use humhub\modules\cfiles\models\FileSystemItem;
use humhub\modules\cfiles\models\Folder;
use humhub\modules\cfiles\Module;
use humhub\modules\cfiles\services\ItemMoveService;
use Yii;
use yii\filters\VerbFilter;
use yii\helpers\ArrayHelper;
use yii\web\NotFoundHttpException;

/**
 * Operations over a SET of files and folders — moving and deleting.
 *
 * Both exist only in the bulk form, and a context menu acting on one row sends a
 * one-element `items` array. One way to delete beats two that have to stay in step. That is
 * also why a delete is an operation here (`POST items/delete`) rather than the `DELETE` of a
 * record: it acts on a set, and a `DELETE` carries no body to name one in.
 *
 * Each operation reports per item rather than failing the whole request on the first
 * problem: moving twelve files into a folder where one name collides should move the other
 * eleven, and the client has to be able to say which one did not make it. Like the core's
 * upload, it answers `200` with `{results, errors}` whenever the request was well-formed — even
 * if nothing could be carried out —, an error `{type, id, title?, messages}` per item; only a
 * request without `items`, or with more than {@see self::MAX_ITEMS}, is a `422`.
 *
 * @since 1.0
 */
class ItemController extends BaseController
{
    /**
     * @var int how many items one request may carry
     */
    public const MAX_ITEMS = 200;

    /**
     * @inheritdoc
     */
    protected array $scopes = ['*' => Module::SCOPE_WRITE];

    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return ArrayHelper::merge(parent::behaviors(), [
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'move' => ['POST'],
                    'delete' => ['POST'],
                ],
            ],
        ]);
    }

    /**
     * Moves `items` into the folder `parentFolderId` of the container `containerId`, or to the
     * container's top level when that is omitted or null.
     */
    public function actionMove()
    {
        $request = Yii::$app->request;
        $container = $this->findContainer((int)$request->getBodyParam('containerId'));
        $this->assertCanWrite($container);

        // Null target = the container's top level, which has no folder record to name.
        $target = $this->findParent($container, $request->getBodyParam('parentFolderId'));

        $refusal = $this->refusal();
        if ($refusal !== null) {
            return $refusal;
        }

        $moved = [];
        $errors = [];

        foreach ($this->requestedItems() as [$descriptor, $item]) {
            if ($item === null) {
                $errors[] = $this->missingError($descriptor);
                continue;
            }

            // Cross-container moves are a different feature (content move) with its own
            // permission rules; this endpoint only rearranges one container's own tree.
            if ($item->content->container->contentcontainer_id !== $container->contentcontainer_id) {
                $errors[] = $this->itemError($item, Yii::t('CfilesModule.base', 'Wrong target folder!'));
                continue;
            }

            if (ItemMoveService::moveInto($container, $target, $item)) {
                $moved[] = $this->descriptor($item);
                continue;
            }

            $errors[] = $this->itemError(
                $item,
                $item->getFirstError($item->getTitle()) ?? Yii::t('CfilesModule.base', 'Could not move the item!'),
            );
        }

        return ['results' => $moved, 'errors' => $errors];
    }

    /**
     * Deletes `items`.
     */
    public function actionDelete()
    {
        $refusal = $this->refusal();
        if ($refusal !== null) {
            return $refusal;
        }

        $deleted = [];
        $errors = [];

        foreach ($this->requestedItems() as [$descriptor, $item]) {
            if ($item === null) {
                $errors[] = $this->missingError($descriptor);
                continue;
            }

            if (!$item->content->canEdit()) {
                $errors[] = $this->itemError($item, Yii::t('CfilesModule.base', 'Could not delete the item!'));
                continue;
            }

            $descriptor = $this->descriptor($item);

            if ($item->delete()) {
                $deleted[] = $descriptor;
                continue;
            }

            $errors[] = $this->itemError($item, Yii::t('CfilesModule.base', 'Could not delete the item!'));
        }

        return ['results' => $deleted, 'errors' => $errors];
    }

    /**
     * The `422` answer for a request whose `items` cannot be carried out at all: none, or more
     * than one request may carry — refused as a whole rather than carried out in part. Null for
     * a request that can.
     */
    private function refusal(): ?array
    {
        $items = Yii::$app->request->getBodyParam('items');

        if (!is_array($items) || $items === []) {
            return $this->missingParameter('items');
        }

        if (count($items) > self::MAX_ITEMS) {
            return $this->validationErrors(['items' => [
                Yii::t('CfilesModule.base', 'At most {count} items can be moved or deleted at once.', ['count' => self::MAX_ITEMS]),
            ]]);
        }

        return null;
    }

    /**
     * The `items` of the request, each as `[descriptor, model]` — the model null for an item
     * that does not exist or that the caller may not see, so that one stale entry in a
     * selection does not fail the others.
     *
     * @return array{0: array, 1: FileSystemItem|null}[]
     */
    private function requestedItems(): array
    {
        return array_map(function ($item) {
            $item = is_array($item) ? $item : [];

            try {
                return [$item, $this->findItem($item)];
            } catch (NotFoundHttpException) {
                return [$item, null];
            }
        }, array_values(Yii::$app->request->getBodyParam('items')));
    }

    private function missingError(array $descriptor): array
    {
        return [
            'type' => (string)($descriptor['type'] ?? ''),
            'id' => (int)($descriptor['id'] ?? 0),
            'messages' => [Yii::t('CfilesModule.base', 'The item could not be found.')],
        ];
    }

    /**
     * How this API names one item — the same `{type, id}` shape a request uses to address it.
     *
     * Deliberately not derived from `getItemType()`: that answers `folder-root` for the root
     * folder and a mime-derived type (`image`, …) for a file.
     */
    private function descriptor(FileSystemItem $item): array
    {
        return [
            'type' => $item instanceof Folder ? 'folder' : 'file',
            'id' => (int)$item->id,
        ];
    }

    private function itemError(FileSystemItem $item, string ...$messages): array
    {
        return $this->descriptor($item) + [
            'title' => $item->getTitle(),
            'messages' => $messages,
        ];
    }

}
