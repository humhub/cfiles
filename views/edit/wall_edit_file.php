<?php 
use yii\helpers\Html; use humhub\compat\CActiveForm; 
use humhub\modules\cfiles\widgets\FilePreview;
?>

<div class="content_edit" id="cfiles_edit_file_<?php echo $file->id;?>">
    
    <div style="margin-bottom: 10px">
        <?php $form=CActiveForm::begin(); ?>
    
        <!-- create contenteditable div for HEditorWidget to place the data -->
        <div id="cfiles_file_description_<?php echo $file->id; ?>_contenteditable" class="form-control atwho-input"
             contenteditable="true"><?php echo \humhub\widgets\RichText::widget(['text' => $file->description, 'edit' => true]); ?></div>
    
        <?php echo $form->field($file, 'description')->label(false)->textArea(array('class' => 'form-control', 'id' => 'cfiles_file_description_' . $file->id, 'placeholder' => Yii::t('CfilesModule.base', 'Edit the file description...'))); ?>
    
        <?= \humhub\widgets\RichTextEditor::widget(['id' => 'cfiles_file_description_' . $file->id, 'inputContent' => $file->description, 'record' => $file]); ?>
        
        <?php //echo $form->field($file, 'description'); ?>
    
    
        <?php echo \humhub\widgets\AjaxButton::widget([
            'label' => Yii::t('PostModule.views_edit', 'Save'),
            'ajaxOptions' => [
                'type' => 'POST',
                'beforeSend' => new yii\web\JsExpression('function(html){ }'),
                'success' => new yii\web\JsExpression('function(html){$(".wall_' . $file->getUniqueId() . '").replaceWith(html); }'),
                'statusCode' => ['400' => new yii\web\JsExpression('function(xhr) { }')],
                'url' => $contentContainer->createUrl('/cfiles/edit/file', [ 'fid' => $currentFolderId, 'id' => $file->getItemId(), 'fromWall' => 1 ])
            ],
            'htmlOptions' => [ 
                'class' => 'btn btn-primary' 
            ] 
        ]); ?>
        
        <?php echo \humhub\widgets\AjaxButton::widget([
            'label' => Yii::t('CfilesModule.base', 'Close'),
            'ajaxOptions' => [
                'type' => 'GET',
                'success' => new yii\web\JsExpression('function(html){$(".wall_' . $file->getUniqueId() . '").replaceWith(html); }'),
                'statusCode' => ['400' => new yii\web\JsExpression('function(xhr) { }')],
                'url' => $contentContainer->createUrl('/cfiles/edit/file', [ 'fid' => $currentFolderId, 'id' => $file->getItemId(), 'fromWall' => 1 , 'cancel' => 1])
            ],
            'htmlOptions' => [ 
                'class' => 'btn btn-primary' 
            ] 
        ]); ?>
    
        <?php CActiveForm::end()?>
    </div>
    
    <div class="preview">
            <?php echo FilePreview::widget(['file' => $file, 'width' => 600, 'height' => 350, 'htmlConf' => ['class' => 'preview', 'id' => 'cfiles-wallout-file-preview-'.$file->id]]); ?>
    </div>
</div>