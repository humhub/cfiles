<?php

use humhub\modules\cfiles\models\Folder;
use yii\db\Migration;

/**
 * Removes the "Files from the stream" marker folders.
 *
 * The virtual folder never held anything: it was a `cfiles_folder` row of type `posted`
 * whose contents were computed on the fly from files attached to posts and comments. With
 * the feature gone the row is a dangling content record that would still show up in search
 * and in the stream, so it is deleted outright.
 *
 * Nothing of value is lost — no file was ever parented to it, and the posts those files are
 * attached to are untouched.
 */
class m260827_120000_remove_posted_files_folder extends Migration
{
    /**
     * The `cfiles_folder.type` value the folder used to carry. Inlined because the constant
     * it came from (`Folder::TYPE_FOLDER_POSTED`) no longer exists — a migration has to keep
     * describing the schema of its own moment.
     */
    private const TYPE_FOLDER_POSTED = 'posted';

    public function safeUp()
    {
        /** @var Folder[] $folders */
        $folders = Folder::find()
            ->where(['cfiles_folder.type' => self::TYPE_FOLDER_POSTED])
            ->all();

        foreach ($folders as $folder) {
            // Through the model rather than by SQL, so the content record, its comments,
            // likes, follows and search index entry go with it.
            $folder->hardDelete();
        }
    }

    public function safeDown()
    {
        echo "m260827_120000_remove_posted_files_folder cannot be reverted.\n";

        return false;
    }
}
