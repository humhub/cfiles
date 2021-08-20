<?php
/* @var $menus array[] */
?>

<?php foreach ($menus as $menuId => $menuItems) : ?>
<ul id="<?= $menuId ?>" class="contextMenu dropdown-menu" role="menu" style="display: none">
    <?php foreach ($menuItems as $menuItem) : ?>
        <?php if (is_array($menuItem)): ?>
            <li><a tabindex="-1" href="#" data-action="<?= $menuItem['action'] ?>"<?php if ($menuItem['editable']) : ?> class="editableOnly"<?php endif; ?>><i class="fa fa-<?= $menuItem['icon'] ?>"></i><?= $menuItem['label'] ?></a></li>
        <?php elseif ($menuItem === 'separator') : ?>
            <li role="separator" class="divider editableOnly"></li>
        <?php endif; ?>
    <?php endforeach; ?>
</ul>
<?php endforeach; ?>
