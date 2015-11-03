<?php use yii\helpers\Url; use yii\helpers\Html; use humhub\modules\cfiles\models\File; $bundle=\ humhub\modules\cfiles\Assets::register($this); ?>
<div class="panel panel-default">
    <div class="panel-body">
        <div class="row">
            <div class="col-lg-10 col-md-9 col-sm-9" id="fileList">
                <!--<?php foreach ($items as $key => $file) : ?>
                     <pre>
                        <?php print_r($file->getInfoArray())?>
                    </pre>
                <?php endforeach; ?>-->

                <ol class="breadcrumb" dir="ltr">
                    <li><a
                        href="<?php echo $contentContainer->createUrl('index', ['fid' => 0]); ?>"><i
                            class="fa fa-home fa-lg fa-fw"></i> </a></li>
                    <li><a
                        href="<?php echo $contentContainer->createUrl('all-posted-files'); ?>">
                            <?php echo Yii::t( 'CfilesModule.views_browse_editFolder', 'All posted files'); ?>
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
                                    data-sort="string">Name</th>
                                <th class="col-sm-2 text-right"
                                    data-sort="int">Size</th>
                                <th class="col-sm-2 text-right"
                                    data-sort="string">Creator</th>
                                <th class="col-sm-3 text-right"
                                    data-sort="int">Updated</th>
                            </tr>
                        </thead>
                        <tfoot>
                            <tr>
                                <td colspan="5"></td>
                            </tr>
                        </tfoot>
                        <?php foreach ($items as $item) : ?>
                        <tr data-type="folder"
                            data-id="<?php //echo $item->getItemId(); ?>"
                            data-url="<?php echo $item->getUrl(); ?>">
                            <td class="text-left"
                                data-sort-value="icon examples"><i
                                class="fa <?php echo File::getIconClassByExt($item->getExtension()); ?> fa-fw"></i>&nbsp;
                                <a href="<?php echo $item->getUrl(); ?>">
                                    <?php echo Html::encode($item->file_name); ?>
                                </a></td>
                            <td class="text-right"
                                data-sort-value="<?php echo $item->size; ?>">
                                <?php if ($item->size == 0): ?> &mdash;
                                <?php else: ?>
                                <?php echo Yii::$app->formatter->asShortSize($item->size, 1); ?>
                                <?php endif; ?>
                            </td>
                            <td class="text-right" data-sort-value=""
                                title=""><a
                                href="<?php echo File::getCreatorById($item->created_by)->createUrl(); ?>">
                                    <?php echo File::getCreatorById($item->created_by)->username?>
                                </a></td>

                            </td>
                            <td class="text-right" data-sort-value=""
                                title="">
                                <?php echo \humhub\widgets\TimeAgo::widget([ 'timestamp'=> $item->updated_at]); ?>
                            </td>
                        </tr>
                        <?php endforeach ; else : ?>
                        <p>No files found.</p>
                        <?php endif; ?>

                    </table>
                </div>
            </div>
            <div class="col-lg-2 col-md-3 col-sm-3">
                <ul class="nav nav-pills nav-stacked">&nbsp;
                </ul>
            </div>
        </div>
    </div>
</div>