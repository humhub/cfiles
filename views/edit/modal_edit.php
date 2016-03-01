<?php use yii\helpers\Html; use humhub\compat\CActiveForm; ?>

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
        </div>

        <div class="modal-footer">
            <?php echo \humhub\widgets\AjaxButton::widget([ 'label'=> Yii::t('CfilesModule.base', 'Save'), 'ajaxOptions' => [ 'type' => 'POST', 'beforeSend' => new yii\web\JsExpression('function(){ setModalLoader(); }'), 'success' => new yii\web\JsExpression('function(html){ $("#globalModal").html(html); }'), 'url' => $contentContainer->createUrl('/cfiles/edit', [ 'fid' => $currentFolderId, 'id' => $folder->id ]) ], 'htmlOptions' => [ 'class' => 'btn btn-primary' ] ]); ?>
            <button type="button" class="btn btn-primary"
                data-dismiss="modal">
                <?php echo Yii::t( 'CfilesModule.base', 'Close'); ?>
            </button>

        </div>
        <?php CActiveForm::end()?>
    </div>
</div>