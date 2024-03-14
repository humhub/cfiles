<?php

namespace humhub\modules\cfiles\controllers;

use humhub\modules\cfiles\models\ConfigureContainerForm;
use humhub\modules\content\components\ContentContainerController;
use humhub\modules\content\components\ContentContainerControllerAccess;
use humhub\modules\space\models\Space;
use humhub\modules\user\models\User;
use Yii;

class ConfigContainerController extends ContentContainerController
{
    /**
     * @inheritdoc
     */
    protected function getAccessRules()
    {
        return [[ContentContainerControllerAccess::RULE_USER_GROUP_ONLY => [Space::USERGROUP_ADMIN, User::USERGROUP_SELF]]];
    }

    public function actionIndex()
    {
        $form = new ConfigureContainerForm(['contentContainer' => $this->contentContainer]);

        if ($form->load(Yii::$app->request->post()) && $form->save()) {
            $this->view->saved();
        }

        return $this->render('index', ['model' => $form]);
    }
}
