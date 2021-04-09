<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2017 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 *
 */

namespace humhub\modules\cfiles\models\forms;

use Yii;

/**
 * Created by PhpStorm.
 * User: buddha
 * Date: 19.08.2017
 * Time: 19:18
 */

class SelectionForm extends \yii\base\Model
{
    /**
     * @var string[] filesystem ids of the selection
     */
    public $selection = [];

    /**
     * @var \humhub\modules\content\components\ContentContainerActiveRecord
     */
    public $contentContainer;

    public function init()
    {
        $selection = Yii::$app->request->post('selection');

        if ($selection === null) {
            // Try to get param from GET because REST API method $I->sendDelete()
            // sends params as GET params instead of expected BODY params
            $selection = Yii::$app->request->get('selection');
        }

        if (is_array($selection)) {
            $this->selection = $selection;
        }
    }

}