<?php 
use yii\helpers\Html; 
use humhub\compat\CActiveForm;
?>

<div class="modal-dialog modal-dialog-small animated fadeIn">
    <div class="modal-content">
        <?php $form=CActiveForm::begin(); ?>
        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
            <h4 class="modal-title" id="myModalLabel">
                <?php echo Yii::t('CfilesModule.base', '<strong>Confirm</strong> delete file'); ?>
            </h4>
        </div>

        <div class="modal-body">
            <br />
            <?php echo Yii::t('CfilesModule.base', 'Do you really want to delete this %number% item(s) with all subcontent?', ['%number%' => count($selectedItems)]); ?>
        </div>
        
        <?php
        if (is_array($selectedItems)) {
            foreach ($selectedItems as $index => $item) {
                echo "<input class='input-hidden-selectedItem' type='hidden' name='selected[]' value='$item'/>";
            }
        }
        ?>

        <div class="modal-footer">
            <?php echo \humhub\widgets\AjaxButton::widget([
                            'label' => Yii::t('CfilesModule.base', 'Delete'),
                            'ajaxOptions' => [
                                'type' => 'POST',
                                'beforeSend' => new yii\web\JsExpression('function(){ setModalLoader(); }'),
                                'success' => new yii\web\JsExpression('function(html){ $("#fileList").html(html); $("#globalModal").modal("hide"); showHideBtns(); }'),
                                'url' => $contentContainer->createUrl('/cfiles/delete', [
                                    'fid' => $currentFolder->id,
                                    'confirm' => true,
                                ])
                            ],
                            'htmlOptions' => [
                                'class' => 'btn btn-primary',
                            ]
                        ]); ?>
            <button type="button" class="btn btn-primary"
                data-dismiss="modal">
                <?php echo Yii::t( 'CfilesModule.base', 'Close'); ?>
            </button>

        </div>
        <?php CActiveForm::end()?>
    </div>
</div>