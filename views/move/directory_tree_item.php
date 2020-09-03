<?php

use humhub\modules\cfiles\models\Folder;
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2017 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 *
 */

/* @var $this \humhub\modules\ui\view\components\View */
/* @var $folder [] */
?>

<li>
    <span class='selectable' data-id="<?= $folder['folder']->id ?>"><?= $folder['folder']->title ?></span>
    <?php if (!empty($folder['subfolders'])) : ?>
        <ul>
            <?php foreach ($folder['subfolders'] as $subfolder) : ?>
                <?= $this->render('directory_tree_item', ['folder' => $subfolder])?>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</li>