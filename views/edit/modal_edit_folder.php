<?php use yii\helpers\Html; use humhub\compat\CActiveForm; ?>

<div class="content_edit"
    id="cfiles_edit_folder<?php echo $folder->id;?>">
    <div class="modal-dialog modal-dialog-small animated fadeIn">
        <div class="modal-content">
            <?php $form=CActiveForm::begin(); ?>
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal"
                    aria-hidden="true">&times;</button>
                <h4 class="modal-title" id="myModalLabel">
                    <?php if ($folder->isNewRecord): ?>
                        <?php echo Yii::t('CfilesModule.base', '<strong>Create</strong> folder'); ?>
                    <?php else: ?>
                        <?php echo Yii::t('CfilesModule.base', '<strong>Edit</strong> folder'); ?>
                    <?php endif; ?>
                </h4>
            </div>

            <div class="modal-body">
                <br />
                <?php echo $form->field($folder, 'title'); ?>
                <?php echo $form->field($folder, 'description'); ?>
            </div>

            <div class="modal-footer">
                <?php
                
                // echo \humhub\widgets\AjaxButton::widget([
                // 'label' => Yii::t('CfilesModule.base', 'Save'),
                // 'ajaxOptions' => [
                // 'type' => 'POST',
                // 'beforeSend' => new yii\web\JsExpression('function(){ setModalLoader(); }'),
                // 'success' => new yii\web\JsExpression('function(html){ $("#globalModal").html(html); }'),
                // 'url' => $contentContainer->createUrl('/cfiles/edit/folder', [
                // 'fid' => $currentFolderId,
                // 'id' => $folder->getItemId(),
                // 'fromWall' => $fromWall
                // ])
                // ],
                // 'htmlOptions' => [
                // 'class' => 'btn btn-primary'
                // ]
                // ]);
                ?>
                <a href="#" class="btn btn-primary"
                    data-action-click="cfiles.editFiles"
                    data-action-url="<?=$contentContainer->createUrl('/cfiles/edit/folder', ['fid' => $currentFolderId,'id' => $folder->getItemId(),'fromWall' => $fromWall])?>">
                    <?= Yii::t('CfilesModule.base', 'Save'); ?>
                </a>
                <button type="button" class="btn btn-primary"
                    data-dismiss="modal">
                    <?php echo Yii::t( 'CfilesModule.base', 'Close'); ?>
                </button>

            </div>
            <?php CActiveForm::end()?>
        </div>
    </div>
</div>