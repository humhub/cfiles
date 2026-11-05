<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\cfiles\assets;

use humhub\components\assets\VueAssetBundle;
use humhub\modules\activity\assets\ActivityVueAsset;
use humhub\modules\comment\assets\CommentVueAsset;
use humhub\modules\content\assets\ContentVueAsset;
use humhub\modules\file\assets\FileVueAsset;
use humhub\modules\like\assets\LikeVueAsset;
use humhub\modules\space\assets\SpaceVueAsset;
use humhub\modules\topic\assets\TopicVueAsset;
use humhub\modules\user\assets\UserVueAsset;

/**
 * Compiled Vue components of the files module.
 *
 * Source: `vue/`, built with `grunt build-vue --module=cfiles` from a core checkout. The
 * artifact is committed — see docs/develop/ui-js-vuejs.md in core.
 *
 * @since 1.0
 */
class CfilesVueAsset extends VueAssetBundle
{
    /**
     * @inheritdoc
     */
    public $css = [
        'css/cfiles.css',
    ];

    /**
     * @inheritdoc
     */
    public $depends = [
        // <ContentControls> — the row context menu.
        ContentVueAsset::class,
        // <UserImage> — the creator in the details —, and <UserFilterControl>, the filter bar's
        // `user` type (the Author filter).
        UserVueAsset::class,
        // <SpaceImage> — a space tile's badge on the global files page —, and
        // <SpaceFilterControl>, the filter bar's `space` type (its Space filter).
        SpaceVueAsset::class,
        // <TopicFilterControl>, the filter bar's `topic` type (the Topic filter).
        TopicVueAsset::class,
        // <LikeButton> on the details' Discussion tab — the platform's own island, rendered by
        // this module without a line of like logic of its own.
        LikeVueAsset::class,
        // <CommentSection>, <ContentActivity> and <FileVersions> — the details' Discussion and
        // Activity tabs.
        CommentVueAsset::class,
        ActivityVueAsset::class,
        FileVueAsset::class,
    ];
}
