<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2026 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\cfiles\tests\codeception\unit;

use humhub\modules\cfiles\Module;
use humhub\modules\cfiles\services\BrowserPreferences;
use tests\codeception\_support\HumHubDbTestCase;
use Yii;

/**
 * What the file browser remembers per user ({@see BrowserPreferences}).
 */
class BrowserPreferencesTest extends HumHubDbTestCase
{
    public function testASortIsRememberedAndForgotten()
    {
        $preferences = new BrowserPreferences($this->becomeUser('Admin'));

        $this->assertNull($preferences->sort());

        $preferences->setSort('nameDesc');
        $this->assertSame('nameDesc', $preferences->sort());

        $preferences->setSort(null);
        $this->assertNull($preferences->sort());
    }

    /**
     * Before the sort keys the choice was stored as a column and a direction; it is mapped on
     * read, so no migration is needed.
     */
    public function testAnOrderStoredAsColumnAndDirectionIsMappedToItsKey()
    {
        $user = $this->becomeUser('Admin');
        /** @var Module $module */
        $module = Yii::$app->getModule('cfiles');
        $settings = $module->settings->user($user);
        $settings->set('defaultSort', 'updatedAt');
        $settings->set('defaultOrder', SORT_DESC);

        $this->assertSame('newest', (new BrowserPreferences($user))->sort());

        // A column no key stands for is no sort.
        $settings->set('defaultSort', 'downloadCount');
        $this->assertNull((new BrowserPreferences($user))->sort());

        // Forgetting the sort forgets the old one too.
        $settings->set('defaultSort', 'size');
        (new BrowserPreferences($user))->setSort(null);
        $this->assertNull((new BrowserPreferences($user))->sort());
    }

    public function testAGuestHasNoPreferences()
    {
        $preferences = new BrowserPreferences(null);

        $preferences->setSort('newest');
        $preferences->setView('tiles');

        $this->assertNull($preferences->sort());
        $this->assertSame('list', $preferences->view());
    }
}
