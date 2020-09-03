<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2017 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 *
 */

use humhub\modules\cfiles\models\Folder;

/* @var $this \humhub\modules\ui\view\components\View */
/* @var $root Folder */

$folderList = Folder::getFolderList($root);

?>

<div id="cfiles-directory-list" data-ui-widget="cfiles.DirectoryList" data-ui-init class="directory-list">
    <div class="selectable" data-id="<?= $root->id; ?>"><?= Yii::t('CfilesModule.base', '/ (root)'); ?></div>
    <ul>
        <?php  foreach (Folder::getFolderList($root) as $folder) :?>
            <?= $this->render('directory_tree_item', ['folder' => $folder]); ?>
        <?php endforeach ?>
    </ul>
</div>