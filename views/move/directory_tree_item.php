<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2017 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 *
 */

use humhub\components\View;
use humhub\helpers\Html;

/* @var $this View */
/* @var $folder array */
?>

<li>
    <span class='selectable' data-id="<?= $folder['folder']->id ?>"><?= Html::encode($folder['folder']->title) ?></span>
    <?php if (!empty($folder['subfolders'])) : ?>
        <ul>
            <?php foreach ($folder['subfolders'] as $subfolder) : ?>
                <?= $this->render('directory_tree_item', ['folder' => $subfolder])?>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</li>
