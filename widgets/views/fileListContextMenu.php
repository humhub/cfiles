
<ul id="contextMenuFolder" class="contextMenu dropdown-menu" role="menu" style="display: none">
    <li><a tabindex="-1" href="#" data-action='download'><i class="fa fa-folder-open"></i><?= Yii::t('CfilesModule.base', 'Open'); ?></a></li>
    <li><a tabindex="-1" href="#" data-action='show-url'><i class="fa fa-link"></i><?= Yii::t('CfilesModule.base', 'Display Url'); ?></a></li>
    <?php if ($canWrite): ?>
        <li role="separator" class="divider"></li>
        <li><a tabindex="-1" href="#" data-action='edit-folder'><i class="fa fa-pencil"></i><?= Yii::t('CfilesModule.base', 'Edit'); ?></a></li>
        <li><a tabindex="-1" href="#" data-action='delete'><i class="fa fa-trash"></i><?= Yii::t('CfilesModule.base', 'Delete'); ?></a></li>
        <li><a tabindex="-1" href="#" data-action='move-files'><i class="fa fa-arrows"></i><?= Yii::t('CfilesModule.base', 'Move'); ?></a></li>
    <?php endif; ?>
    <?php if ($zipEnabled): ?>
        <li><a tabindex="-1" href="#" data-action='zip'><i class="fa fa-file-archive-o"></i><?= Yii::t('CfilesModule.base', 'Download ZIP'); ?></a></li>
    <?php endif; ?>
</ul>

<ul id="contextMenuFile" class="contextMenu dropdown-menu" role="menu"
    style="display: none">
    <li><a tabindex="-1" href="#" data-action='download'><i class="fa fa-cloud-download"></i><?= Yii::t('CfilesModule.base', 'Download'); ?></a></li>
    <li><a tabindex="-1" href="#" data-action='show-post'><i class="fa fa-window-maximize"></i><?= Yii::t('CfilesModule.base', 'Show Post'); ?></a></li>
    <li><a tabindex="-1" href="#" data-action='show-url'><i class="fa fa-link"></i><?= Yii::t('CfilesModule.base', 'Display Url'); ?></a></li>

    <?php if ($canWrite && !$folder->isAllPostedFiles()): ?>
        <li role="separator" class="divider"></li>
        <li><a tabindex="-1" href="#" data-action='edit-file'><i class="fa fa-pencil"></i><?= Yii::t('CfilesModule.base', 'Edit'); ?></a></li>
        <li><a tabindex="-1" href="#" data-action='delete'><i class="fa fa-trash"></i><?= Yii::t('CfilesModule.base', 'Delete'); ?></a></li>
        <li><a tabindex="-1" href="#" data-action='move-files'><i class="fa fa-arrows"></i><?= Yii::t('CfilesModule.base', 'Move'); ?></a></li>
    <?php endif; ?>

</ul>

<ul id="contextMenuImage" class="contextMenu dropdown-menu" role="menu" style="display: none">
    <li><a tabindex="-1" href="#" data-action='download'><i class="fa fa-cloud-download"></i><?= Yii::t('CfilesModule.base', 'Download'); ?></a></li>
    <li><a tabindex="-1" href="#" data-action='show-post'><i class="fa fa-window-maximize"></i><?= Yii::t('CfilesModule.base', 'Show Post'); ?></a></li>
    <li><a tabindex="-1" href="#" data-action='show-url'><i class="fa fa-link"></i><?= Yii::t('CfilesModule.base', 'Display Url'); ?></a></li>

    <?php if ($canWrite && !$folder->isAllPostedFiles()): ?>
        <li role="separator" class="divider"></li>
        <li><a tabindex="-1" href="#" data-action='edit-file'><i class="fa fa-pencil"></i><?= Yii::t('CfilesModule.base', 'Edit'); ?></a></li>
        <li><a tabindex="-1" href="#" data-action='delete'><i class="fa fa-trash"></i><?= Yii::t('CfilesModule.base', 'Delete'); ?></a></li>
        <li><a tabindex="-1" href="#" data-action='move-files'><i class="fa fa-arrows"></i><?= Yii::t('CfilesModule.base', 'Move'); ?></a></li>
    <?php endif; ?>
</ul>

<ul id="contextMenuAllPostedFiles" class="contextMenu dropdown-menu" role="menu" style="display: none">
    <li><a tabindex="-1" href="#" data-action='download'><i class="fa fa-folder-open"></i><?= Yii::t('CfilesModule.base', 'Open'); ?></a></li>
    <li><a tabindex="-1" href="#" data-action='show-url'><i class="fa fa-link"></i><?= Yii::t('CfilesModule.base', 'Display Url'); ?></a></li>
</ul>
