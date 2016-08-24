<?php
use yii\db\Schema;
use yii\db\Migration;

class m160824_120822_add_has_wall_entry extends Migration
{

    public function up()
    {
        $this->addColumn('cfiles_folder', 'has_wall_entry', $this->boolean()->defaultValue(false));
    }

    public function down()
    {
        echo "m160824_120822_add_has_wall_entry cannot be reverted.\n";
        
        return false;
    }
    
    /*
     * // Use safeUp/safeDown to run migration code within a transaction public function safeUp() { } public function safeDown() { }
     */
}
