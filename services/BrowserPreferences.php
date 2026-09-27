<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\cfiles\services;

use humhub\modules\cfiles\components\FolderList;
use humhub\modules\cfiles\Module;
use humhub\modules\user\models\User;
use Yii;

/**
 * What the file browser remembers per user: the sort chosen last and the view (tiles or list).
 * Read and written by the browser's entry points (the page, the items endpoint, the
 * preferences endpoint) — the list itself stays free of side effects.
 *
 * The browser has no settings screen, and neither belongs in a shared link, so they are user
 * settings of the module. A guest has none: reads fall back to the module's defaults, writes
 * are dropped.
 *
 * The sort is stored as its key (`sort`). Before the list had sort keys it was stored as a
 * column and a direction (`defaultSort`/`defaultOrder`); those are mapped to the key they stand
 * for when no key is stored yet ({@see FolderList::keyOfOrder()}), so no migration is needed.
 *
 * @since 1.0
 */
class BrowserPreferences
{
    private const SORT = 'sort';
    private const LEGACY_SORT = 'defaultSort';
    private const LEGACY_ORDER = 'defaultOrder';
    private const VIEW = 'defaultView';

    public function __construct(private readonly ?User $user)
    {
    }

    /**
     * The sort the user chose last, `null` when there is none. Whether the list still knows
     * it is the list's to decide ({@see FolderList::$storedSort}).
     */
    public function sort(): ?string
    {
        $settings = $this->settings();

        if ($settings === null) {
            return null;
        }

        $key = $settings->get(self::SORT);

        if ($key === null) {
            $column = $settings->get(self::LEGACY_SORT);
            $key = $column === null ? null : FolderList::keyOfOrder((string)$column, (int)$settings->get(self::LEGACY_ORDER, SORT_ASC));
        }

        return is_string($key) && $key !== '' && $key !== FolderList::SORT_DEFAULT ? $key : null;
    }

    /**
     * Remembers a sort; `null` forgets it (the default order applies again).
     */
    public function setSort(?string $key): void
    {
        $settings = $this->settings();

        if ($settings === null) {
            return;
        }

        if ($key === null) {
            foreach ([self::SORT, self::LEGACY_SORT, self::LEGACY_ORDER] as $name) {
                if ($settings->get($name) !== null) {
                    $settings->delete($name);
                }
            }

            return;
        }

        if ($settings->get(self::SORT) !== $key) {
            $settings->set(self::SORT, $key);
        }
    }

    /**
     * The view the user chose last, else the module's default — one of
     * {@see FolderListingService::VIEWS}.
     */
    public function view(): string
    {
        foreach ([$this->settings()?->get(self::VIEW), $this->module()->defaultView] as $view) {
            if (is_string($view) && isset(FolderListingService::VIEWS[$view])) {
                return $view;
            }
        }

        return 'list';
    }

    /**
     * @param string $view one of {@see FolderListingService::VIEWS}
     */
    public function setView(string $view): void
    {
        $this->settings()?->set(self::VIEW, $view);
    }

    private function settings()
    {
        return $this->user === null ? null : $this->module()->settings->user($this->user);
    }

    private function module(): Module
    {
        /** @var Module $module */
        $module = Yii::$app->getModule('cfiles');

        return $module;
    }
}
