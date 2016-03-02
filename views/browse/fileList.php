<?php
use yii\helpers\Html;
use humhub\modules\cfiles\controllers\BrowseController;
?>
<ol class="breadcrumb" dir="ltr">
<?php foreach ($crumb as $parentFolder): ?>
    <li><a
        href="<?php echo $contentContainer->createUrl('index', ['fid' => $parentFolder->id]); ?>">
    <?php echo $parentFolder->id == BrowseController::ROOT_ID ? '<i class="fa fa-home fa-lg fa-fw"></i>' : Html::encode($parentFolder->title); ?></a></li>
<?php endforeach; ?>
</ol>

<div id="cfiles-log"></div>

<div class="table-responsive">
    <table id="bs-table" class="table table-hover">
        <?php if(sizeof($items) > 0 || $allPostedFilesCount > 0) : ?>
        <thead>
            <tr>
                <th class="text-right" data-sort="int">
                    <?php echo Html::checkbox('allchk', false, [ 'class' => 'allselect']); ?></th>
                <th class="col-sm-5 text-left" data-sort="string"><?php echo Yii::t('CfilesModule.base', 'Name'); ?></th>
                <th class="col-sm-2 text-right" data-sort="int"><?php echo Yii::t('CfilesModule.base', 'Size'); ?></th>
                <th class="col-sm-2 text-right" data-sort="string"><?php echo Yii::t('CfilesModule.base', 'Creator'); ?></th>
                <th class="col-sm-3 text-right" data-sort="int"><?php echo Yii::t('CfilesModule.base', 'Updated'); ?></th>
            </tr>
        </thead>
        <tfoot>
            <tr>
                <td colspan="5"></td>
            </tr>
        </tfoot>
        <?php if ($allPostedFilesCount > 0) : ?>
            <tr data-type="all-posted-files"
            data-url="<?php echo $contentContainer->createUrl('all-posted-files'); ?>"
            data-id="<?php echo BrowseController::All_POSTED_FILES_ID; ?>">
            <td></td>
            <td class="text-left title">
                <div class="title">
                    <i class="fa fa-folder fa-fw"></i>&nbsp;
                    <a
                    href="<?php echo $contentContainer->createUrl('all-posted-files'); ?>">
                            <?php echo Yii::t('CfilesModule.base', 'All posted files'); ?> (<?php echo ''. $allPostedFilesCount; ?>)
                    </a>
                </div>
            </td>
            <td></td>
            <td></td>
            <td></td>
        </tr>
        <?php endif; ?>
        <?php foreach ($items as $item) : ?>
        <tr data-type="<?php echo $item->getItemType(); ?>"
            data-id="<?php echo $item->id; ?>"
            data-url="<?php echo $item->getUrl(); ?>">
            <td class="text-muted text-right">
                <?php echo Html::checkbox('selected[]', false, [ 'value' => $item->getItemId(), 'class' => 'multiselect']); ?>
            </td>
            <td class="text-left" data-sort-value="icon examples">
                <div class="title">
                    <i class="fa <?php echo $item->getIconClass(); ?> fa-fw"></i>&nbsp;
                    <?php if ($item->getItemType() === "image") : ?>
                    <a class="preview-link" data-toggle="lightbox"
                        href="<?php echo $item->getUrl(); ?>#.jpeg"
                        data-footer='
                        <button type="button" class="btn btn-primary" data-dismiss="modal"><?php echo Yii::t('FileModule.base', 'Close'); ?></button>'>
                        <?php echo Html::encode($item->getTitle()); ?>
                    </a>
                    <?php else : ?>
                    <a href="<?php echo $item->getUrl(); ?>">
                        <?php echo Html::encode($item->getTitle()); ?>
                    </a>
                    <?php endif; ?>
                </div>
            </td>
            <td class="text-right"
                data-sort-value="<?php echo $item->getSize(); ?>">
                <div class="size pull-right">
                    <?php if ($item->getSize() == 0): ?> 
                        &mdash;
                    <?php else: ?>
                        <?php echo Yii::$app->formatter->asShortSize($item->getSize(), 1); ?>
                    <?php endif; ?>
                </div>
            </td>
            <td class="text-right" data-sort-value="" title="">
                <div class="creator">
                    <a href="<?php echo $item->creator->createUrl(); ?>">
                                <?php echo $item->creator->username?>
                    </a>
                </div>
            </td>
            <td class="text-right" data-sort-value="" title="">
                <div class="timestamp pull-right">
                    <?php echo \humhub\widgets\TimeAgo::widget([ 'timestamp' => $item->content->updated_at ]); ?>
                </div>
            </td>
        </tr>
        <?php endforeach; else: ?>
        <p><?php echo Yii::t('CfilesModule.base', 'No files found.');?></p>
        <?php endif; ?>
        

    </table>
</div>
<script>
    $(function () {
        initFileList();
    });
</script>