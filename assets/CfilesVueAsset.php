<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\cfiles\assets;

use humhub\assets\CoreApiAsset;
use humhub\assets\CoreVueAsset;
use humhub\components\assets\AssetBundle;
use humhub\modules\content\assets\ContentVueAsset;
use humhub\modules\like\assets\LikeVueAsset;
use humhub\modules\user\assets\UserVueAsset;

/**
 * Compiled Vue components of the files module.
 *
 * Source: `vue/`, built with `node vue.build.mjs --module <path-to-cfiles>` from a core
 * checkout. The artifact is committed — see docs/develop/ui-js-vuejs.md in core.
 *
 * @since 1.0
 */
class CfilesVueAsset extends AssetBundle
{
    /**
     * @inheritdoc
     */
    public $sourcePath = '@cfiles/resources';

    /**
     * @inheritdoc
     */
    public $css = [
        'css/cfiles.css',
    ];

    /**
     * @inheritdoc
     */
    public $js = [
        'js/humhub.cfiles.vue.js',
    ];

    /**
     * @inheritdoc
     */
    public $depends = [
        CoreApiAsset::class,
        // <UiModal>, <DropdownMenu> and the form suite live in the core component set and are
        // referenced by tag only, so they must be registered before this bundle's own script
        // runs.
        CoreVueAsset::class,
        // <ContentControls> — the row context menu.
        ContentVueAsset::class,
        // <UserImage> — the creator avatar on every row.
        UserVueAsset::class,
        // <LikeButton> on every row — the platform's own island, rendered by this module
        // without a line of like logic of its own.
        LikeVueAsset::class,
    ];
}
