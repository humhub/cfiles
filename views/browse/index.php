<?php
use yii\helpers\Url;
use yii\helpers\Html;
use humhub\modules\cfiles\controllers\BrowseController;
use humhub\models\Setting;

$bundle = \humhub\modules\cfiles\Assets::register($this);
$this->registerJsVar('cfilesUploadUrl', $contentContainer->createUrl('/cfiles/upload', [
    'fid' => $currentFolder->id
]));
$this->registerJsVar('cfilesZipUploadUrl', $contentContainer->createUrl('/cfiles/zip/upload-zipped-folder', [
    'fid' => $currentFolder->id
    ]));
$this->registerJsVar('cfilesDeleteUrl', $contentContainer->createUrl('/cfiles/delete', [
    'fid' => $currentFolder->id
]));
$this->registerJsVar('cfilesEditFolderUrl', $contentContainer->createUrl('/cfiles/edit', [
    'id' => '--folderId--'
]));
$this->registerJsVar('cfilesZipFolderUrl', $contentContainer->createUrl('/cfiles/zip/download-zipped-folder', [
    'fid' => '--folderId--'
    ]));
$this->registerJsVar('cfilesMoveUrl', $contentContainer->createUrl('/cfiles/move', [
    'init' => 1
]));

?>
<?php echo Html::beginForm(null, null, ['data-target' => '#globalModal']); ?>
<div class="panel panel-default">

    <div class="panel-body">
        <div class="row">
            <div class="col-sm-8 col-md-9 col-lg-10" id="fileList">
                <?php echo $fileList; ?>
            </div>
            <div class="col-sm-4 col-md-3 col-lg-2">

                <div id="progress" class="progress"
                    style="display: none">
                    <div class="progress-bar progress-bar-success"></div>
                </div>

                <ul class="nav nav-pills nav-stacked">
                    <?php if($this->context->canWrite()): ?>
                    <li><span class="fileinput-button btn btn-success">
                            <i class="glyphicon glyphicon-plus"></i> <?php echo Yii::t('CfilesModule.base', '<strong>Add file(s)</strong>');?> <input
                            id="fileupload" type="file" name="files[]"
                            multiple>
                    </span></li>
                    <?php else : ?>
                    <li><span>&nbsp;<br />&nbsp;
                    </span></li>
                    <?php endif; ?>
                    <li class="nav-divider"></li>
                    <?php if(Setting::Get('enableZipSupport', 'cfiles')): ?>
                    <?php if($this->context->canWrite()): ?>
                    <li><a class="fileinput-button"> <i
                            class="glyphicon glyphicon-plus"></i> <?php echo Yii::t('CfilesModule.base', 'Upload .zip');?> <input
                            id="zipupload" type="file" name="files[]"
                            multiple>
                    </a></li>
                    <?php endif; ?>
                    <?php if($itemCount > 0): ?>
                        <li><?php echo Html::a(Yii::t('CfilesModule.base', 'Download .zip'), $contentContainer->createUrl('/cfiles/zip/download-zipped-folder', ['fid' => $currentFolder->id])); ?></li>
                    <?php endif; ?>
                    <?php endif; ?>
                    <?php if($this->context->canWrite()): ?>
                    <li><?php echo Html::a(Yii::t('CfilesModule.base', 'Add directory'), $contentContainer->createUrl('/cfiles/edit', ['fid' => $currentFolder->id]), array('data-target' => '#globalModal')); ?></li>
                        <?php if ($currentFolder->id !== BrowseController::ROOT_ID) : ?>
                    <li><?php echo Html::a(Yii::t('CfilesModule.base', 'Edit directory'), $contentContainer->createUrl('/cfiles/edit', ['id' => $currentFolder->id]), array('data-target' => '#globalModal')); ?></li>
                        <?php endif; ?>
                    <li>
                        <?php
                        echo \humhub\widgets\AjaxButton::widget([
                            'label' => Yii::t('CfilesModule.base', 'Delete')." (<span class='chkCnt'></span>)",
                            'tag' => 'a',
                            'ajaxOptions' => [
                                'type' => 'POST',
                                'success' => new yii\web\JsExpression('function(html){ $("#globalModal").html(html); $("#globalModal").modal("show");}'),
                                'url' => $contentContainer->createUrl('/cfiles/delete', [
                                    'fid' => $currentFolder->id
                                ])
                            ],
                            'htmlOptions' => [
                                'class' => 'selectedOnly filedelete-button',
                                'style' => 'display:none',
                            ]
                        ]);
                        ?>
                                            
                    </li>
                    <!-- <li><?php echo Html::a(Yii::t('CfilesModule.base', 'Delete')." (<span class='chkCnt'></span>)", $contentContainer->createUrl('/cfiles/delete', ['fid' => $currentFolder->id]), array('data-target' => '#globalModal', 'class' => 'selectedOnly filedelete-button', 'style' => 'display:none;')); ?></li> -->
                    <li>
                        <?php
                        echo \humhub\widgets\AjaxButton::widget([
                            'label' => Yii::t('CfilesModule.base', 'Move')." (<span class='chkCnt'></span>)",
                            'tag' => 'a',
                            'ajaxOptions' => [
                                'type' => 'POST',
                                'success' => new yii\web\JsExpression('function(html){ $("#globalModal").html(html); $("#globalModal").modal("show"); openDirectory(' . $currentFolder->id . '); selectDirectory(' . $currentFolder->id . ');}'),
                                'url' => $contentContainer->createUrl('/cfiles/move', [
                                    'init' => 1,
                                    'fid' => $currentFolder->id
                                ])
                            ],
                            'htmlOptions' => [
                                'class' => 'selectedOnly filemove-button',
                                'style' => 'display:none'
                            ]
                        ]);
                        ?>
                    </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </div>
