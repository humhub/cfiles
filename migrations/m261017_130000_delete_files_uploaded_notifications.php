<?php

use humhub\components\Migration;

/**
 * Deletes the notifications and queued jobs of the batched upload notification of 0.19.
 *
 * Since 1.0.0 every uploaded file is announced by the core's new-content notification, and
 * `notifications\FilesUploaded` and `jobs\SendFileUploadNotification` are gone. A stored
 * notification of the removed class would be skipped by the list but still counted as unseen,
 * and a queued job of the removed class could no longer be run.
 */
class m261017_130000_delete_files_uploaded_notifications extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->delete('notification', ['class' => 'humhub\modules\cfiles\notifications\FilesUploaded']);

        if ($this->db->getTableSchema('queue', true) !== null) {
            $this->delete('queue', ['like', 'job', 'SendFileUploadNotification']);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m261017_130000_delete_files_uploaded_notifications cannot be reverted.\n";

        return false;
    }
}
