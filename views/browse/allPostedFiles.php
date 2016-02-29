<?php
use yii\helpers\Url;
use yii\helpers\Html;
use humhub\modules\cfiles\models\File;
use humhub\modules\cfiles\controllers\BrowseController;
$bundle = \humhub\modules\cfiles\Assets::register($this);
$this->registerJsVar('cfilesUploadUrl', "unused");
$this->registerJsVar('cfilesDeleteUrl', "unused");
$this->registerJsVar('cfilesEditFolderUrl', "unused");
$this->registerJsVar('cfilesMoveUrl', "unused");
?>
<div class="panel panel-default">
    <div class="panel-body">
        <div class="row">
            <div class="col-lg-10 col-md-9 col-sm-9" id="fileList">

                <ol class="breadcrumb" dir="ltr">
                    <li><a
                        href="<?php echo $contentContainer->createUrl('index', ['fid' => 0]); ?>"><i
                            class="fa fa-home fa-lg fa-fw"></i> </a></li>
                    <li><a
                        href="<?php echo $contentContainer->createUrl('all-posted-files'); ?>">
                            <?php echo Yii::t( 'CfilesModule.base', 'All posted files'); ?>
                        </a></li>
                </ol>

                <ul id="log">

                </ul>

                <div class="table-responsive">
                    <table id="bs-table" class="table table-hover">
                        <?php if(sizeof($items)> 0) : ?>
                        <thead>
                            <tr>
                                <th class="col-sm-5 text-left"
                                    data-sort="string"><?php echo Yii::t('CfilesModule.base', 'Name');?></th>
                                <th class="col-sm-2 text-right"
                                    data-sort="int"><?php echo Yii::t('CfilesModule.base', 'Size');?></th>
                                <th class="col-sm-2 text-right"
                                    data-sort="string"><?php echo Yii::t('CfilesModule.base', 'Creator');?></th>
                                <th class="col-sm-3 text-right"
                                    data-sort="int"><?php echo Yii::t('CfilesModule.base', 'Updated');?></th>
                            </tr>
                        </thead>
                        <tfoot>
                            <tr>
                                <td colspan="5"></td>
                            </tr>
                        </tfoot>
                        <?php foreach ($items as $item) : ?>
                        <tr
                            data-type="<?php echo File::getItemTypeByExt($item['file']->getExtension());?>"
                            data-url="<?php echo $item['file']->getUrl(); ?>"
                            data-content-url="<?php echo empty($item['content']) ? "" : $item['content']->getUrl(); ?>">
                            <td class="text-left"
                                data-sort-value="icon examples"><i
                                class="fa <?php echo File::getIconClassByExt($item['file']->getExtension()); ?> fa-fw"></i>&nbsp;
                                <?php if (File::getItemTypeByExt($item['file']->getExtension()) === "image") : ?>
                                <a class="preview-link"
                                data-toggle="lightbox"
                                href="<?php echo $item['file']->getUrl(); ?>#.jpeg"
                                data-footer='<button  type="button"
                                class="btn btn-primary"
                                data-dismiss="modal"><?php echo Yii::t('FileModule.base', 'Close'); ?></button>'><?php echo Html::encode($item['file']->file_name); ?></a>
                                <?php else : ?>
                                <a
                                href="<?php echo $item['file']->getUrl(); ?>">
                                    <?php echo Html::encode($item['file']->file_name); ?>
                                </a>
                                <?php endif; ?>
                            </td>
                            <td class="text-right"
                                data-sort-value="<?php echo $item['file']->size; ?>">
                                <?php if ($item['file']->size == 0): ?> &mdash;
                                <?php else: ?>
                                <?php echo Yii::$app->formatter->asShortSize($item['file']->size, 1); ?>
                                <?php endif; ?>
                            </td>
                            <td class="text-right" data-sort-value=""
                                title=""><a
                                href="<?php echo File::getCreatorById($item['file']->created_by)->createUrl(); ?>">
                                    <?php echo File::getCreatorById($item['file']->created_by)->username?>
                                </a></td>

                            </td>
                            <td class="text-right" data-sort-value=""
                                title="">
                                <?php echo \humhub\widgets\TimeAgo::widget([ 'timestamp'=> $item['file']->updated_at]); ?>
                            </td>
                        </tr>
                        <?php endforeach ; else : ?>
                        <p><?php echo Yii::t('CfilesModule.base', 'No files found.');?></p>
                        <?php endif; ?>

                    </table>
                </div>
            </div>
            <div class="col-lg-2 col-md-3 col-sm-3">
                <ul class="nav nav-pills nav-stacked">
                    <li><span>&nbsp;<br/>&nbsp;</span></li>
                    <li class="nav-divider"></li>
                    <li><?php echo Html::a('Download .zip', $contentContainer->createUrl('/cfiles/zip/download-zipped-folder', ['fid' => BrowseController::All_POSTED_FILES_ID])); ?></li>
                </ul>
            </div>
        </div>
    </div>
</div>

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