<?php
use yii\db\Schema;
use yii\db\Migration;

class m160921_1102234_remove_has_wall_entry extends Migration
{

    public function up()
    {
        try {
            $this->dropColumn('cfiles_folder', 'has_wall_entry');
        } catch (Exception $ex) {
            Yii::error("Could not drop haswall entry column", 'cfiles');
        }
    }

    public function down()
    {
        echo "m160921_1102234_remove_has_wall_entry cannot be reverted.\n";
        
        return false;
    }
    
    /*
     * // Use safeUp/safeDown to run migration code within a transaction public function safeUp() { } public function safeDown() { }
     */
}
