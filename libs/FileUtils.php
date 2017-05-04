<?php

namespace humhub\modules\cfiles\libs;

use humhub\modules\file\models\File;
use humhub\modules\file\libs\ImageConverter;
use humhub\modules\comment\models\Comment;
use humhub\modules\content\models\Content;
use humhub\modules\post\models\Post;

/**
 * This is a utility lib for files.
 * 
 * @package humhub.modules.gallery.libs
 * @since 1.0
 * @author Sebastian Stumpf
 */
class FileUtils
{

    public static $map = [
        'code' => [
            'ext' => [
                'html',
                'cmd',
                'bat',
                'xml'
            ],
            'icon' => 'fa-file-code-o'
        ],
        'archive' => [
            'ext' => [
                'zip',
                'rar',
                'gz',
                'tar'
            ],
            'icon' => 'fa-file-archive-o'
        ],
        'audio' => [
            'ext' => [
                'mp3',
                'wav'
            ],
            'icon' => 'fa-file-audio-o'
        ],
        'excel' => [
            'ext' => [
                'xls',
                'xlsx'
            ],
            'icon' => 'fa-file-excel-o'
        ],
        'image' => [
            'ext' => [
                'jpg',
                'gif',
                'bmp',
                'svg',
                'tiff',
                'png'
            ],
            'icon' => 'fa-file-image-o'
        ],
        'pdf' => [
            'ext' => [
                'pdf'
            ],
            'icon' => 'fa-file-pdf-o'
        ],
        'powerpoint' => [
            'ext' => [
                'ppt',
                'pptx'
            ],
            'icon' => 'fa-file-powerpoint-o'
        ],
        'text' => [
            'ext' => [
                'txt',
                'log',
                'md'
            ],
            'icon' => 'fa-file-text-o'
        ],
        'video' => [
            'ext' => [
                'mp4',
                'mpeg',
                'swf'
            ],
            'icon' => 'fa-file-video-o'
        ],
        'word' => [
            'ext' => [
                'doc',
                'docx'
            ],
            'icon' => 'fa-file-word-o'
        ],
        'default' => [
            'ext' => [],
            'icon' => 'fa-file-o'
        ]
    ];

    /**
     * Get the extensions font awesome icon class.
     *
     * @param string $ext
     *            the extension.
     * @return string the font awesome icon class for this extension.
     */
    public static function getIconClassByExt($ext = '')
    {
        foreach (self::$map as $type => $info) {
            if (in_array(strtolower($ext), $info['ext'])) {
                return $info['icon'];
            }
        }
        return self::$map['default']['icon'];
    }

    /**
     * Sanitize a filename.
     *
     * @param string $file_name            
     * @return string
     */
    public static function sanitizeFilename($filename)
    {
        $file = new \humhub\modules\file\models\File();
        $file->file_name = $filename;
        $file->sanitizeFilename();
        return $file->file_name;
    }

    /**
     * Get the extensions type.
     *
     * @param string $ext
     *            the extension.
     * @return string the type or 'unknown'.
     */
    public static function getItemTypeByExt($ext)
    {
        foreach (self::$map as $type => $info) {
            if (in_array(strtolower($ext), $info['ext'])) {
                return $type;
            }
        }
        return 'unknown';
    }

    /**
     * Crop an image file to a square thumbnail.
     * The thumbnail will be saved with the suffix "&lt;width&gt;_thumb_square"
     * @param File $basefile the file to crop.
     * @param number $maxDimension limit maximum with/height.
     * @return string the thumbnail's url or null if an error occured.
     */
    public static function getSquareThumbnailUrlFromFile(File $basefile = null, $maxDimension = 1000)
    {
        if ($basefile === null) {
            return;
        }

        $suffix = $maxDimension . '_thumb_square';
        $originalFilename = $basefile->getStoredFilePath();
        $previewFilename = $basefile->getStoredFilePath($suffix);

        // already generated
        if (is_file($previewFilename)) {
            return $basefile->getUrl($suffix);
        }

        // Check file exists & has valid mime type
        if ($basefile->getMimeBaseType() != "image" || !is_file($originalFilename)) {
            return "";
        }

        $imageInfo = @getimagesize($originalFilename);

        // check valid image dimesions
        if (!isset($imageInfo[0]) || !isset($imageInfo[1])) {
            return "";
        }

        // Check if image type is supported
        if ($imageInfo[2] != IMAGETYPE_PNG && $imageInfo[2] != IMAGETYPE_JPEG && $imageInfo[2] != IMAGETYPE_GIF) {
            return "";
        }

        $dim = min($imageInfo[0], $imageInfo[1], $maxDimension);
        ImageConverter::Resize($originalFilename, $previewFilename, array(
            'mode' => 'force',
            'width' => $dim,
            'height' => $dim
        ));
        return $basefile->getUrl($suffix);
    }

    /**
     * Get the content model the file is connected to.
     * @param File $basefile the file.
     */
    public static function getBaseContent($file = null)
    {
        if ($file === null) {
            return null;
        }
        $searchItem = $file;
        // if the item is connected to a Comment, we have to search for the corresponding Post
        if ($file->object_model === Comment::className()) {
            $searchItem = Comment::findOne([
                        'id' => $file->object_id
            ]);
        }
        $query = Content::find();
        $query->andWhere([
            'content.object_id' => $searchItem->object_id,
            'content.object_model' => $searchItem->object_model
        ]);
        return $query->one();
    }

    /**
     * Get the post the file is connected to.
     * @param File $basefile the file.
     */
    public static function getBasePost($file = null)
    {
        if ($file === null) {
            return null;
        }
        $searchItem = $file;
        // if the item is connected to a Comment, we have to search for the corresponding Post
        if ($file->object_model === Comment::className()) {
            $searchItem = Comment::findOne([
                        'id' => $file->object_id
            ]);
        }
        $return = Post::findOne(['id' => $searchItem->object_id
        ]);
    }

}
