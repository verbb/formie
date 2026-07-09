<?php
namespace verbb\formie\migrations;

use verbb\formie\Formie;
use verbb\formie\helpers\HandleHelper;
use verbb\formie\helpers\Table;
use verbb\formie\models\Notification;

use craft\db\Migration;
use craft\db\Query;
use craft\helpers\Json;
use craft\helpers\StringHelper;
use craft\validators\HandleValidator;

class m260710_000000_fix_invalid_notification_handles extends Migration
{
    // Public Methods
    // =========================================================================

    public function safeUp(): bool
    {
        $this->_fixNotificationRecords();
        $this->_fixStencilNotifications();

        return true;
    }

    public function safeDown(): bool
    {
        echo "m260710_000000_fix_invalid_notification_handles cannot be reverted.\n";

        return false;
    }


    // Private Methods
    // =========================================================================

    private function _fixNotificationRecords(): void
    {
        if (!$this->db->tableExists(Table::FORMIE_NOTIFICATIONS) || !$this->db->columnExists(Table::FORMIE_NOTIFICATIONS, 'handle')) {
            return;
        }

        $notifications = (new Query())
            ->select(['id', 'formId', 'name', 'handle'])
            ->from(Table::FORMIE_NOTIFICATIONS)
            ->all();

        foreach ($notifications as $notification) {
            $handle = trim((string)($notification['handle'] ?? ''));

            if ($this->_isValidHandle($handle)) {
                continue;
            }

            // Legacy installs can carry invalid handles (e.g. "myNotification.1") from early backfills.
            // Regenerate from the notification name so the form can save again.
            $model = new Notification([
                'formId' => (int)$notification['formId'],
                'name' => $notification['name'],
            ]);

            $newHandle = Formie::$plugin->getNotifications()->getUniqueNotificationHandle($model);

            $this->update(Table::FORMIE_NOTIFICATIONS, [
                'handle' => $newHandle,
            ], ['id' => $notification['id']], [], false);
        }
    }

    private function _fixStencilNotifications(): void
    {
        if (!$this->db->tableExists(Table::FORMIE_STENCILS) || !$this->db->columnExists(Table::FORMIE_STENCILS, 'data')) {
            return;
        }

        $stencils = (new Query())
            ->select(['id', 'data'])
            ->from(Table::FORMIE_STENCILS)
            ->all();

        foreach ($stencils as $stencil) {
            $data = Json::decodeIfJson($stencil['data'] ?? null);

            if (!is_array($data) || empty($data['notifications']) || !is_array($data['notifications'])) {
                continue;
            }

            $hasChanged = false;
            $existingHandles = [];

            foreach ($data['notifications'] as &$notification) {
                if (!is_array($notification)) {
                    continue;
                }

                $handle = trim((string)($notification['handle'] ?? ''));

                if ($this->_isValidHandle($handle)) {
                    $existingHandles[] = $handle;
                    continue;
                }

                $baseHandle = StringHelper::toHandle($notification['name'] ?? $handle ?: 'notification');
                $newHandle = HandleHelper::getUniqueHandle($existingHandles, $baseHandle);
                $existingHandles[] = $newHandle;

                $notification['handle'] = $newHandle;
                $hasChanged = true;
            }

            unset($notification);

            if ($hasChanged) {
                $this->update(Table::FORMIE_STENCILS, [
                    'data' => $data,
                ], ['id' => $stencil['id']], [], false);
            }
        }
    }

    private function _isValidHandle(?string $handle): bool
    {
        if (!$handle) {
            return false;
        }

        return (bool)preg_match('/^' . HandleValidator::$handlePattern . '$/', $handle);
    }
}
