<?php

namespace humhub\modules\cfiles\libs;

/**
 * Maps a file extension onto the FontAwesome icon the stream and the search results show
 * for it.
 *
 * Not replaceable by the core's `MimeHelper`: that one answers the `mime-*` CSS classes the
 * platform's own file widgets are styled with, which is a different vocabulary. The API uses
 * `MimeHelper` (see `serializers\FileSerializer`) because a client renders its own icons;
 * this stays for the server-rendered stream entry.
 *
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
                'xml',
            ],
            'icon' => 'fa-file-code-o',
        ],
        'archive' => [
            'ext' => [
                'zip',
                'rar',
                'gz',
                'tar',
            ],
            'icon' => 'fa-file-archive-o',
        ],
        'audio' => [
            'ext' => [
                'mp3',
                'wav',
            ],
            'icon' => 'fa-file-audio-o',
        ],
        'excel' => [
            'ext' => [
                'xls',
                'xlsx',
            ],
            'icon' => 'fa-file-excel-o',
        ],
        'image' => [
            'ext' => [
                'jpg',
                'jpeg',
                'gif',
                'bmp',
                'svg',
                'tiff',
                'png',
            ],
            'icon' => 'fa-file-image-o',
        ],
        'pdf' => [
            'ext' => [
                'pdf',
            ],
            'icon' => 'fa-file-pdf-o',
        ],
        'powerpoint' => [
            'ext' => [
                'ppt',
                'pptx',
            ],
            'icon' => 'fa-file-powerpoint-o',
        ],
        'text' => [
            'ext' => [
                'txt',
                'log',
                'md',
            ],
            'icon' => 'fa-file-text-o',
        ],
        'video' => [
            'ext' => [
                'mp4',
                'mpeg',
                'swf',
            ],
            'icon' => 'fa-file-video-o',
        ],
        'word' => [
            'ext' => [
                'doc',
                'docx',
            ],
            'icon' => 'fa-file-word-o',
        ],
        'default' => [
            'ext' => [],
            'icon' => 'fa-file-o',
        ],
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
        $ext = strtolower($ext);
        foreach (self::$map as $info) {
            if (in_array($ext, $info['ext'])) {
                return $info['icon'];
            }
        }
        return self::$map['default']['icon'];
    }

}
