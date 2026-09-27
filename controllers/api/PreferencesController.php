<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\cfiles\controllers\api;

use humhub\modules\cfiles\services\BrowserPreferences;
use humhub\modules\cfiles\services\FolderListingService;
use Yii;
use yii\filters\VerbFilter;
use yii\helpers\ArrayHelper;

/**
 * What the file browser remembers for the caller that is not part of a listing's meaning:
 * the view (`tiles` or `list`). The sort is remembered by the listing itself
 * ({@see \humhub\modules\cfiles\components\FolderList}), because it is a parameter of it; a
 * view only decides how a page is shown — and how large it is, which the client asks for
 * itself (`pageSize`).
 *
 * No guest access: a guest has no settings to keep a preference in.
 *
 * @since 1.0
 */
class PreferencesController extends BaseController
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
                    'update' => ['PATCH'],
                ],
            ],
        ]);
    }

    /**
     * Stores `view` (one of {@see FolderListingService::VIEWS}) and answers the preferences.
     */
    public function actionUpdate()
    {
        $view = Yii::$app->request->getBodyParam('view');

        if ($view === null || $view === '') {
            return $this->missingParameter('view');
        }

        if (!is_string($view) || !isset(FolderListingService::VIEWS[$view])) {
            return $this->validationErrors(['view' => [
                Yii::t('base', 'Unknown value "{value}".', ['value' => is_string($view) ? $view : '']),
            ]]);
        }

        $preferences = new BrowserPreferences(Yii::$app->user->getIdentity());
        $preferences->setView($view);

        return ['view' => $preferences->view()];
    }
}
