<?php

use yii\db\Migration;

/**
 * Superseded by m260827_140000_drop_root_folder.
 *
 * This migration re-owned each container's root folder to the container owner, because a root
 * created by an ordinary member was deleted along with that member's account, taking the
 * container's whole file tree with it. There is no root folder anymore — top level is a NULL
 * parent — so there is nothing left to repair, and the class it called no longer exists.
 *
 * Kept as an empty step so installations that already ran it keep a consistent migration
 * history.
 */
class m260405_120000_fix_root_folder_owner extends Migration
{
    public function safeUp()
    {
    }

    public function safeDown()
    {
        echo "m260405_120000_fix_root_folder_owner cannot be reverted.\n";

        return false;
    }
}
