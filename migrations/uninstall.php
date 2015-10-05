<?php
use yii\db\Migration;

class uninstall extends Migration
{

    public function up()
    {
        $this->dropTable('cfiles_file');
        $this->dropTable('cfiles_folder');
    }

    public function down()
    {
        echo "uninstall does not support migration down.\n";
        return false;
    }
}