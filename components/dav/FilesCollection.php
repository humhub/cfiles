<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2026 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\cfiles\components\dav;

use humhub\components\listing\ListContext;
use humhub\modules\cfiles\components\GlobalFolderList;
use humhub\modules\content\components\ContentContainerActiveRecord;
use humhub\modules\space\models\Space;
use humhub\modules\user\models\User;
use Sabre\DAV\Collection;
use Sabre\DAV\Exception\NotFound;

/**
 * `/dav/files/`: a folder per space of the user — those of the global files page
 * ({@see GlobalFolderList::scopeQuery()}) — named by the space's URL (`/dav/files/sales/`),
 * and one for the user's own profile, named `~` and the username (`/dav/files/~jane/`), when
 * the module is enabled there.
 *
 * The URL of a space rather than its name: names are not unique and change on a rename, a
 * path must be neither. Other spaces and profiles are not reachable here, also not by their
 * path.
 *
 * @since 1.0
 */
class FilesCollection extends Collection
{
    public const NAME = 'files';

    public const PROFILE_PREFIX = '~';

    /**
     * @var array<string, ContentContainerActiveRecord>|null by segment
     */
    private ?array $containers = null;

    public function __construct(private readonly User $user)
    {
    }

    /**
     * @inheritdoc
     */
    public function getName()
    {
        return self::NAME;
    }

    /**
     * @inheritdoc
     */
    public function getChildren()
    {
        $children = [];
        foreach ($this->containers() as $segment => $container) {
            $children[] = new LevelNode($container, null, (string)$segment);
        }

        return $children;
    }

    /**
     * @inheritdoc
     */
    public function getChild($name)
    {
        $container = $this->containers()[$name] ?? null;
        if ($container === null) {
            throw new NotFound('No such space: ' . $name);
        }

        return new LevelNode($container, null, $name);
    }

    /**
     * @inheritdoc
     */
    public function childExists($name)
    {
        return isset($this->containers()[$name]);
    }

    /**
     * The path segment of a container below `/dav/files/`.
     */
    public static function segment(ContentContainerActiveRecord $container): string
    {
        if ($container instanceof User) {
            return self::PROFILE_PREFIX . $container->username;
        }

        return $container instanceof Space && !empty($container->url) ? $container->url : $container->guid;
    }

    /**
     * @return array<string, ContentContainerActiveRecord> by segment
     */
    private function containers(): array
    {
        if ($this->containers !== null) {
            return $this->containers;
        }

        $this->containers = [];
        $spaces = GlobalFolderList::scopeQuery(new ListContext($this->user))->orderBy(['space.name' => SORT_ASC])->all();
        foreach ($spaces as $space) {
            $this->containers[self::segment($space)] = $space;
        }

        if ($this->user->moduleManager->isEnabled('cfiles')) {
            $this->containers[self::segment($this->user)] = $this->user;
        }

        return $this->containers;
    }
}
