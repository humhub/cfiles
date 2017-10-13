<?php

use humhub\libs\Html;
?>

<div class="pull-left" style="padding-right:12px">
    <i class="fa fa-folder-o fa-fw" style="font-size:40px"></i>
</div>

<strong><?= Html::a(Html::encode($folder->title), $folderUrl); ?></strong><br />
<br />

<?= Html::a(Yii::t('CfilesModule.base', 'Open file folder'), $folderUrl, ['class' => 'btn btn-sm btn-default', 'data-ui-loader' => '']); ?>

<div class="clearfix"></div>