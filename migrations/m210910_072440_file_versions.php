<?php

use yii\db\Migration;

/**
 * Class m210910_072440_file_versions
 */
class m210910_072440_file_versions extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('cfiles_file', 'file_id', $this->integer()->null());
        $this->addForeignKey('fk_cfiles_file_version', 'cfiles_file', 'file_id', 'file', 'id', 'SET NULL');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn('cfiles_file', 'file_id');
    }

}
