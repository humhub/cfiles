<?php

use yii\helpers\Html;

/* @var $folder  \humhub\modules\cfiles\models\Folder */

if($folder->content->isPublic()) {
    $visibilityIcon = 'fa-unlock-alt';
    $visibilityTitle = Yii::t('CfilesModule.base', 'This folder is public.');
} else {
    $visibilityIcon = 'fa-lock';
    $visibilityTitle = Yii::t('CfilesModule.base', 'This folder is private.');
}

$visibilityIcon = $folder->content->isPublic() ? 'fa-unlock-alt': 'fa-lock' ;
?>

<div class="panel panel-default" style="margin-bottom:10px;">
    <div class="panel-head">
        <ol id="cfiles-crumb" class="breadcrumb" dir="ltr">
            <?php foreach ($folder->getCrumb() as $parentFolder): ?>
                <?php $url = $contentContainer->createUrl('/cfiles/browse/index', ['fid' => $parentFolder->id]); ?>
                <li>
                    <a href="<?= $url ?>">
                        <?= $parentFolder->isRoot() ? '<i class="fa fa-home fa-lg fa-fw"></i>' : Html::encode($parentFolder->title); ?>
                    </a>
                </li>
            <?php endforeach; ?>
            <?php if(!$folder->isRoot()) : ?>
                <li class="folder-visibility tt" data-placement="left"  title="<?= $folder->getVisibilityTitle() ?>">
                    <i class="fa <?= $visibilityIcon ?> fa-lg"></i>
                </li>
            <?php endif; ?>
        </ol>
    </div>
</div>
