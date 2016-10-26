<?php use yii\helpers\Html; use humhub\compat\CActiveForm; ?>

<div class="content_edit" id="cfiles_edit_folder_<?php echo $folder->id;?>">
    
    <?php $form=CActiveForm::begin(); ?>
    
    <?php echo $form->field($folder, 'title'); ?></h5>
    
    <!-- create contenteditable div for HEditorWidget to place the data -->
    <div id="cfiles_folder_description_<?php echo $folder->id; ?>_contenteditable" class="form-control atwho-input"
         contenteditable="true"><?php echo \humhub\widgets\RichText::widget(['text' => $folder->description, 'edit' => true]); ?></div>
    <?php echo $form->field($folder, 'description')->label(false)->textArea(array('class' => 'form-control', 'id' => 'cfiles_folder_description_' . $folder->id, 'placeholder' => Yii::t('CfilesModule.base', 'Edit the folder description...'))); ?>
    <?= \humhub\widgets\RichTextEditor::widget(['id' => 'cfiles_folder_description_' . $folder->id, 'inputContent' => $folder->description, 'record' => $folder]); ?>
    
    <?php echo \humhub\widgets\AjaxButton::widget([
        'label' => Yii::t('CfilesModule.base', 'Save'),
        'ajaxOptions' => [
            'type' => 'POST',
            'beforeSend' => new yii\web\JsExpression('function(html){ }'),
            'success' => new yii\web\JsExpression('function(html){$(".wall_' . $folder->getUniqueId() . '").replaceWith(html); }'),
            'statusCode' => ['400' => new yii\web\JsExpression('function(xhr) { }')],
            'url' => $contentContainer->createUrl('/cfiles/edit/folder', [ 'fid' => $currentFolderId, 'id' => $folder->getItemId(), 'fromWall' => 1 ])
        ],
        'htmlOptions' => [ 
            'class' => 'btn btn-primary' 
        ] 
    ]); ?>
    
    <?php echo \humhub\widgets\AjaxButton::widget([
        'label' => Yii::t('CfilesModule.base', 'Close'),
        'ajaxOptions' => [
            'type' => 'GET',
            'success' => new yii\web\JsExpression('function(html){$(".wall_' . $folder->getUniqueId() . '").replaceWith(html); }'),
            'statusCode' => ['400' => new yii\web\JsExpression('function(xhr) { }')],
            'url' => $contentContainer->createUrl('/cfiles/edit/folder', [ 'fid' => $currentFolderId, 'id' => $folder->getItemId(), 'fromWall' => 1 , 'cancel' => 1])
        ],
        'htmlOptions' => [ 
            'class' => 'btn btn-primary' 
        ] 
    ]); ?>

    <?php CActiveForm::end()?>
</div>