<?php

use humhub\modules\ui\icon\widgets\Icon;
use yii\helpers\Html;

/* @var $folder  \humhub\modules\cfiles\models\Folder */

?>

<?php if (!$folder->isRoot()): ?>
    <nav id="cfiles-crumb" class="mb-3" aria-label="breadcrumb">

        <div class="folder-visibility float-end tt" data-placement="left"
             title="<?= $folder->getVisibilityTitle() ?>">
            <?= Icon::get($folder->content->isPublic() ? 'unlock' : 'lock')->size(Icon::SIZE_LG) ?>
        </div>

        <ol class="breadcrumb">
            <?php foreach ($folder->getCrumb() as $parentFolder): ?>
                <li class="breadcrumb-item">
                    <a href="<?= $contentContainer->createUrl('/cfiles/browse/index', ['fid' => $parentFolder->id]) ?>">
                        <?= $parentFolder->isRoot() ?
                            Icon::get('home')->size(Icon::SIZE_LG) :
                            Html::encode($parentFolder->title) ?>
                    </a>
                </li>
            <?php endforeach; ?>
        </ol>

    </nav>
<?php endif; ?>
