<?php

use humhub\modules\cfiles\models\File;
use humhub\modules\cfiles\models\Folder;
use yii\db\Migration;

/**
 * Removes the per-container root folder.
 *
 * The root folder was introduced in 2016 alongside the "Files from the stream" folder, as one
 * of two "default folders" created when the module was enabled on a container. Its twin is
 * gone, and the schema never needed it: `parent_folder_id` is nullable, the foreign key is
 * `ON DELETE SET NULL`, and an earlier migration (m170830_122433) had already moved top-level
 * items to a NULL parent before the root folder was made mandatory.
 *
 * Top level is `parent_folder_id IS NULL` again. That removes a Content record per container
 * that nobody could see or use, the ownership problem it created when its creator was deleted
 * (m260405_120000 existed only to repair that), and the `type` column, whose last remaining
 * value this was.
 */
class m260827_140000_drop_root_folder extends Migration
{
    private const TYPE_FOLDER_ROOT = 'root';

    public function safeUp()
    {
        $rootIds = (new \yii\db\Query())
            ->select('id')
            ->from('cfiles_folder')
            ->where(['type' => self::TYPE_FOLDER_ROOT])
            ->column();

        if ($rootIds !== []) {
            // Detach the children first: letting the foreign key's ON DELETE SET NULL do it
            // would work, but only where the constraint actually exists — it was added in a
            // try/catch (m170830_122439) and may be missing on older installations.
            $this->update('cfiles_folder', ['parent_folder_id' => null], ['parent_folder_id' => $rootIds]);
            $this->update('cfiles_file', ['parent_folder_id' => null], ['parent_folder_id' => $rootIds]);

            /** @var Folder[] $roots */
            $roots = Folder::find()->where(['id' => $rootIds])->all();

            foreach ($roots as $root) {
                // Through the model, so the Content record and its search index entry go too.
                $root->hardDelete();
            }
        }

        $this->dropColumn('cfiles_folder', 'type');
    }

    public function safeDown()
    {
        echo "m260827_140000_drop_root_folder cannot be reverted.\n";

        return false;
    }
}
