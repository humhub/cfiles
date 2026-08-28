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
use humhub\modules\file\handler\DownloadFileHandler;
use humhub\modules\file\handler\FileHandlerCollection;
use humhub\modules\file\libs\FileHelper;
use humhub\modules\file\widgets\FileDownload;
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
            // Where clicking the name goes, and with which attributes — see self::link().
            'link' => self::link($file),
            'downloadCount' => (int)$file->download_count,
            'parentFolderId' => $file->parent_folder_id ? (int)$file->parent_folder_id : null,
            'createdAt' => Format::dateTime($content->created_at),
            'updatedAt' => Format::dateTime($content->updated_at ?: $content->created_at),
            'creator' => UserSerializer::short($content->createdBy),
        ];
    }

    /**
     * Where the file's name links, and the attributes that link needs.
     *
     * A file is not always just a download: modules contribute handlers that view or edit one
     * (an office suite, a diagram editor), and the platform decides between two link shapes
     * accordingly — `FileHelper::createLink()` is the server-rendered original this mirrors.
     * With nothing but the download handler registered it is a plain link carrying the
     * `data-file-*` attributes the platform's download JS reads; with anything else it opens
     * the file dialog in the global modal, which offers the handlers.
     *
     * The client cannot make this call — the handler collection lives here — so the decision
     * ships with the payload as a url plus attributes, the same shape a described menu entry
     * uses.
     *
     * Handler registration CAN depend on the caller (a module may only offer its editor to
     * someone who may edit), which makes this the one part of the payload that is not
     * strictly caller-neutral. The degradation is mild and one-directional: a cached decision
     * at worst opens the dialog for someone who then only finds "Download" in it, and the
     * dialog re-evaluates the handlers itself when it opens.
     *
     * @return array{url: string|null, attributes: array}
     */
    private static function link(File $file): array
    {
        $baseFile = $file->baseFile;

        if ($baseFile === null) {
            return ['url' => null, 'attributes' => []];
        }

        $handlers = FileHandlerCollection::getByType([
            FileHandlerCollection::TYPE_VIEW,
            FileHandlerCollection::TYPE_EXPORT,
            FileHandlerCollection::TYPE_EDIT,
            FileHandlerCollection::TYPE_IMPORT,
        ], $baseFile);

        $downloadOnly = count($handlers) === 1 && $handlers[0] instanceof DownloadFileHandler;

        if ($downloadOnly) {
            return [
                'url' => $baseFile->getUrl([], true),
                'attributes' => array_merge(
                    ['target' => '_blank'],
                    FileDownload::getFileDataAttributes($baseFile),
                ),
            ];
        }

        return [
            'url' => Url::to(['/file/view', 'guid' => $baseFile->guid], true),
            'attributes' => ['data-bs-target' => '#globalModal'],
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
