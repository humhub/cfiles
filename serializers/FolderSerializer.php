<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\cfiles\serializers;

use humhub\components\api\Format;
use humhub\models\RecordMap;
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
     *     recordId: int,
     *     title: string,
     *     description: string,
     *     visibility: int,
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
            // What the like API addresses a record by — a platform-wide id, not the content's
            // (see humhub\models\RecordMap).
            'recordId' => RecordMap::getId($folder),
            'title' => (string)$folder->title,
            'description' => (string)$folder->description,
            'visibility' => (int)$content->visibility,
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
     * The ancestors of the given folder, down to and including it — what a breadcrumb renders
     * after its own top-level entry. Empty for the top level, which has no record of its own.
     *
     * @return array[]
     */
    public static function path(?Folder $folder): array
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
                'url' => $current->getUrl(true),
            ]);
        }

        return $path;
    }
}
