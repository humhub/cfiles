<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2017 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 *
 */

use yii\db\Migration;

class m170830_122433_set_root_partent_null extends Migration
{
    public function safeUp()
    {
        $this->update('cfiles_file', ['parent_folder_id' => new \yii\db\Expression('NULL')], ['parent_folder_id' => 0]);
        $this->update('cfiles_folder', ['parent_folder_id' => new \yii\db\Expression('NULL')], ['parent_folder_id' => 0]);
    }

    public function safeDown()
    {
        echo "m170830_122433_set_root_partent_null.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m170830_122432_foreignkeys cannot be reverted.\n";

        return false;
    }
    */
}
