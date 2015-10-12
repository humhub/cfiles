<?php
use yii\helpers\Html;
use humhub\compat\CActiveForm;
use humhub\modules\cfiles\models\Folder;
/* use yii\widgets\ActiveForm; */
/* $form = ActiveForm::begin([]) */
?>

<div class="modal-dialog modal-dialog-small animated fadeIn">
    <div class="modal-content">
        <?php $form = CActiveForm::begin(); ?>
        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal"
                aria-hidden="true">&times;</button>
            <h4 class="modal-title" id="myModalLabel">
                <?php if ($file instanceof Folder) :
                    echo Yii::t('CfilesModule.views_browse_itemExists', '<strong>Directory</strong> "%title%" exists', ['%title%' => $file->title]);
                elseif ($file instanceof File) :
                    echo Yii::t('CfilesModule.views_browse_itemExists', '<strong>File</strong> "%title%" exists', ['%title%' => $file->title]);
                else :
                    echo Yii::t('CfilesModule.views_browse_itemExists', '<strong>Item</strong> already exists');
                endif ?>
            </h4>
        </div>

        <div class="modal-body">
            <?php echo Yii::t('CfilesModule.views_browse_itemExists', 'What should be done?'); ?>
        </div>
        
        <div class="modal-footer">
            <?php
            echo \humhub\widgets\AjaxButton::widget([
                'label' => Yii::t('CfilesModule.views_browse_itemExists', 'Rename'),
                'ajaxOptions' => [
                    'type' => 'POST',
                    'beforeSend' => new yii\web\JsExpression('function(){ setModalLoader(); }'),
                    'success' => new yii\web\JsExpression('function(html){ $("#globalModal").html(html); }'),
                    'url' => $contentContainer->createUrl($responseUrl, [
                        'fid' => $currentFolderId,
                        'id' => $file->id,
                    ])
                ],
                'htmlOptions' => [
                    'class' => 'btn btn-primary'
                ]
            ]);
            ?>
            <button type="button" class="btn btn-primary"
                data-dismiss="modal"><?php echo Yii::t('CfilesModule.views_browse_itemExists', 'Cancel'); ?></button>
        </div>
        <?php CActiveForm::end()?>
    </div>
</div>
