<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\cfiles\serializers;

use humhub\components\api\Format;
use humhub\modules\cfiles\models\Folder;
use humhub\modules\user\serializers\UserSerializer;

/**
 * The wire representation of a {@see Folder} (see `docs/develop/concept-api.md`).
 *
 * Caller-neutral by construction: nothing here depends on who is asking, so one
 * serialization serves every reader who may see the folder. What the caller may DO with it
 * comes from the content controls endpoint, which the row's `⋮` loads on open.
 *
 * @since 1.0
 */
class FolderSerializer
{
    /**
     * @return array{
     *     type: string,
     *     id: int,
     *     contentId: int,
     *     title: string,
     *     description: string,
     *     visibility: int,
     *     isRoot: bool,
     *     parentFolderId: int|null,
     *     itemCount: int|null,
     *     createdAt: string|null,
     *     updatedAt: string|null,
     *     creator: array|null,
     *     url: string,
     * }
     */
    public static function folder(Folder $folder, ?int $itemCount = null): array
    {
        $content = $folder->content;

        return [
            // Folders and files share one list, so every row says what it is.
            'type' => 'folder',
            'id' => (int)$folder->id,
            'contentId' => (int)$content->id,
            // The RAW stored title, deliberately not Folder::getTitle(): that localizes the
            // root folder's name, and a localized payload depends on who is asking, which is
            // exactly what keeps it from being cacheable. `isRoot` lets the client label it.
            'title' => (string)$folder->title,
            'description' => (string)$folder->description,
            'visibility' => (int)$content->visibility,
            'isRoot' => $folder->isRoot(),
            'parentFolderId' => $folder->parent_folder_id ? (int)$folder->parent_folder_id : null,
            // Only filled where the list query counted children anyway; null means "not
            // counted", which a client renders as nothing rather than as zero.
            'itemCount' => $itemCount,
            'createdAt' => Format::dateTime($content->created_at),
            'updatedAt' => Format::dateTime($content->updated_at ?: $content->created_at),
            'creator' => UserSerializer::short($content->createdBy),
            'url' => $folder->getUrl(true),
        ];
    }

    /**
     * The path from the root folder down to (and including) the given folder — what a
     * breadcrumb renders.
     *
     * @return array[]
     */
    public static function path(Folder $folder): array
    {
        $path = [];
        $seen = [];

        for ($current = $folder; $current !== null; $current = $current->parentFolder) {
            // A corrupt parent chain (a cycle) must not hang the request; the integrity check
            // is what repairs it, this just refuses to spin.
            if (isset($seen[$current->id])) {
                break;
            }
            $seen[$current->id] = true;

            array_unshift($path, [
                'id' => (int)$current->id,
                'title' => (string)$current->title,
                'isRoot' => $current->isRoot(),
                'url' => $current->getUrl(true),
            ]);
        }

        return $path;
    }
}
