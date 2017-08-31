<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2017 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 *
 */

use humhub\modules\cfiles\models\File;
use humhub\modules\cfiles\models\Folder;
use yii\db\Migration;

class m170830_122439_foreignkeys extends Migration
{
    public function safeUp()
    {
        // Remove all folders with invalid parent folder, note in mysql we can not use the name of the table to delete in a sub select as in the delete files query
        $query = (new \yii\db\Query())
            ->select(['id'])
            ->from('cfiles_folder folder')
            ->where('folder.parent_folder_id IS NOT NULL')
            ->andWhere( 'NOT EXISTS (SELECT id FROM cfiles_folder f where f.id = folder.parent_folder_id)')
            ->limit(200);

        $countQuery = clone $query;

        while($countQuery->count()) {
            $this->deleteFolders($this->extractIds($query->all()));
        }

        // Remove all files and file content with invalid parent folder
        $this->execute('DELETE file FROM cfiles_file AS file WHERE file.parent_folder_id IS NOT NULL AND NOT EXISTS (SELECT id FROM cfiles_folder folder where folder.id = file.parent_folder_id)');
        $this->execute('DELETE FROM content WHERE content.object_model = :fileclass AND NOT EXISTS (SELECT id FROM cfiles_file f WHERE f.id = content.object_id)', [':fileclass' => File::class]);
        $this->execute('DELETE FROM file WHERE file.object_model = :fileclass AND NOT EXISTS (SELECT id FROM cfiles_file f WHERE f.id = file.object_id)', [':fileclass' => File::class]);

        try {
            $this->addForeignKey('fk_cfiles_file_parent_folder', 'cfiles_file', 'parent_folder_id', 'cfiles_folder', 'id', 'SET NULL');
            $this->addForeignKey('fk_cfiles_folder_parent_folder', 'cfiles_folder', 'parent_folder_id', 'cfiles_folder', 'id', 'SET NULL');
        } catch(Exception $e) {
            Yii::error($e);
        }
    }

    public function extractIds($rows = null)
    {
        if(empty($rows)) {
            return [];
        }

        $ids = [];
        foreach($rows as $row) {
            $ids[] = $row['id'];
        }
        return $ids;
    }

    public function deleteFolders($ids = [])
    {
        if(empty($ids)) {
            return;
        }

        // Delete sub files
        $this->delete('cfiles_file', ['IN', 'parent_folder_id', $ids]);

        // Recursively delete sub folders
        $query = (new \yii\db\Query())
            ->select(['id'])
            ->from('cfiles_folder')
            ->where(['IN', 'cfiles_folder.parent_folder_id', $ids]);

        $this->deleteFolders($this->extractIds($query->all()));

        // Delete folder itself
        $this->delete('cfiles_folder', ['IN', 'id', $ids]);
        $this->delete('content', ['AND',['object_model' => Folder::class], ['in', 'object_id', $ids]]);
    }

    public function safeDown()
    {
        echo "m170830_122437_foreignkeys.\n";

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
