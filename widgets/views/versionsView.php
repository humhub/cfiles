<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2021 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

use humhub\modules\cfiles\models\File;
use humhub\modules\cfiles\widgets\VersionItem;
use humhub\modules\file\models\File as BaseFile;

/* @var File $file */
/* @var BaseFile[] $versions */
/* @var int $currentVersion */
?>
<table class="table table-hover">
    <thead>
        <tr>
            <th><?= Yii::t('CfilesModule.base', 'Time'); ?></th>
            <th><?= Yii::t('CfilesModule.base', 'Author'); ?></th>
            <th class="text-right"><?= Yii::t('CfilesModule.base', 'Size'); ?></th>
            <th class="text-center"><?= Yii::t('CfilesModule.base', 'Actions'); ?></th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($versions as $versionBaseFile) : ?>
        <?= VersionItem::widget([
            'file' => $versionBaseFile,
            'isCurrent' => ($currentVersion == $versionBaseFile->id),
            'revertUrl' => $file->getVersionsUrl($versionBaseFile->id),
        ]) ?>
    <?php endforeach; ?>
    </tbody>
</table>
