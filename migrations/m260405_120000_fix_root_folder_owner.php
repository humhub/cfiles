<?php

use humhub\modules\cfiles\models\Folder;
use humhub\modules\content\models\ContentContainerModuleState;
use humhub\modules\space\models\Space;
use yii\db\Migration;

class m260405_120000_fix_root_folder_owner extends Migration
{
    public function safeUp()
    {
        $enabledSpaces = Space::find()
            ->innerJoin(
                ContentContainerModuleState::tableName() . ' contentcontainer_module',
                'contentcontainer_module.contentcontainer_id = space.contentcontainer_id',
            )
            ->andWhere([
                'contentcontainer_module.module_id' => 'cfiles',
                'contentcontainer_module.module_state' => [
                    ContentContainerModuleState::STATE_ENABLED,
                    ContentContainerModuleState::STATE_FORCE_ENABLED,
                ],
            ]);

        foreach ($enabledSpaces->each() as $space) {
            Folder::ensureRootFolderStructure($space);
        }
    }

    public function safeDown()
    {
        echo "m260405_120000_fix_root_folder_owner cannot be reverted.\n";

        return false;
    }
}
