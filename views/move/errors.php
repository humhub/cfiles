<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2017 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 *
 */

/* @var $this \humhub\modules\ui\view\components\View */
/* @var $model MoveForm */

?>
<?php if ($model->hasErrors()) : ?>
    <div class="alert alert-danger">
        <ul>
            <?php foreach ($model->getErrors() as $attribute => $errors) : ?>
                <?= "<li>$errors[0]</li>" ?>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>