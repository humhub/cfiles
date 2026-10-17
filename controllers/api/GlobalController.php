<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2026 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\cfiles\controllers\api;

use humhub\components\listing\ListContext;
use humhub\components\listing\ListValidationException;
use humhub\modules\cfiles\components\FolderList;
use humhub\modules\cfiles\components\GlobalFolderList;
use humhub\modules\cfiles\Module;
use humhub\modules\cfiles\services\BrowserPreferences;
use humhub\modules\cfiles\services\GlobalListingService;
use Yii;
use yii\filters\VerbFilter;
use yii\helpers\ArrayHelper;
use yii\web\NotFoundHttpException;

/**
 * The global files page's listing, `GET /api/v2/cfiles/items`: the spaces of the user as tiles,
 * or the hits across them ({@see GlobalFolderList}). Inside a space the page lists with the
 * file browser's own endpoint, `GET /api/v2/cfiles/<containerId>/items`.
 *
 * For logged-in users only (no guest action: a guest is refused by the core's authentication,
 * 401), and only while the admin switched the page on ({@see Module::getShowGlobalMenuItem()}) —
 * switched off, it does not exist, for guests as well.
 *
 * @property Module $module
 */
class GlobalController extends BaseController
{
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
                ],
            ],
        ]);
    }

    /**
     * @inheritdoc
     */
    public function beforeAction($action)
    {
        // Before authentication: switched off, the endpoint does not exist.
        if (!$this->module->getShowGlobalMenuItem()) {
            Yii::$app->response->format = 'json';
            throw new NotFoundHttpException();
        }

        return parent::beforeAction($action);
    }

    /**
     * Parameters are those of {@see GlobalFolderList}, plus `page` and `pageSize` (default: the
     * page size of the user's view). A sort sent is remembered as the file browser's is — it is
     * the same choice ({@see BrowserPreferences}).
     */
    public function actionItems()
    {
        $params = $this->listParams();

        $context = ListContext::forCurrentUser();
        $preferences = new BrowserPreferences($context->user);

        try {
            $list = (new GlobalFolderList(['storedSort' => $preferences->sort()]))->build($params, $context);
        } catch (ListValidationException $e) {
            return $this->validationErrors($e->errors);
        }

        $sort = $params[FolderList::SORT_PARAM] ?? null;
        if (is_string($sort) && $sort !== '') {
            $preferences->setSort($sort === FolderList::SORT_DEFAULT ? null : $sort);
        }

        $request = Yii::$app->request;

        return (new GlobalListingService($list))->payload(
            (int)$request->get('page', 1),
            $request->get('pageSize') === null ? null : (int)$request->get('pageSize'),
        );
    }
}
