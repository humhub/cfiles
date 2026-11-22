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
     *     visibility: string,
     *     parentFolderId: int|null,
     *     itemCount: int|null,
     *     createdAt: string|null,
     *     updatedAt: string|null,
     *     creator: array|null,
     *     topics: array[],
     *     url: string,
     * }
     * @param array[]|null $topics the folder's ({@see ItemTopicSerializer}), null = looked up
     */
    public static function folder(Folder $folder, ?int $itemCount = null, ?array $topics = null): array
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
            'visibility' => ItemVisibility::name((int)$content->visibility),
            'parentFolderId' => $folder->parent_folder_id ? (int)$folder->parent_folder_id : null,
            // Only filled where the list query counted children anyway; null means "not
            // counted", which a client renders as nothing rather than as zero.
            'itemCount' => $itemCount,
            'createdAt' => Format::dateTime($content->created_at),
            'updatedAt' => Format::dateTime($content->updated_at ?: $content->created_at),
            'creator' => UserSerializer::short($content->createdBy),
            // See ItemTopicSerializer; a listing hands in those of its whole page.
            'topics' => $topics ?? ItemTopicSerializer::forContent($content),
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
        $container = $folder?->content->container;
        if ($container === null) {
            return [];
        }

        // The folders of the container in one query, its tree walked here: one query whatever
        // the depth, instead of a parent, its content and its container per level.
        $folders = Folder::find()
            ->select(['cfiles_folder.id', 'cfiles_folder.parent_folder_id', 'cfiles_folder.title'])
            ->innerJoin('content', 'content.object_id = cfiles_folder.id AND content.object_model = :model', [':model' => Folder::class])
            ->where(['content.contentcontainer_id' => $container->contentcontainer_id])
            ->asArray()
            ->indexBy('id')
            ->all();

        $path = [];
        $seen = [];
        for ($id = (int)$folder->id; isset($folders[$id]); $id = (int)$folders[$id]['parent_folder_id']) {
            // A corrupt parent chain (a cycle) must not hang the request; the integrity check
            // is what repairs it, this just refuses to spin.
            if (isset($seen[$id])) {
                break;
            }
            $seen[$id] = true;

            array_unshift($path, [
                'id' => $id,
                'title' => (string)$folders[$id]['title'],
                'url' => $container->createUrl('/cfiles/browse/index', ['fid' => $id], true),
            ]);
        }

        return $path;
    }
}
