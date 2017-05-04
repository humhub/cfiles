<?php

use yii\db\Migration;

class m170210_154141_folderNoStream extends Migration
{

    public function up()
    {
        try {
            $this->dropColumn('cfiles_folder', 'has_wall_entry');
        } catch (Exception $ex) {
            Yii::error("Could not drop haswall entry column", 'cfiles');
        }

        $this->db->createCommand('UPDATE content c ' .
                'LEFT JOIN cfiles_folder f ON f.id=c.object_id AND c.object_model=:folderClass ' .
                'SET c.stream_channel = NULL ' .
                'WHERE f.id IS NOT NULL', [':folderClass' => humhub\modules\cfiles\models\Folder::class])->execute();
    }

    public function down()
    {
        echo "m170210_154141_folderNoStream cannot be reverted.\n";

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
