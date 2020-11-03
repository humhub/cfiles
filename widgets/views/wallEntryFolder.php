<?php

use humhub\libs\Html;
use humhub\modules\cfiles\models\Folder;

/* @var $folder Folder */
/* @var $folderUrl string */
?>

<strong><?= Html::a(Html::encode($folder->title), $folderUrl); ?></strong><br />
<br />

<?= Html::a(Yii::t('CfilesModule.base', 'Open file folder'), $folderUrl, ['class' => 'btn btn-sm btn-default', 'data-ui-loader' => '']); ?>

<div class="clearfix"></div>