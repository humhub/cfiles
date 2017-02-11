<?php

use yii\db\Migration;

class m170211_110702_show_in_stream extends Migration
{

    public function up()
    {
        $this->update('file', ['show_in_stream' => false], ['object_model' => \humhub\modules\cfiles\models\File::class]);
    }

    public function down()
    {
        echo "m170211_110702_show_in_stream cannot be reverted.\n";

        return false;
    }

    /*
      // Use safeUp/safeDown to run migration code within a transaction
      public function safeUp()
      {
      }

      public function safeDown()
      {
      }
     */
}
