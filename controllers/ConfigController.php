<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2015 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */
namespace humhub\modules\cfiles\controllers;

use Yii;
use humhub\modules\cfiles\models\ConfigureForm;
use humhub\models\Setting;

/**
 * ConfigController handles the configuration requests.
 *
 * @package humhub.modules.cfiles.controllers
 * @since 1.0
 * @author Sebastian Stumpf
 */
class ConfigController extends \humhub\modules\admin\components\Controller
{

    /**
     * Configuration action for super admins.
     */
    public function actionIndex()
    {
        $form = new ConfigureForm();
        $form->disableZipSupport = Setting::Get('disableZipSupport', 'cfiles');           
        
        if ($form->load(Yii::$app->request->post()) && $form->validate()) {
            Setting::Set('disableZipSupport', $form->disableZipSupport, 'cfiles');
        }
        
        return $this->render('index', array('model' => $form));
    }
}

?>