</div>
<?php echo Html::endForm(); ?>


<ul id="contextMenuFolder" class="contextMenu dropdown-menu" role="menu"
    style="display: none">
    <li><a tabindex="-1" href="#" data-action='download'><?php echo Yii::t('CfilesModule.base', 'Open');?></a></li>
    <li role="separator" class="divider"></li>
    <?php if($this->context->canWrite()): ?>
    <li><a tabindex="-1" href="#" data-action='edit'><?php echo Yii::t('CfilesModule.base', 'Edit');?></a></li>
    <li><a tabindex="-1" href="#" data-action='delete'><?php echo Yii::t('CfilesModule.base', 'Delete');?></a></li>
    <li><a tabindex="-1" href="#" data-action='move-files'><?php echo Yii::t('CfilesModule.base', 'Move folder');?></a></li>
    <?php endif; ?>
    <li><a tabindex="-1" href="#" data-action='zip'><?php echo Yii::t('CfilesModule.base', 'Download zip');?></a></li>
</ul>

<ul id="contextMenuFile" class="contextMenu dropdown-menu" role="menu"
    style="display: none">
    <li><a tabindex="-1" href="#" data-action='download'><?php echo Yii::t('CfilesModule.base', 'Download');?></a></li>
    <?php if($this->context->canWrite()): ?>
    <li role="separator" class="divider"></li>
    <li><a tabindex="-1" href="#" data-action='delete'><?php echo Yii::t('CfilesModule.base', 'Delete');?></a></li>
    <li><a tabindex="-1" href="#" data-action='move-files'><?php echo Yii::t('CfilesModule.base', 'Move file');?></a></li>
    <?php endif; ?>
</ul>

<ul id="contextMenuImage" class="contextMenu dropdown-menu" role="menu"
    style="display: none">
    <li><a tabindex="-1" href="#" data-action='download'><?php echo Yii::t('CfilesModule.base', 'Download');?></a></li>
    <li role="separator" class="divider"></li>
    <li><a tabindex="-1" href="#" data-action='show-image'><?php echo Yii::t('CfilesModule.base', 'Show');?></a></li>
    <?php if($this->context->canWrite()): ?>
    <li><a tabindex="-1" href="#" data-action='delete'><?php echo Yii::t('CfilesModule.base', 'Delete');?></a></li>
    <li><a tabindex="-1" href="#" data-action='move-files'><?php echo Yii::t('CfilesModule.base', 'Move file');?></a></li>
    <?php endif; ?>
</ul>

<ul id="contextMenuAllPostedFiles" class="contextMenu dropdown-menu"
    role="menu" style="display: none">
    <li><a tabindex="-1" href="#" data-action='download'><?php echo Yii::t('CfilesModule.base', 'Open');?></a></li>
    <li><a tabindex="-1" href="#" data-action='zip'><?php echo Yii::t('CfilesModule.base', 'Download zip');?></a></li>
</ul>

<div id="hiddenLogContainer" style="display: none">
    <div class="alert alert-danger" style="display: none">
        <ul>
        </ul>
    </div>
    <div class="alert alert-warning" style="display: none">
        <ul>
        </ul>
    </div>
    <div class="alert alert-info" style="display: none">
        <ul>
        </ul>
    </div>
</div>
