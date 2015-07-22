<?php

use yii\helpers\Html;
?>
<ol class="breadcrumb" dir="ltr">
    <li><a href="<?php echo $contentContainer->createUrl('index', ['fid' => 0]); ?>"><i class="fa fa-home fa-lg fa-fw"></i> </a></li>
    <?php foreach ($crumb as $parentFolder): ?>
        <li><a href="<?php echo $contentContainer->createUrl('index', ['fid' => $parentFolder->id]); ?>"><?php echo Html::encode($parentFolder->title); ?></a></li>
    <?php endforeach; ?>
</ol>      

<div class="table-responsive">
    <table id="bs-table" class="table table-hover">
        <thead>
            <tr>
                <th class="text-right" data-sort="int"><?php echo Html::checkbox('allchk', false, ['class' => 'allselect']); ?></th>
                <th class="col-sm-7 text-left" data-sort="string">Name</th>
                <th class="col-sm-2 text-right" data-sort="int">Size</th>
                <th class="col-sm-3 text-right" data-sort="int">Created</th>
            </tr>
        </thead>
        <tfoot>
            <tr>
                <td colspan="4">
                </td>
            </tr>
        </tfoot>

        <?php foreach ($items as $item) : ?>
            <tr data-type="folder" data-id="<?php echo $item->getItemId(); ?>" data-url="<?php echo $item->getUrl(); ?>">
                <td class="text-muted text-right">
                    <?php echo Html::checkbox('selected[]', false, ['value' => $item->getItemId(), 'class' => 'multiselect']); ?>
                </td>   
                <td class="text-left" data-sort-value="icon examples"><i class="fa fa-<?php echo $item->getIconClass(); ?> fa-fw"></i>&nbsp;
                    <a href="<?php echo $item->getUrl(); ?>">
                        <?php echo Html::encode($item->getTitle()); ?>
                    </a>
                </td>
                <td class="text-right" data-sort-value="<?php echo $item->getSize(); ?>">
                    <?php if ($item->getSize() == 0): ?>
                        &mdash;
                    <?php else: ?>
                        <?php echo Yii::$app->formatter->asShortSize($item->getSize(), 1); ?>
                    <?php endif; ?>
                </td>
                <td class="text-right" data-sort-value="" title="">
                    <?php echo \humhub\widgets\TimeAgo::widget(['timestamp' => $item->content->created_at]); ?>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>
</div>
<script>

    $(function () {
        initFileList();
    });
</script>
