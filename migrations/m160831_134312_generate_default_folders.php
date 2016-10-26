<?php
use yii\db\Schema;
use yii\db\Migration;
use humhub\modules\space\behaviors\SpaceModelModules;
use humhub\modules\cfiles\models\Folder;
use humhub\modules\cfiles\models\File;
use humhub\modules\space\models\Space;
use humhub\modules\user\models\User;
use humhub\modules\cfiles\Module;
use humhub\libs\DateHelper;

class m160831_134312_generate_default_folders extends Migration
{

    public function up()
    {
        $spaces = Space::find()->all();
        $users = User::find()->all();
        $containers = array_merge($users == null ? [] : $users, $spaces == null ? [] : $spaces);
        foreach ($containers as $container) {
            $created_by = $container instanceof User ? $container->id : $container instanceof Space ? $container->created_by : 1;
            $created_by = $created_by == null ? 1 : $created_by;
            if ($container->isModuleEnabled('cfiles')) {
                $this->insert('cfiles_folder', [
                    'title' => Module::ROOT_TITLE,
                    'description' => Module::ROOT_DESCRIPTION,
                    'parent_folder_id' => 0,
                    'has_wall_entry' => false,
                    'type' => Folder::TYPE_FOLDER_ROOT
                ]);
                $root_id = Yii::$app->db->getLastInsertID();
                $this->insert('content', [
                    'guid' => \humhub\libs\UUID::v4(),
                    'object_model' => Folder::className(),
                    'object_id' => $root_id,
                    'visibility' => 0,
                    'sticked' => 0,
                    'archived' => 0,
                    'created_at' => new \yii\db\Expression('NOW()'),
                    'created_by' => $created_by,
                    'updated_at' => new \yii\db\Expression('NOW()'),
                    'updated_by' => $created_by,
                    'contentcontainer_id' => $container->contentcontainer_id
                ]);
                $this->insert('cfiles_folder', [
                    'title' => Module::ALL_POSTED_FILES_TITLE,
                    'description' => Module::ALL_POSTED_FILES_DESCRIPTION,
                    'parent_folder_id' => $root_id,
                    'has_wall_entry' => false,
                    'type' => Folder::TYPE_FOLDER_POSTED
                ]);
                $allpostedfiles_id = Yii::$app->db->getLastInsertID();
                $this->insert('content', [
                    'guid' => \humhub\libs\UUID::v4(),
                    'object_model' => Folder::className(),
                    'object_id' => $allpostedfiles_id,
                    'visibility' => 0,
                    'sticked' => 0,
                    'archived' => 0,
                    'created_at' => new \yii\db\Expression('NOW()'),
                    'created_by' => $created_by,
                    'updated_at' => new \yii\db\Expression('NOW()'),
                    'updated_by' => $created_by,
                    'contentcontainer_id' => $container->contentcontainer_id
                ]);
                $posted_content_id = Yii::$app->db->getLastInsertID();
                
                $filesQuery = File::find()->joinWith('baseFile')->contentContainer($container);
                $foldersQuery = Folder::find()->contentContainer($container);
                $filesQuery->andWhere([
                    'cfiles_file.parent_folder_id' => 0
                ]);
                // user maintained folders
                $foldersQuery->andWhere([
                    'cfiles_folder.parent_folder_id' => 0
                ]);
                // do not return any folders here that are root or allpostedfiles
                $foldersQuery->andWhere([
                    'cfiles_folder.type' => null
                ]);
                
                $rootsubfiles = $filesQuery->all();
                $rootsubfolders = $foldersQuery->all();
                
                foreach ($rootsubfiles as $file) {
                    $this->update('cfiles_file', [
                        'cfiles_file.parent_folder_id' => $root_id
                    ], ['id' => $file->id]);
                }
                foreach ($rootsubfolders as $folder) {
                    $this->update('cfiles_folder', [
                        'parent_folder_id' => $root_id
                    ], ['id' => $folder->id]);
                }
            }
        }
    }

    public function down()
    {
        echo "m160831_134312_generate_default_folders cannot be reverted.\n";
        
        return false;
    }
    
    /*
     * // Use safeUp/safeDown to run migration code within a transaction public function safeUp() { } public function safeDown() { }
     */
}
