<?php
use yii\helpers\Html;
use humhub\compat\CActiveForm;

$this->registerJs('initDirectoryList();', \yii\web\View::POS_END);

function renderFolder($folder) {
    echo "<li><span class='selectable' id='".$folder['folder']->id."'>".$folder['folder']->title."</span>";
    if(!empty($folder['subfolders'])) {
        echo "<ul>";
        foreach($folder['subfolders'] as $subfolder) {
          renderFolder($subfolder);
        }
        echo "</ul>";
    }
    echo "</li>";
}
?>

</script>
<div id="destIdContainer" style="display: none;"></div>

<div class="modal-dialog modal-dialog-small animated fadeIn">
    <div class="modal-content">
        <?php $form = CActiveForm::begin(); ?>
        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal"
                aria-hidden="true">&times;</button>
            <h4 class="modal-title" id="myModalLabel">
                <?php echo Yii::t('CfilesModule.views_browse_moveFiles', '<strong>Move</strong> files'); ?>
            </h4>
        </div>

        <div class="modal-body">
            <br />
            <div class="directory-list">
                <div class="selectable" id="0">/ (root)</div>
                <ul>
                <?php foreach ($folders as $dir) :
                    renderFolder($dir);
                endforeach; ?>
                </ul>
            </div>

            <div class="modal-footer">
            <?php
            echo \humhub\widgets\AjaxButton::widget([
                'label' => Yii::t('CfilesModule.views_browse_moveFiles', 'Save'),
                'ajaxOptions' => [
                    'type' => 'POST',
                    'beforeSend' => new yii\web\JsExpression('function(){ setModalLoader(); }'),
                    'success' => new yii\web\JsExpression('function(html){ $("#globalModal").html(html); }'),
                    'url' => $contentContainer->createUrl('/cfiles/browse/move-files', [
                        'fid' => $currentFolderId,
                    ])
                ],
                'htmlOptions' => [
                    'class' => 'btn btn-primary'
                ]
            ]);
            ?>
            <button type="button" class="btn btn-primary"
                    data-dismiss="modal"><?php echo Yii::t('CfilesModule.views_browse_moveFiles', 'Close'); ?></button>

            </div>
        <?php CActiveForm::end()?>
    </div>
  </div>
</div>