<?php
use yii\db\Schema;
use yii\db\Migration;

class m150720_174011_initial extends Migration
{

    public function up()
    {
        $this->createTable('cfiles_file', array(
            'id' => 'pk',
            'parent_folder_id' => 'int(11) NULL'
        ), '');
        
        $this->createTable('cfiles_folder', array(
            'id' => 'pk',
            'parent_folder_id' => 'int(11) NULL',
            'title' => 'varchar(255) NOT NULL'
        ), '');
    }

    public function down()
    {
        echo "m150720_174011_initial cannot be reverted.\n";
        
        return false;
    }
    
    /*
     * // Use safeUp/safeDown to run migration code within a transaction public function safeUp() { } public function safeDown() { }
     */
}
