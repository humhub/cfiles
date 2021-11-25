<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2021 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

use humhub\widgets\Button;

/* @var string $versionsRowsHtml */
/* @var string|false $nextPageVersionsUrl */
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
        <?= $versionsRowsHtml ?>
    </tbody>
</table>
<?php if ($nextPageVersionsUrl) : ?>
    <div class="text-center">
        <br>
        <?= Button::defaultType(Yii::t('CfilesModule.base', 'Show older versions'))
            ->icon('chevron-down')
            ->action('cfiles.loadNextPageVersions', $nextPageVersionsUrl)
            ->xs(); ?>
    </div>
<?php endif; ?>
