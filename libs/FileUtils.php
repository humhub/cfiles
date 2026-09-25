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
            'icon' => 'file-code',
        ],
        'archive' => [
            'ext' => [
                'zip',
                'rar',
                'gz',
                'tar',
            ],
            'icon' => 'file-zip',
        ],
        'audio' => [
            'ext' => [
                'mp3',
                'wav',
            ],
            'icon' => 'file-music',
        ],
        'excel' => [
            'ext' => [
                'xls',
                'xlsx',
            ],
            'icon' => 'file-spreadsheet',
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
            'icon' => 'photo',
        ],
        'pdf' => [
            'ext' => [
                'pdf',
            ],
            'icon' => 'file-type-pdf',
        ],
        'powerpoint' => [
            'ext' => [
                'ppt',
                'pptx',
            ],
            'icon' => 'presentation',
        ],
        'text' => [
            'ext' => [
                'txt',
                'log',
                'md',
            ],
            'icon' => 'file-text',
        ],
        'video' => [
            'ext' => [
                'mp4',
                'mpeg',
                'swf',
            ],
            'icon' => 'movie',
        ],
        'word' => [
            'ext' => [
                'doc',
                'docx',
            ],
            'icon' => 'file-type-doc',
        ],
        'default' => [
            'ext' => [],
            'icon' => 'file',
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
