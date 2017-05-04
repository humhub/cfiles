<ul id="contextMenuFolder" class="contextMenu dropdown-menu" role="menu" style="display: none">
    <li><a tabindex="-1" href="#" data-action='download'><?= Yii::t('CfilesModule.base', 'Open'); ?></a></li>
    <?php if ($canWrite): ?>
        <li role="separator" class="divider"></li>
        <li><a tabindex="-1" href="#" data-action='edit-folder'><?= Yii::t('CfilesModule.base', 'Edit'); ?></a></li>
        <li><a tabindex="-1" href="#" data-action='delete'><?= Yii::t('CfilesModule.base', 'Delete'); ?></a></li>
        <li><a tabindex="-1" href="#" data-action='move-files'><?= Yii::t('CfilesModule.base', 'Move'); ?></a></li>
    <?php endif; ?>
    <?php if ($zipEnabled): ?>
        <li><a tabindex="-1" href="#" data-action='zip'><?= Yii::t('CfilesModule.base', 'Download ZIP'); ?></a></li>
    <?php endif; ?>
</ul>

<ul id="contextMenuFile" class="contextMenu dropdown-menu" role="menu"
    style="display: none">
    <li><a tabindex="-1" href="#" data-action='download'><?= Yii::t('CfilesModule.base', 'Download'); ?></a></li>
    <li><a tabindex="-1" href="#" data-action='show-post'><?= Yii::t('CfilesModule.base', 'Show Post'); ?></a></li>
    <?php if ($canWrite && !$folder->isAllPostedFiles()): ?>
        <li role="separator" class="divider"></li>
        <li><a tabindex="-1" href="#" data-action='edit-file'><?= Yii::t('CfilesModule.base', 'Edit'); ?></a></li>
        <li><a tabindex="-1" href="#" data-action='delete'><?= Yii::t('CfilesModule.base', 'Delete'); ?></a></li>
        <li><a tabindex="-1" href="#" data-action='move-files'><?= Yii::t('CfilesModule.base', 'Move'); ?></a></li>
    <?php endif; ?>
</ul>

<ul id="contextMenuImage" class="contextMenu dropdown-menu" role="menu"
    style="display: none">
    <li><a tabindex="-1" href="#" data-action='download'><?= Yii::t('CfilesModule.base', 'Download'); ?></a></li>
    <li><a tabindex="-1" href="#" data-action='show-post'><?= Yii::t('CfilesModule.base', 'Show Post'); ?></a></li>
    <li role="separator" class="divider"></li>
    <li><a tabindex="-1" href="#" data-action='show-image'><?= Yii::t('CfilesModule.base', 'Show Image'); ?></a></li>
    <?php if ($canWrite && !$folder->isAllPostedFiles()): ?>    
        <li><a tabindex="-1" href="#" data-action='edit-file'><?= Yii::t('CfilesModule.base', 'Edit'); ?></a></li>
        <li><a tabindex="-1" href="#" data-action='delete'><?= Yii::t('CfilesModule.base', 'Delete'); ?></a></li>
        <li><a tabindex="-1" href="#" data-action='move-files'><?= Yii::t('CfilesModule.base', 'Move'); ?></a></li>
    <?php endif; ?>
</ul>

<ul id="contextMenuAllPostedFiles" class="contextMenu dropdown-menu"
    role="menu" style="display: none">
    <li><a tabindex="-1" href="#" data-action='download'><?= Yii::t('CfilesModule.base', 'Open'); ?></a></li>
    <?php if ($zipEnabled): ?>
        <li><a tabindex="-1" href="#" data-action='zip'><?= Yii::t('CfilesModule.base', 'Download ZIP'); ?></a></li>
    <?php endif; ?>
</ul>
