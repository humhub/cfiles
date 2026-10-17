<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\cfiles\services;

use humhub\modules\cfiles\models\File;
use humhub\modules\file\actions\DownloadAction;
use Yii;
use yii\base\Action;

/**
 * Counts file downloads.
 *
 * The count is a side effect of the core file module serving a download, so it is decided
 * entirely from the request: only a successful, explicit, non-HEAD download of the original
 * file counts — not a thumbnail, not a preview variant, not a range request — and only once
 * per session per file, so reloading a PDF viewer does not inflate the number.
 *
 * That is a pile of request conditions, which is why it is not on the model and not in the
 * event handler either: `Events` should say WHEN, this says WHAT.
 *
 * @since 1.0
 */
class DownloadCounterService
{
    /**
     * @var string session key holding the ids already counted for this visitor
     */
    private const SESSION_KEY = 'trackedDownloads';

    /**
     * Counts the download the given action just served, if it was one.
     */
    public static function track(?Action $action): void
    {
        if (!$action instanceof DownloadAction || !self::isCountableRequest()) {
            return;
        }

        $file = static::findByGuid((string)Yii::$app->request->get('guid'));

        if ($file === null || self::alreadyCounted($file)) {
            return;
        }

        self::remember($file);

        File::updateAllCounters(['download_count' => 1], ['id' => $file->id]);
    }

    /**
     * The cfiles file wrapping the platform file of this guid.
     */
    public static function findByGuid(string $guid): ?File
    {
        if ($guid === '') {
            return null;
        }

        return File::find()
            ->innerJoin('file', 'object_id = ' . File::tableName() . '.id')
            ->where(['guid' => $guid])
            ->andWhere(['object_model' => File::class])
            ->one();
    }

    private static function isCountableRequest(): bool
    {
        $request = Yii::$app->request;

        return $request->isGet
            && !$request->isHead
            && Yii::$app->response->statusCode === 200
            // A thumbnail or a converted preview is not a download.
            && $request->get('variant') === null
            && $request->get('suffix') === null
            // Only an explicit download, not viewing the file inline.
            && $request->get('download', false)
            && !empty($request->get('guid'));
    }

    private static function alreadyCounted(File $file): bool
    {
        return in_array((int)$file->id, self::counted(), true);
    }

    private static function remember(File $file): void
    {
        $counted = self::counted();
        $counted[] = (int)$file->id;

        Yii::$app->session->set(self::SESSION_KEY, $counted);
    }

    /**
     * @return int[]
     */
    private static function counted(): array
    {
        $counted = Yii::$app->session->get(self::SESSION_KEY, []);

        return is_array($counted) ? $counted : [];
    }
}
