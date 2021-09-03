<?php

use humhub\modules\ui\menu\MenuEntry;

/* @var $entries MenuEntry[] */
?>

<div data-ui-widget="stream.StreamEntry">
    <ul class="contextMenu dropdown-menu">
        <?php foreach ($entries as $entry) : ?>
            <li><?= $entry->render() ?></li>
        <?php endforeach; ?>
    </ul>
</div>