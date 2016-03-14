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
$this->registerJsVar('cfilesZipUploadUrl', $contentContainer->createUrl('/cfiles/zip/upload-archive', [
    'fid' => $currentFolder->id
    ]));
$this->registerJsVar('cfilesDeleteUrl', $contentContainer->createUrl('/cfiles/delete', [
    'fid' => $currentFolder->id
]));
$this->registerJsVar('cfilesEditFolderUrl', $contentContainer->createUrl('/cfiles/edit', [
    'id' => '--folderId--'
]));
$this->registerJsVar('cfilesDownloadArchiveUrl', $contentContainer->createUrl('/cfiles/zip/download-archive', [
    'fid' => '--folderId--'
    ]));
$this->registerJsVar('cfilesMoveUrl', $contentContainer->createUrl('/cfiles/move', [
    'init' => 1
]));

?>
<?php echo Html::beginForm(null, null, ['data-target' => '#globalModal', 'id' => 'cfiles-form']); ?>
<div class="panel panel-default">

    <div class="panel-body">
    
        <div class="row files-action-menu">
            <?php if($this->context->action->id == "all-posted-files"): ?>
            <?php if(!Setting::Get('disableZipSupport', 'cfiles')): ?>
            <div class="col-sm-4">
                <div>
                    <?php echo Html::a('<i class="fa fa-download"></i> '.Yii::t('CfilesModule.base', 'ZIP all'), $contentContainer->createUrl('/cfiles/zip/download-archive', ['fid' => $currentFolder->id]), array('class' => 'btn btn-default overflow-ellipsis')); ?>
                </div>
            </div>
            <div class="col-sm-4">
                <div>
                    <?php echo Html::a('<i class="fa fa-download"></i> '.Yii::t('CfilesModule.base', 'ZIP selected')." (<span class='chkCnt'></span>)", $contentContainer->createUrl('/cfiles/zip/download-archive'), array('class' => 'btn btn-default selectedOnly overflow-ellipsis', 'id' => 'zip-selected-button', 'style' => 'display:none;')) ?>
                </div>
            </div>
            <?php endif; ?>
            <?php else: ?>
            <?php if($this->context->canWrite()): ?>
            <div class="col-sm-4">
                <div id="progress" class="progress"
                    style="display: none">
                    <div class="progress-bar progress-bar-success"></div>
                </div>
                <?php 
                $icon = '<i class="glyphicon glyphicon-plus"></i> ';
                $buttons = [];
                $buttons[] = 
                '<span class="split-button fileinput-button btn btn-success overflow-ellipsis">'.
                    $icon.
                    Yii::t('CfilesModule.base', 'Add file(s)').
                    '<input id="fileupload" type="file" name="files[]" multiple>'.
                '</span>';
                if(!Setting::Get('disableZipSupport', 'cfiles')):
                    $buttons[] = 
                    '<span class="fileinput-button btn btn-success overflow-ellipsis">'.
                        $icon.
                        Yii::t('CfilesModule.base', 'Upload ZIP').
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
            <?php if($this->context->canWrite() || (!Setting::Get('disableZipSupport', 'cfiles') && $itemCount > 0)): ?>
            <div class="col-sm-4">
                <?php 
                $icon = '<i class="fa fa-folder"></i> ';
                $buttons = [];
                if($this->context->canWrite()):
                    $buttons[] = Html::a('<i class="fa fa-folder"></i> '.Yii::t('CfilesModule.base', 'Add directory'), $contentContainer->createUrl('/cfiles/edit', ['fid' => $currentFolder->id]), array('data-target' => '#globalModal', 'class' => 'split-button btn btn-default overflow-ellipsis'));
                    if ($currentFolder->id !== BrowseController::ROOT_ID):
                        $buttons[] = Html::a('<i class="fa fa-folder"></i> '.Yii::t('CfilesModule.base', 'Edit directory'), $contentContainer->createUrl('/cfiles/edit', ['id' => $currentFolder->id]), array('data-target' => '#globalModal', 'class' => 'btn btn-default overflow-ellipsis'));
                    endif;
                endif;
                if(!Setting::Get('disableZipSupport', 'cfiles') && $itemCount > 0):
                    $buttons[] = Html::a('<i class="fa fa-download"></i> '.Yii::t('CfilesModule.base', 'ZIP all'), $contentContainer->createUrl('/cfiles/zip/download-archive', ['fid' => $currentFolder->id]), array('class' => 'btn btn-default overflow-ellipsis'));
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
            <?php if($this->context->canWrite() || !Setting::Get('disableZipSupport', 'cfiles')): ?>
            <div class="col-sm-4 selectedOnly">
                <?php 
                $icon = '';
                $buttons = [];
                if($this->context->canWrite()):
                    $buttons[] = 
                    \humhub\widgets\AjaxButton::widget([
                        'label' => '<i class="fa fa-trash"></i> '.Yii::t('CfilesModule.base', 'Delete'),
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
                        'label' => '<i class="fa fa-arrows"></i> '.Yii::t('CfilesModule.base', 'Move'),
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
                endif;
                if(!Setting::Get('disableZipSupport', 'cfiles')) {
                    $buttons[] = Html::a('<i class="fa fa-download"></i> '.Yii::t('CfilesModule.base', 'ZIP selected'), $contentContainer->createUrl('/cfiles/zip/download-archive'), array('class' => 'btn btn-default overflow-ellipsis', 'id' => 'zip-selected-button'));
                }
                echo DropdownButton::widget([
                    'label' => "(<span class='chkCnt'></span>) ".\Yii::t('CfilesModule.base', 'Selected items...'),
                    'buttons' => $buttons,
                    'icon' => $icon,
                    'split' => false,
                    'options' => [
                        'class' => 'btn btn-default overflow-ellipsis',
                        ]
                    ]
                );      
                ?>
            </div>
            <?php endif; ?>
            <?php endif; ?>
        </div>
        <hr id="files-action-menu-divider">
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
    <li><a tabindex="-1" href="#" data-action='move-files'><?php echo Yii::t('CfilesModule.base', 'Move');?></a></li>
    <?php endif; ?>
    <?php if(!Setting::Get('disableZipSupport', 'cfiles')): ?>
    <li><a tabindex="-1" href="#" data-action='zip'><?php echo Yii::t('CfilesModule.base', 'Download ZIP');?></a></li>
    <?php endif; ?>
</ul>

<ul id="contextMenuFile" class="contextMenu dropdown-menu" role="menu"
    style="display: none">
    <li><a tabindex="-1" href="#" data-action='download'><?php echo Yii::t('CfilesModule.base', 'Download');?></a></li>
    <?php if($this->context->action->id == "all-posted-files"): ?>
    <li role="separator" class="divider"></li>
    <li><a tabindex="-1" href="#" data-action='show-post'><?php echo Yii::t('CfilesModule.base', 'Show Post');?></a></li>
    <?php elseif($this->context->canWrite()): ?>
    <li role="separator" class="divider"></li>
    <li><a tabindex="-1" href="#" data-action='delete'><?php echo Yii::t('CfilesModule.base', 'Delete');?></a></li>
    <li><a tabindex="-1" href="#" data-action='move-files'><?php echo Yii::t('CfilesModule.base', 'Move');?></a></li>
    <?php endif; ?>
</ul>

<ul id="contextMenuImage" class="contextMenu dropdown-menu" role="menu"
    style="display: none">
    <li><a tabindex="-1" href="#" data-action='download'><?php echo Yii::t('CfilesModule.base', 'Download');?></a></li>
    <li role="separator" class="divider"></li>
    <li><a tabindex="-1" href="#" data-action='show-image'><?php echo Yii::t('CfilesModule.base', 'Show');?></a></li>
    <?php if($this->context->action->id == "all-posted-files"): ?>
    <li><a tabindex="-1" href="#" data-action='show-post'><?php echo Yii::t('CfilesModule.base', 'Show Post');?></a></li>
    <?php elseif($this->context->canWrite()): ?>    
    <li><a tabindex="-1" href="#" data-action='delete'><?php echo Yii::t('CfilesModule.base', 'Delete');?></a></li>
    <li><a tabindex="-1" href="#" data-action='move-files'><?php echo Yii::t('CfilesModule.base', 'Move');?></a></li>
    <?php endif; ?>
</ul>

<ul id="contextMenuAllPostedFiles" class="contextMenu dropdown-menu"
    role="menu" style="display: none">
    <li><a tabindex="-1" href="#" data-action='download'><?php echo Yii::t('CfilesModule.base', 'Open');?></a></li>
    <li><a tabindex="-1" href="#" data-action='zip'><?php echo Yii::t('CfilesModule.base', 'Download ZIP');?></a></li>
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
