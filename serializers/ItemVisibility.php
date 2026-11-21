<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2026 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\cfiles\serializers;

use humhub\modules\content\models\Content;

/**
 * The visibility of a file or folder on the wire: a named value (`private`, `public`), as
 * `/api/v2` has every enum, never the stored integer — both in what the API answers and in
 * what a write takes.
 *
 * @since 1.0
 */
final class ItemVisibility
{
    public const NAMES = [
        Content::VISIBILITY_PRIVATE => 'private',
        Content::VISIBILITY_PUBLIC => 'public',
    ];

    /**
     * The name of a stored visibility.
     */
    public static function name(int $visibility): ?string
    {
        return self::NAMES[$visibility] ?? null;
    }

    /**
     * The stored visibility a name stands for, null for anything that is no name of one.
     */
    public static function value(mixed $name): ?int
    {
        $value = is_string($name) ? array_search($name, self::NAMES, true) : false;

        return $value === false ? null : $value;
    }
}
