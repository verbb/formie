<?php
namespace verbb\formie\migrations;

use verbb\formie\helpers\Table;

use craft\db\Migration;
use craft\db\Query;

/**
 * Craft stores every permission name lowercased, and compares them with a strict (case-sensitive)
 * `in_array()` when checking user/group permissions. Some earlier Formie migrations seeded rows in
 * `userpermissions` straight from the camel-cased permission constants, which produced rows Craft
 * could link to (MySQL lookups are case-insensitive) but could never match on read.
 *
 * The visible symptom was permissions such as “Access reports” appearing to never save: the group
 * was linked to the camel-cased row, but the permission editor and `can()` checks both compared
 * against the lowercase name and found nothing.
 *
 * This normalises any mixed-case rows, merging them into an existing lowercase row where one
 * already exists so no assignments are lost.
 */
class m260712_000000_lowercase_permission_names extends Migration
{
    // Public Methods
    // =========================================================================

    public function safeUp(): bool
    {
        $rows = (new Query())
            ->select(['id', 'name'])
            ->from([Table::USERPERMISSIONS])
            ->all($this->db);

        // Group every row by its lowercase name so mixed-case duplicates can be merged
        $rowsByLowerName = [];

        foreach ($rows as $row) {
            $rowsByLowerName[strtolower((string)$row['name'])][] = [
                'id' => (int)$row['id'],
                'name' => (string)$row['name'],
            ];
        }

        foreach ($rowsByLowerName as $lowerName => $group) {
            $alreadyCorrect = count($group) === 1 && $group[0]['name'] === $lowerName;

            if ($alreadyCorrect) {
                continue;
            }

            // Prefer an existing lowercase row as the canonical target, otherwise promote the first
            $canonical = null;

            foreach ($group as $row) {
                if ($row['name'] === $lowerName) {
                    $canonical = $row;
                    break;
                }
            }

            if (!$canonical) {
                $canonical = $group[0];
                $this->update(Table::USERPERMISSIONS, ['name' => $lowerName], ['id' => $canonical['id']]);
            }

            foreach ($group as $row) {
                if ($row['id'] === $canonical['id']) {
                    continue;
                }

                $this->_repointAssignments(Table::USERPERMISSIONS_USERS, 'userId', $row['id'], $canonical['id']);
                $this->_repointAssignments(Table::USERPERMISSIONS_USERGROUPS, 'groupId', $row['id'], $canonical['id']);

                $this->delete(Table::USERPERMISSIONS, ['id' => $row['id']]);
            }
        }

        return true;
    }

    public function safeDown(): bool
    {
        echo "m260712_000000_lowercase_permission_names cannot be reverted.\n";

        return false;
    }


    // Private Methods
    // =========================================================================

    /**
     * Moves assignments from a duplicate permission row onto the canonical row, skipping any
     * assignment that would collide with an existing row (the join tables are uniquely indexed).
     */
    private function _repointAssignments(string $table, string $ownerColumn, int $fromPermissionId, int $toPermissionId): void
    {
        $ownerIds = (new Query())
            ->select([$ownerColumn])
            ->from([$table])
            ->where(['permissionId' => $fromPermissionId])
            ->column($this->db);

        if (!$ownerIds) {
            return;
        }

        $existingOwnerIds = (new Query())
            ->select([$ownerColumn])
            ->from([$table])
            ->where(['permissionId' => $toPermissionId])
            ->column($this->db);

        $existingOwnerIds = array_map('intval', $existingOwnerIds);
        $insert = [];

        foreach (array_unique(array_map('intval', $ownerIds)) as $ownerId) {
            if (in_array($ownerId, $existingOwnerIds, true)) {
                continue;
            }

            $insert[] = [$toPermissionId, $ownerId];
        }

        if ($insert !== []) {
            $this->batchInsert($table, ['permissionId', $ownerColumn], $insert);
        }

        $this->delete($table, ['permissionId' => $fromPermissionId]);
    }
}
