<?php
use yii\helpers\Url;
use yii\helpers\Html;
use humhub\modules\cfiles\models\File;
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
            <?php if(Setting::Get('enableZipSupport', 'cfiles')): ?>
            <div class="col-sm-3">
                <div>
                    <?php echo Html::a('<i class="fa fa-download"></i> '.Yii::t('CfilesModule.base', 'Download .zip'), $contentContainer->createUrl('/cfiles/zip/download-archive', ['fid' => $currentFolder->id]), array('class' => 'btn btn-default overflow-ellipsis')); ?>
                </div>
            </div>
            <div class="col-sm-3">
                <div>
                    <?php echo Html::a('<i class="fa fa-download"></i> '.Yii::t('CfilesModule.base', 'Archive selected')." (<span class='chkCnt'></span>)", $contentContainer->createUrl('/cfiles/zip/download-archive'), array('class' => 'btn btn-default selectedOnly overflow-ellipsis', 'id' => 'zip-selected-button')) ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
        <hr>
        <div class="row">
            <div class="col-sm-12" id="fileList">

                <ol class="breadcrumb" dir="ltr">
                    <li><a
                        href="<?php echo $contentContainer->createUrl('/cfiles/browse/index', ['fid' => 0]); ?>"><i
                            class="fa fa-home fa-lg fa-fw"></i> </a></li>
                    <li><a
                        href="<?php echo $contentContainer->createUrl('/cfiles/browse/all-posted-files'); ?>">
                            <?php echo Yii::t( 'CfilesModule.base', 'All posted files'); ?>
                        </a></li>
                </ol>

                <ul id="log">

                </ul>
                
                <?php if(sizeof($items)> 0) : ?>
                <div class="table-responsive">
                    <table id="bs-table" class="table table-hover">
                        <thead>
                            <tr>
                                <th class="text-right">
                                    <?php echo Html::checkbox('allchk', false, [ 'class' => 'allselect']); ?></th>
                                <th class="text-left"><?php echo Yii::t('CfilesModule.base', 'Name'); ?></th>
                                <th class="hidden-xs text-right"><?php echo Yii::t('CfilesModule.base', 'Size'); ?></th>
                                <th class="text-right"><?php echo Yii::t('CfilesModule.base', 'Creator'); ?></th>
                                <th class="hidden-xxs text-right"><?php echo Yii::t('CfilesModule.base', 'Updated'); ?></th>
                            </tr>
                        </thead>
                        <tfoot>
                            <tr>
                                <td colspan="3"></td>
                                <td class="hidden-xs"></td>
                                <td class="hidden-xxs"></td>
                            </tr>
                        </tfoot>
                        <?php foreach ($items as $item) : ?>
                        <tr
                            data-type="<?php echo File::getItemTypeByExt($item['file']->getExtension());?>"
                            data-url="<?php echo $item['file']->getUrl().'&'.http_build_query(['download' => true]); ?>"
                            data-content-url="<?php echo empty($item['content']) ? "" : $item['content']->getUrl(); ?>">
                            <td class="text-muted text-right">
                                <?php echo Html::checkbox('selected[]', false, [ 'value' => 'baseFile_'.$item['file']->id, 'class' => 'multiselect']); ?>
                            </td>
                            <td class="text-left"
                                data-sort-value="icon examples">
                                <div class="title">
                                    <i class="fa <?php echo File::getIconClassByExt($item['file']->getExtension()); ?> fa-fw"></i>&nbsp;
                                    <?php if (File::getItemTypeByExt($item['file']->getExtension()) === "image") : ?>
                                    <a class="preview-link" data-toggle="lightbox"
                                        href="<?php echo $item['file']->getUrl(); ?>#.jpeg"
                                        data-footer='
                                        <button type="button" class="btn btn-primary" data-dismiss="modal"><?php echo Yii::t('FileModule.base', 'Close'); ?></button>'>
                                        <?php echo Html::encode($item['file']->file_name); ?>
                                    </a>
                                    <?php else : ?>
                                    <a href="<?php echo $item['file']->getUrl(); ?>">
                                        <?php echo Html::encode($item['file']->file_name); ?>
                                    </a>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td class="hidden-xs text-right">
                                <div class="size pull-right">
                                    <?php if ($item['file']->size == 0): ?>
                                        &mdash;
                                    <?php else: ?>
                                        <?php echo Yii::$app->formatter->asShortSize($item['file']->size, 1); ?>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td class="text-right">
                                <div class="creator pull-right">
                                    <a href="<?php echo File::getCreatorById($item['file']->created_by)->createUrl(); ?>">
                                        <img class="img-rounded tt img_margin"
                                            src="<?php echo File::getCreatorById($item['file']->created_by)->getProfileImage()->getUrl(); ?>"
                                            width="21" height="21" alt="21x21" data-src="holder.js/21x21"
                                            style="width: 21px; height: 21px;"
                                            data-original-title="<?php echo File::getCreatorById($item['file']->created_by)->getDisplayName();?>"
                                            data-placement="top" title="" data-toggle="tooltip">
                                    </a>
                                </div>
                            </td>

                            <td class="hidden-xxs text-right">
                                <div class="timestamp pull-right">
                                    <?php echo \humhub\widgets\TimeAgo::widget([ 'timestamp'=> $item['file']->updated_at]); ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </table>
                </div>
                <?php else : ?>
                <div class="folderEmptyMessage">
                    <div class="panel">
                        <div class="panel-body">
                            <p><strong><?php echo Yii::t('CfilesModule.base', 'This folder is empty.');?></strong></p>
                            <?php echo Yii::t('CfilesModule.base', 'Upload files to the stream to fill this folder.');?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php echo Html::endForm(); ?>

<ul id="contextMenuFile" class="contextMenu dropdown-menu" role="menu"
    style="display: none">
    <li><a tabindex="-1" href="#" data-action='download'><?php echo Yii::t('CfilesModule.base', 'Download');?></a></li>
    <li role="separator" class="divider"></li>
    <li><a tabindex="-1" href="#" data-action='show-post'><?php echo Yii::t('CfilesModule.base', 'Show Post');?></a></li>
</ul>

<ul id="contextMenuImage" class="contextMenu dropdown-menu" role="menu"
    style="display: none">
    <li><a tabindex="-1" href="#" data-action='download'><?php echo Yii::t('CfilesModule.base', 'Download');?></a></li>
    <li role="separator" class="divider"></li>
    <li><a tabindex="-1" href="#" data-action='show-image'><?php echo Yii::t('CfilesModule.base', 'Show Image');?></a></li>
    <li><a tabindex="-1" href="#" data-action='show-post'><?php echo Yii::t('CfilesModule.base', 'Show Post');?></a></li>
</ul>

<script>
    $(function() {
        initFileList();
    });
</script>