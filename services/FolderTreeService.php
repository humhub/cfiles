<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\cfiles\services;

use humhub\modules\cfiles\models\Folder;
use humhub\modules\content\components\ContentContainerActiveRecord;
use humhub\modules\content\models\Content;
use humhub\modules\space\models\Space;
use humhub\modules\user\models\User;
use yii\db\ActiveQuery;

/**
 * Every container's folder tree begins with one root folder, and this owns it.
 *
 * The root is not an ordinary folder: it is created on demand rather than by anyone, it
 * belongs to the container's owner rather than to whoever happened to trigger its creation
 * (so it survives that user being deleted), it cannot be renamed, moved or deleted, and it
 * is never listed as a child of anything. None of that is a property of a folder RECORD, so
 * none of it lives on the model.
 *
 * @since 1.0
 */
class FolderTreeService
{
    public const ROOT_TITLE = 'Root';

    public const ROOT_DESCRIPTION = 'The root folder is the entry point that contains all available files.';

    /**
     * The container's root folder, or null when it has none yet.
     */
    public static function getRoot(ContentContainerActiveRecord $container): ?Folder
    {
        return Folder::find()
            ->contentContainer($container)
            ->andWhere(['cfiles_folder.type' => Folder::TYPE_FOLDER_ROOT])
            ->one();
    }

    /**
     * The container's root folder, creating it if this is the first time anyone asked.
     */
    public static function getOrInitRoot(ContentContainerActiveRecord $container): ?Folder
    {
        return static::getRoot($container) ?? static::initRoot($container);
    }

    /**
     * Creates the container's root folder.
     *
     * @return Folder|null null when one already exists or the save failed — the caller that
     *         wants the folder either way asks {@see self::getOrInitRoot()} instead.
     */
    public static function initRoot(ContentContainerActiveRecord $container): ?Folder
    {
        if (static::getRoot($container) !== null) {
            return null;
        }

        $root = new Folder($container, Content::VISIBILITY_PUBLIC, [
            'type' => Folder::TYPE_FOLDER_ROOT,
            'title' => self::ROOT_TITLE,
            'description' => self::ROOT_DESCRIPTION,
        ]);

        $root->content->created_by = static::getContainerOwnerId($container);
        // Nobody wants a stream entry announcing that a folder tree now exists.
        $root->silentContentCreation = true;

        return $root->save() ? $root : null;
    }

    /**
     * Makes sure the container has a root folder and that it belongs to the container owner.
     *
     * Called wherever a container gains the module (space created, module enabled, profile
     * created) and before the browser renders, so a container that predates any of that
     * repairs itself on first use.
     */
    public static function ensureRootStructure(ContentContainerActiveRecord $container): void
    {
        $root = static::getOrInitRoot($container);

        if ($root !== null) {
            static::ensureRootOwner($root, $container);
        }
    }

    /**
     * Re-owns a root folder to the container owner.
     *
     * A root created by an ordinary member used to be deleted along with that member's
     * account, taking the container's whole tree with it.
     *
     * @return bool whether the owner actually changed
     */
    public static function ensureRootOwner(?Folder $root, ContentContainerActiveRecord $container): bool
    {
        if ($root === null || $root->content === null) {
            return false;
        }

        $ownerId = static::getContainerOwnerId($container);

        if ($ownerId === null || (int)$root->content->created_by === (int)$ownerId) {
            return false;
        }

        $root->content->created_by = $ownerId;

        return (bool)$root->content->save(false, ['created_by']);
    }

    /**
     * The readable subfolders of a folder.
     *
     * The root is filtered out explicitly: it carries a `parent_folder_id` of its own in some
     * historic data, and it is the tree rather than a node in it.
     */
    public static function subFolderQuery(Folder $parent, array $orderBy = ['title' => SORT_ASC]): ActiveQuery
    {
        return Folder::find()
            ->contentContainer($parent->content->container)
            ->readable()
            ->andWhere(['cfiles_folder.parent_folder_id' => $parent->id])
            ->andWhere([
                'or',
                ['cfiles_folder.type' => null],
                ['<>', 'cfiles_folder.type', Folder::TYPE_FOLDER_ROOT],
            ])
            ->orderBy($orderBy);
    }

    /**
     * Who a container's content belongs to when nobody in particular created it.
     */
    public static function getContainerOwnerId(ContentContainerActiveRecord $container): ?int
    {
        if ($container instanceof User) {
            return (int)$container->id;
        }

        if ($container instanceof Space) {
            return (int)$container->created_by;
        }

        return null;
    }
}
