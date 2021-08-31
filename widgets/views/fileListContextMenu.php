<?php

use humhub\modules\content\widgets\LegacyWallEntryControlLink;
use humhub\modules\ui\menu\MenuEntry;

/* @var $entries MenuEntry[] */
?>

<div data-ui-widget="stream.StreamEntry">
    <ul class="contextMenu dropdown-menu">
        <?php foreach ($entries as $entry) : ?>
            <?php if($entry instanceof LegacyWallEntryControlLink) : ?>
                <?= $entry->render() ?>
            <?php else: ?>
                <li>
                    <?= $entry->render() ?>
                </li>
            <?php endif; ?>
        <?php endforeach; ?>
    </ul>
</div>