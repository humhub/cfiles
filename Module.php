<?php

namespace humhub\modules\cfiles;

use Yii;

class Module extends \humhub\components\Module
{

    public function behaviors()
    {
        return [
            //   \humhub\modules\user\behaviors\UserModule::className(),
            \humhub\modules\space\behaviors\SpaceModule::className(),
        ];
    }

    /*
      public function disable()
      {
      if (parent::disable()) {

      foreach (WikiPage::model()->findAll() as $page) {
      $page->delete();
      }

      return true;
      }

      return false;
      }

      public function getSpaceModuleDescription()
      {
      return Yii::t('WikiModule.base', 'Adds a wiki to this space.');
      }

      public function getUserModuleDescription()
      {
      return Yii::t('WikiModule.base', 'Adds a wiki to your profile.');
      }
     */

    public function getItemById($itemId)
    {

        list($type, $id) = explode('-', $itemId);

        if ($type == 'file') {
            return models\File::findOne(['id' => $id]);
        } elseif ($type == 'folder') {
            return models\Folder::findOne(['id' => $id]);
        }
        return null;
    }

}
