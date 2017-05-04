<?php

use humhub\compat\CActiveForm;

function renderFolder($folder)
{
    echo "<li><span class='selectable' id='" . $folder['folder']->id . "'>" . $folder['folder']->title . "</span>";
    if (!empty($folder['subfolders'])) {
        echo "<ul>";
        foreach ($folder['subfolders'] as $subfolder) {
            renderFolder($subfolder);
        }
        echo "</ul>";
    }
    echo "</li>";
}
?>

<?php \humhub\widgets\ModalDialog::begin([
    'header' => Yii::t('CfilesModule.base', '<strong>Move</strong> files'),
    'size' => 'small',
    'animation' => 'fadeIn'
]) ?>

    <?php $form = CActiveForm::begin(); ?>
        <div class="modal-body">
            <?php if (!empty($errorMsgs)) : ?>
                <div class="alert alert-danger">
                    <ul>
                        <?php foreach ($errorMsgs as $error) : ?>
                           <?= "<li>$error</li>" ?>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            <br />
            <div id="cfiles-directory-list" data-ui-widget="cfiles.DirectoryList" data-ui-init class="directory-list">
                <div class="selectable" id="<?php echo $rootFolder->id; ?>"><?= Yii::t('CfilesModule.base', '/ (root)'); ?></div>
                <ul>
                    <?php
                    foreach ($folders as $dir) :
                        renderFolder($dir);
                    endforeach
                    ;
                    ?>
                </ul>
            </div>

            <input id="input-hidden-selectedFolder" type="hidden" name="destfid" value="<?= $selectedFolderId ?>" />

            <?php
            if (is_array($selectedItems)) {
                foreach ($selectedItems as $index => $item) {
                    echo "<input class='input-hidden-selectedItem' type='hidden' name='selected[]' value='$item'/>";
                }
            }
            ?>
            
        </div>
        <div class="modal-footer">
                <a href="#" class="btn btn-primary"
                   data-action-click="ui.modal.submit"
                   data-ui-loader
                   data-action-url="<?= $contentContainer->createUrl('/cfiles/move', []) ?>">
                       <?= Yii::t('CfilesModule.base', 'Save'); ?>
                </a>
            
                <button type="button" class="btn btn-primary"
                        data-dismiss="modal"><?php echo Yii::t('CfilesModule.base', 'Close'); ?></button>

            </div>
    <?php CActiveForm::end() ?>

<?php \humhub\widgets\ModalDialog::end()?>