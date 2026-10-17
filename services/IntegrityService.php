<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\cfiles\services;

use humhub\commands\IntegrityController;
use humhub\modules\cfiles\models\File;
use humhub\modules\cfiles\models\FileSystemItem;
use humhub\modules\cfiles\models\Folder;

/**
 * The module's contribution to `php yii integrity`.
 *
 * One rule: an item that names a parent folder which no longer exists is unreachable — the
 * browser can never navigate to it and no cascade will ever catch it — so it is offered for
 * deletion. An item with no parent at all is the root folder, which is fine.
 *
 * @since 1.0
 */
class IntegrityService
{
    public static function check(IntegrityController $controller): void
    {
        self::checkOrphans($controller, File::find()->all(), 'file');
        self::checkOrphans($controller, Folder::find()->all(), 'folder');
    }

    /**
     * @param FileSystemItem[] $items
     */
    private static function checkOrphans(IntegrityController $controller, array $items, string $label): void
    {
        $controller->showTestHeadline('CFiles Module (' . count($items) . ' ' . $label . ' entries)');

        foreach ($items as $item) {
            if (empty($item->parent_folder_id) || $item->parentFolder !== null) {
                continue;
            }

            if ($controller->showFix('Deleting cfiles ' . $label . ' id ' . $item->id . ' without existing parent!')) {
                $item->hardDelete();
            }
        }
    }
}
