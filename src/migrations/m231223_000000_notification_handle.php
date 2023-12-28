<?php
namespace verbb\formie\migrations;

use craft\db\Migration;
use craft\db\Query;
use craft\helpers\StringHelper;

class m231223_000000_notification_handle extends Migration
{
    public function safeUp(): bool
    {
        if (!$this->db->columnExists('{{%formie_notifications}}', 'handle')) {
            $this->addColumn('{{%formie_notifications}}', 'handle', $this->string(64)->after('name'));
        }

        // Populate unique handles for all notifications
        $notifications = (new Query())
            ->select(['*'])
            ->from(['{{%formie_notifications}}'])
            ->all();

        foreach ($notifications as $notification) {
            $this->update('{{%formie_notifications}}', [
                'handle' => $this->uniqueHandle($notification),
            ], ['id' => $notification['id']], [], false);
        }

        return true;
    }

    public function safeDown(): bool
    {
        echo "m231223_000000_notification_handle cannot be reverted.\n";
        return false;
    }

    private function uniqueHandle(array $notification): string
    {
        // Ensure that we limit the handle as appropriate
        $notificationHandle = StringHelper::toHandle($notification['name']);
        $notificationHandle = substr($notificationHandle, 0, 60);

        $increment = 1;
        $handle = $notificationHandle;

        // Generate a unique notification handle. Note that they're not unique globally, just per-form.
        while (true) {
            $existingNotification = (new Query())
                ->select(['*'])
                ->from(['{{%formie_notifications}}'])
                ->where(['handle' => $handle, 'formId' => $notification['formId']])
                ->one();

            if (!$existingNotification) {
                return $handle;
            }

            $handle = $notificationHandle . $increment;

            $increment++;
        }
    }
}
