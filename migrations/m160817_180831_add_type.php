<?php
use yii\db\Schema;
use yii\db\Migration;

class m160817_180831_add_type extends Migration
{

    public function up()
    {
        $this->addColumn('cfiles_folder', 'type', $this->string(32));
    }

    public function down()
    {
        echo "m160817_180831_add_type cannot be reverted.\n";
        
        return false;
    }
    
    /*
     * // Use safeUp/safeDown to run migration code within a transaction public function safeUp() { } public function safeDown() { }
     */
}
