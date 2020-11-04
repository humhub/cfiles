<?php

use yii\db\Migration;

/**
 * Class m201030_071320_add_download_counter
 */
class m201030_071320_add_download_counter extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('cfiles_file', 'download_count', $this->integer()->unsigned()->defaultValue(0)->notNull());
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn('cfiles_file', 'download_count');
    }
}
