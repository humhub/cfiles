<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2017 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 *
 */
?>

<?php if ($canWrite || $zipEnabled): ?>
    <div class="selectedOnly pull-left" style="margin-right:2px;">
        <div class="btn-group">
            <button class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
                (<span class='chkCnt'></span>) <?= Yii::t('CfilesModule.base', 'Selected items...') ?> <span class="caret"></span>
            </button>
            <ul class="dropdown-menu">
                <?php if ($canWrite): ?>
                    <li>
                        <a href="#" class="selectedOnly filedelete-button" style="display:none"
                           data-action-click="deleteSelection"
                           data-action-submit
                           data-action-url="<?= $deleteSelectionUrl ?>">
                            <i class="fa fa-trash"></i> <?= Yii::t('CfilesModule.base', 'Delete') ?>
                        </a>
                    </li>
                    <li>
                        <a href="#" class="selectedOnly filemove-button" style="display:none"
                           data-action-click="cfiles.move"
                           data-action-submit
                           data-fid="<?= $folder->id ?>"
                           data-action-url="<?= $moveSelectionUrl ?>">
                            <i class="fa fa-arrows"></i> <?= Yii::t('CfilesModule.base', 'Move') ?>
                        </a>
                    </li>
                <?php endif; ?>
                <?php if ($zipEnabled) : ?>
                    <li>
                        <a href="#" class="selectedOnly" style="display:none"
                           data-action-click="zipSelection"
                           data-action-submit
                           data-action-url="<?= $zipSelectionUrl; ?>">

                            <i class="fa fa-download"></i> <?= Yii::t('CfilesModule.base', 'ZIP selected') ?>
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
<?php endif; ?>