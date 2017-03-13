<?php

use yii\helpers\Html;
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
        </ol>
    </div>
</div>
