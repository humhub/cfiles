<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\cfiles\serializers;

use humhub\components\api\Format;
use humhub\libs\MimeHelper;
use humhub\modules\cfiles\models\File;
use humhub\modules\file\converter\PreviewImage;
use humhub\modules\file\libs\FileHelper;
use humhub\modules\user\serializers\UserSerializer;
use yii\helpers\Url;

/**
 * The wire representation of a cfiles {@see File} (see `docs/develop/concept-api.md`).
 *
 * A cfiles file is two records: the content record carrying title, description, visibility
 * and authorship, and the platform file record ({@see \humhub\modules\file\models\File})
 * carrying the bytes. The client should not have to know that, so this flattens both into
 * one row shape that sits next to {@see FolderSerializer::folder()} in the same list.
 *
 * Caller-neutral by construction — see {@see FolderSerializer}.
 *
 * @since 1.0
 */
class FileSerializer
{
    /**
     * @return array{
     *     type: string,
     *     id: int,
     *     contentId: int,
     *     guid: string|null,
     *     title: string,
     *     description: string,
     *     visibility: int,
     *     mimeType: string|null,
     *     mimeIcon: string|null,
     *     size: int,
     *     url: string|null,
     *     downloadUrl: string|null,
     *     previewUrl: string|null,
     *     downloadCount: int,
     *     parentFolderId: int|null,
     *     createdAt: string|null,
     *     updatedAt: string|null,
     *     creator: array|null,
     * }
     */
    public static function file(File $file): array
    {
        $content = $file->content;
        $baseFile = $file->baseFile;
        $previewImage = new PreviewImage();

        return [
            'type' => 'file',
            'id' => (int)$file->id,
            'contentId' => (int)$content->id,
            'guid' => $baseFile?->guid,
            // The file name IS the title of a cfiles file - renaming one renames the stored
            // file (see File::setTitle()).
            'title' => (string)$file->getTitle(),
            'description' => (string)$file->description,
            'visibility' => (int)$content->visibility,
            'mimeType' => $baseFile?->mime_type,
            'mimeIcon' => $baseFile
                ? MimeHelper::getMimeIconClassByExtension(FileHelper::getExtension($baseFile->file_name))
                : null,
            'size' => (int)($baseFile?->size ?? 0),
            // Opens the file the way the browser prefers (inline for what it can render).
            'url' => $baseFile?->getUrl([], true),
            // Forces a download and survives renames and content changes - see
            // humhub\modules\cfiles\controllers\DownloadController for why the indirection
            // exists.
            'downloadUrl' => self::downloadUrl($file),
            'previewUrl' => $baseFile && $previewImage->applyFile($baseFile)
                ? Url::to($previewImage->getUrl(), true)
                : null,
            'downloadCount' => (int)$file->download_count,
            'parentFolderId' => $file->parent_folder_id ? (int)$file->parent_folder_id : null,
            'createdAt' => Format::dateTime($content->created_at),
            'updatedAt' => Format::dateTime($content->updated_at ?: $content->created_at),
            'creator' => UserSerializer::short($content->createdBy),
        ];
    }

    private static function downloadUrl(File $file): ?string
    {
        $container = $file->content->container;

        if ($container === null || $file->baseFile === null) {
            return null;
        }

        return $container->createUrl('/cfiles/download', ['guid' => $file->baseFile->guid], true);
    }
}
