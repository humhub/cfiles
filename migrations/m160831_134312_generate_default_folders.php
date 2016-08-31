<?php
use yii\db\Schema;
use yii\db\Migration;
use humhub\modules\space\behaviors\SpaceModelModules;
use humhub\modules\cfiles\models\Folder;
use humhub\modules\cfiles\models\File;
use humhub\modules\space\models\Space;
use humhub\modules\user\models\User;
use humhub\modules\cfiles\Module;

class m160831_134312_generate_default_folders extends Migration
{

    public function up()
    {
        $spaces = Space::find()->all();
        $users = User::find()->all();
        $containers = array_merge($users == null ?  [] : $users, $spaces == null ? [] : $spaces);
        foreach ($containers as $container) {
            if ($container->isModuleEnabled('cfiles')) {
                $created_by = 1;$container instanceof User ? $container->id : $container instanceof Space ? $container->created_by : 1; 
                $created_by = $created_by == null ? 1 : $created_by;               
                $root = new Folder();
                $root->title = Module::ROOT_TITLE;
                $root->content->container = $container;
                $root->description = Module::ROOT_DESCRIPTION;
                $root->type = Folder::TYPE_FOLDER_ROOT;
                $root->has_wall_entry = true;
                $root->content->created_by = $created_by;
                $root->save();
                $posted = new Folder();
                $posted->title = Module::ALL_POSTED_FILES_TITLE;
                $posted->description = Module::ALL_POSTED_FILES_DESCRIPTION;
                $posted->content->container = $container;
                $posted->parent_folder_id = $root->id;
                $posted->type = Folder::TYPE_FOLDER_POSTED;
                $posted->has_wall_entry = false;
                $posted->content->created_by = $created_by;
                $posted->save();
                
                $filesQuery = File::find()->joinWith('baseFile')
                    ->contentContainer($container);
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
                    $file->parent_folder_id = $root->id;
                    $file->save();
                }
                foreach ($rootsubfolders as $folder) {
                    $folder->parent_folder_id = $root->id;
                    $folder->save();
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
