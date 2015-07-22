<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

$form = ActiveForm::begin([])
?>

<div class="modal-dialog modal-dialog-small animated fadeIn">
    <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
        <h4 class="modal-title" id="myModalLabel">
            <?php if ($folder->isNewRecord): ?>
                <?php echo Yii::t('CfilesModule.views_browse_editFolder', '<strong>Create</strong> folder'); ?>
            <?php else: ?>
                <?php echo Yii::t('CfilesModule.views_browse_editFolder', '<strong>Edit</strong> folder'); ?>
            <?php endif; ?>
        </h4>
    </div>
    <div class="modal-body">
        <hr/>
        <br/>
        <div class="modal-content">
            <?= $form->field($folder, 'title'); ?>
        </div>
    </div>

    <div class="modal-footer">
        <hr/>
        <br/>
        <?php
        echo \humhub\widgets\AjaxButton::widget([
            'label' => Yii::t('SpaceModule.views_create_create', 'Save'),
            'ajaxOptions' => [
                'type' => 'POST',
                'beforeSend' => new yii\web\JsExpression('function(){ setModalLoader(); }'),
                'success' => new yii\web\JsExpression('function(html){ $("#globalModal").html(html); }'),
                'url' => $contentContainer->createUrl('/cfiles/browse/edit-folder', ['fid' => $currentFolderId, 'id' => $folder->id]),
            ],
            'htmlOptions' => ['class' => 'btn btn-primary']
        ]);
        ?>
    </div>    
</div>
<?php ActiveForm::end() ?>