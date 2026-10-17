<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\cfiles\controllers\api;

use humhub\modules\cfiles\models\FileSystemItem;
use humhub\modules\cfiles\models\Folder;
use humhub\modules\cfiles\services\ItemMoveService;
use Yii;
use yii\filters\VerbFilter;
use yii\helpers\ArrayHelper;
use yii\web\ForbiddenHttpException;

/**
 * Operations over a SET of files and folders — moving and deleting.
 *
 * Both exist only in the bulk form, and a context menu acting on one row sends a
 * one-element `items` array. One way to delete beats two that have to stay in step.
 *
 * Each operation reports per item rather than failing the whole request on the first
 * problem: moving twelve files into a folder where one name collides should move the other
 * eleven, and the client has to be able to say which one did not make it.
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
     * Moves `items` into `targetFolderId`, or to the container's top level when that is
     * omitted or null.
     */
    public function actionMove()
    {
        $request = Yii::$app->request;
        $container = $this->findContainer((int)$request->getBodyParam('containerId'));
        $this->assertCanWrite($container);

        // Null target = the container's top level, which has no folder record to name.
        $target = $this->findParent($container, $request->getBodyParam('targetFolderId'));

        $moved = [];
        $errors = [];

        foreach ($this->requestedItems() as $item) {
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

        return $this->result($moved, $errors);
    }

    /**
     * Deletes `items`.
     */
    public function actionDelete()
    {
        $deleted = [];
        $errors = [];

        foreach ($this->requestedItems() as $item) {
            if (!$item->content->canEdit()) {
                throw new ForbiddenHttpException();
            }

            $descriptor = $this->descriptor($item);

            if ($item->delete()) {
                $deleted[] = $descriptor;
                continue;
            }

            $errors[] = $this->itemError($item, Yii::t('CfilesModule.base', 'Could not delete the item!'));
        }

        return $this->result($deleted, $errors);
    }

    /**
     * The `items` of the request, resolved to models the caller may see.
     *
     * @return FileSystemItem[]
     */
    private function requestedItems(): array
    {
        $items = Yii::$app->request->getBodyParam('items');

        if (!is_array($items)) {
            return [];
        }

        return array_map(
            fn($item) => $this->findItem(is_array($item) ? $item : []),
            array_slice($items, 0, self::MAX_ITEMS),
        );
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

    private function itemError(FileSystemItem $item, string $message): array
    {
        return $this->descriptor($item) + [
            'title' => $item->getTitle(),
            'message' => $message,
        ];
    }

    /**
     * `422` when nothing at all succeeded, so a client can treat a wholesale failure as one —
     * a partial success stays a `200` carrying both lists.
     */
    private function result(array $succeeded, array $errors): array
    {
        if ($succeeded === [] && $errors !== []) {
            Yii::$app->response->statusCode = 422;
        }

        return ['results' => $succeeded, 'errors' => $errors];
    }
}
