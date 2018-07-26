<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2015 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\cfiles\controllers;

use Yii;
use humhub\modules\cfiles\models\forms\MoveForm;
use humhub\modules\cfiles\permissions\ManageFiles;
use humhub\modules\cfiles\permissions\WriteAccess;

/**
 * Description of BrowseController
 *
 * @author luke, Sebastian Stumpf
 */
class MoveController extends BaseController
{

    /**
     * @inheritdoc
     */
    public function getAccessRules()
    {
        return [
            ['permission' => [ManageFiles::class]]
        ];
    }

    /**
     * Action to move files and folders from the current, to another folder.
     * @return string
     */
    public function actionIndex() //Make sure an $fid is given otherwise the root folder is used as default
    {
        $model = new MoveForm([
            'root' => $this->getRootFolder(),
            'sourceFolder' => $this->getCurrentFolder()
        ]);

        if (!$model->load(Yii::$app->request->post())) {
            return $this->renderAjax('modal_move', [
                'model' => $model,
            ]);
        }

        if($model->save()) {
            $this->view->saved();
            return $this->htmlRedirect($model->destination->createUrl('/cfiles/browse'));
        } else {
            $errorMsg = Yii::t('CfilesModule.base', 'Some files could not be moved: ');
            foreach ($model->getErrors() as $key => $errors) {
                foreach ($errors as $error) {
                    $errorMsg .= $error.' ';
                }
            }

            $this->view->error($errorMsg);
            return $this->htmlRedirect($model->sourceFolder->createUrl('/cfiles/browse'));
        }
    }
}
