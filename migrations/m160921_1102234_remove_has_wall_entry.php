<?php
use yii\db\Schema;
use yii\db\Migration;

class m160921_1102234_remove_has_wall_entry extends Migration
{

    public function up()
    {
        $this->dropColumn('cfiles_folder', 'has_wall_entry');
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
