<?php
use yii\helpers\Url;
use yii\helpers\Html;
use humhub\modules\cfiles\controllers\BrowseController;
use humhub\models\Setting;
use yii\bootstrap\ButtonDropdown;
use humhub\modules\cfiles\widgets\DropdownButton;

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
    
        <div class="row files-action-menu">
            <?php if($this->context->canWrite()): ?>
            <div class="col-sm-3">
                <div id="progress" class="progress"
                    style="display: none">
                    <div class="progress-bar progress-bar-success"></div>
                </div>
                <?php 
                $icon = '<i class="glyphicon glyphicon-plus"></i> ';
                $buttons = [];
                $buttons[] = 
                '<span class="fileinput-button btn btn-success overflow-ellipsis">'.
                    $icon.
                    Yii::t('CfilesModule.base', 'Add file(s)').
                    '<input id="fileupload" type="file" name="files[]" multiple>'.
                '</span>';
                if(Setting::Get('enableZipSupport', 'cfiles')):
                    $buttons[] = 
                    '<span class="fileinput-button btn btn-success overflow-ellipsis">'.
                        $icon.
                        Yii::t('CfilesModule.base', 'Upload .zip').
                        '<input id="zipupload" type="file" name="files[]" multiple>'.
                    '</span>';
                endif;
                echo DropdownButton::widget([
                    'label' => \Yii::t('CfilesModule.base', 'Upload'),
                    'buttons' => $buttons,
                    'icon' => $icon,
                    'options' => [
                        'class' => 'btn btn-success overflow-ellipsis',
                        ]
                    ]
                );      
                ?>
            </div>
            <?php endif; ?>
            <?php if(Setting::Get('enableZipSupport', 'cfiles') && $itemCount > 0): ?>
            <div class="col-sm-3">
                <div>
                    <?php echo Html::a('<i class="fa fa-download"></i> '.Yii::t('CfilesModule.base', 'Download .zip'), $contentContainer->createUrl('/cfiles/zip/download-zipped-folder', ['fid' => $currentFolder->id]), array('class' => 'btn btn-default overflow-ellipsis')); ?>
                </div>
            </div>
            <?php endif; ?>
            <?php if($this->context->canWrite()): ?>
            <div class="col-sm-3">
                <?php 
                $icon = '<i class="fa fa-folder"></i> ';
                $buttons = [];
                $buttons[] = Html::a('<i class="fa fa-folder"></i> '.Yii::t('CfilesModule.base', 'Add directory'), $contentContainer->createUrl('/cfiles/edit', ['fid' => $currentFolder->id]), array('data-target' => '#globalModal', 'class' => 'btn btn-default overflow-ellipsis'));
                if ($currentFolder->id !== BrowseController::ROOT_ID):
                    $buttons[] = Html::a('<i class="fa fa-folder"></i> '.Yii::t('CfilesModule.base', 'Edit directory'), $contentContainer->createUrl('/cfiles/edit', ['id' => $currentFolder->id]), array('data-target' => '#globalModal', 'class' => 'btn btn-default overflow-ellipsis'));
                endif;
                echo DropdownButton::widget([
                    'label' => \Yii::t('CfilesModule.base', 'Folder options'),
                    'buttons' => $buttons,
                    'icon' => $icon,
                    'options' => [
                        'class' => 'btn btn-default overflow-ellipsis',
                        ]
                    ]
                );      
                ?>
            </div>
            <?php endif; ?>
            <?php if($this->context->canWrite()): ?>
            <div class="col-sm-3 selectedOnly">
                <?php 
                $icon = '';
                $buttons = [];
                $buttons[] = 
                \humhub\widgets\AjaxButton::widget([
                    'label' => '<i class="fa fa-trash"></i> '.Yii::t('CfilesModule.base', 'Delete')." (<span class='chkCnt'></span>)",
                    'tag' => 'button',
                    'ajaxOptions' => [
                    'type' => 'POST',
                    'success' => new yii\web\JsExpression('function(html){ $("#globalModal").html(html); $("#globalModal").modal("show");}'),
                    'url' => $contentContainer->createUrl('/cfiles/delete', [
                        'fid' => $currentFolder->id
                        ])
                    ],
                    'htmlOptions' => [
                    'class' => 'btn btn-danger selectedOnly filedelete-button overflow-ellipsis',
                    'style' => 'display:none',
                    ]
                ]);
                $buttons[] = 
                \humhub\widgets\AjaxButton::widget([
                    'label' => '<i class="fa fa-arrows"></i> '.Yii::t('CfilesModule.base', 'Move')." (<span class='chkCnt'></span>)",
                    'tag' => 'button',
                    'ajaxOptions' => [
                    'type' => 'POST',
                    'success' => new yii\web\JsExpression('function(html){ $("#globalModal").html(html); $("#globalModal").modal("show"); openDirectory(' . $currentFolder->id . '); selectDirectory(' . $currentFolder->id . ');}'),
                    'url' => $contentContainer->createUrl('/cfiles/move', [
                        'init' => 1,
                        'fid' => $currentFolder->id
                        ])
                    ],
                    'htmlOptions' => [
                    'class' => 'btn btn-default filemove-button overflow-ellipsis',
                    ]
                ]);
                echo DropdownButton::widget([
                    'label' => \Yii::t('CfilesModule.base', 'Move / Delete')." (<span class='chkCnt'></span>)",
                    'buttons' => $buttons,
                    'icon' => $icon,
                    'options' => [
                        'class' => 'btn btn-default overflow-ellipsis',
                        ]
                    ]
                );      
                ?>
            </div>
            <?php endif; ?>
        </div>
        <hr>
        <div class="row">
            <div class="col-sm-12" id="fileList">
                <?php echo $fileList; ?>
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
    <?php if(Setting::Get('enableZipSupport', 'cfiles')): ?>
    <li><a tabindex="-1" href="#" data-action='zip'><?php echo Yii::t('CfilesModule.base', 'Download zip');?></a></li>
    <?php endif; ?>
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
