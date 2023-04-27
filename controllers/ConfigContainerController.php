<?php

namespace humhub\modules\cfiles\controllers;

use humhub\modules\cfiles\models\ConfigureContainerForm;
use humhub\modules\content\components\ContentContainerController;
use Yii;

class ConfigContainerController extends ContentContainerController
{

    public function actionIndex()
    {
        $form = new ConfigureContainerForm(['contentContainer' => $this->contentContainer]);

        if ($form->load(Yii::$app->request->post()) && $form->save()) {
            $this->view->saved();
        }

        return $this->render('index', ['model' => $form]);
    }
}

?>