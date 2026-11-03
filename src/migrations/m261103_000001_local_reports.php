<?php
namespace verbb\formie\migrations;

use verbb\formie\helpers\Table;
use verbb\formie\services\Reports;
use verbb\formie\services\ScheduledReports;

use Craft;
use craft\db\Migration;
use craft\db\Query;
use craft\helpers\Db;
use craft\helpers\Json;
use craft\helpers\ProjectConfig as ProjectConfigHelper;

use DateTime;

class m261103_000001_local_reports extends Migration
{
    // Public Methods
    // =========================================================================

    public function safeUp(): bool
    {
        if (!$this->db->columnExists(Table::FORMIE_REPORTS, 'settings')) {
            $this->addColumn(Table::FORMIE_REPORTS, 'settings', $this->text()->after('sortOrder'));
        }

        if (!$this->db->columnExists(Table::FORMIE_SCHEDULED_REPORTS, 'delivery')) {
            $this->addColumn(Table::FORMIE_SCHEDULED_REPORTS, 'delivery', $this->text()->after('lastSentAt'));
        }

        // Filters, columns and schedule details only exist in project config until now.
        $this->_importReports();
        $this->_importScheduledReports();

        return true;
    }

    public function safeDown(): bool
    {
        return false;
    }


    // Private Methods
    // =========================================================================

    /**
     * Migrations run before project config is applied, and nothing will listen for these keys
     * afterwards. YAML deployed with this upgrade is therefore read now, including removals.
     *
     * @return array{0: array, 1: array} The entries to keep, and the UIDs removed by pending YAML.
     */
    private function _getConfigEntries(string $key): array
    {
        $projectConfig = Craft::$app->getProjectConfig();
        $stored = $projectConfig->get($key) ?? [];

        if (!$projectConfig->getDoesExternalConfigExist()) {
            return [$this->_unpackEntries($stored), []];
        }

        $incoming = $projectConfig->get($key, true) ?? [];

        return [$this->_unpackEntries($incoming), array_keys(array_diff_key($stored, $incoming))];
    }

    private function _unpackEntries(array $entries): array
    {
        return array_filter(array_map(
            static fn(mixed $entry) => is_array($entry) ? ProjectConfigHelper::unpackAssociativeArray($entry) : null,
            $entries,
        ));
    }

    private function _importReports(): void
    {
        [$entries, $removedUids] = $this->_getConfigEntries(Reports::CONFIG_REPORTS_KEY);

        foreach ($entries as $uid => $entry) {
            $row = $this->_findRow(Table::FORMIE_REPORTS, $uid);
            $handle = (string)($entry['handle'] ?? '');
            $handleOwnerId = (new Query())->from(Table::FORMIE_REPORTS)->select('id')->where(['handle' => $handle])->scalar($this->db);

            if ($handle === '' || ($handleOwnerId && (int)$handleOwnerId !== (int)($row['id'] ?? 0))) {
                Craft::warning("Skipped importing report “{$uid}” because its handle “{$handle}” is unavailable.", __METHOD__);

                continue;
            }

            $columns = [
                'name' => (string)($entry['name'] ?? $handle),
                'handle' => $handle,
                'sortOrder' => $entry['sortOrder'] ?? null,
                'settings' => is_array($entry['settings'] ?? null) ? Json::encode($entry['settings']) : null,
                'dateDeleted' => null,
            ];

            if ($row) {
                $this->update(Table::FORMIE_REPORTS, $columns, ['id' => $row['id']], [], false);
            } else {
                $this->insert(Table::FORMIE_REPORTS, [...$columns, 'uid' => $uid]);
            }
        }

        $this->_softDeleteRows(Table::FORMIE_REPORTS, $removedUids);
    }

    private function _importScheduledReports(): void
    {
        [$entries, $removedUids] = $this->_getConfigEntries(ScheduledReports::CONFIG_SCHEDULED_REPORTS_KEY);

        foreach ($entries as $uid => $entry) {
            $delivery = is_array($entry['delivery'] ?? null) ? $entry['delivery'] : [];
            $reportUid = $entry['reportUid'] ?? $delivery['reportUid'] ?? null;
            $reportId = $reportUid ? Db::idByUid(Table::FORMIE_REPORTS, (string)$reportUid) : null;

            if (!$reportId) {
                Craft::warning("Skipped importing scheduled report “{$uid}” because its report is missing.", __METHOD__);

                continue;
            }

            $columns = [
                'reportId' => $reportId,
                'name' => (string)($entry['name'] ?? $uid),
                'enabled' => (bool)($entry['enabled'] ?? true),
                'delivery' => Json::encode([...$delivery, 'reportUid' => $reportUid]),
                'dateDeleted' => null,
            ];
            $row = $this->_findRow(Table::FORMIE_SCHEDULED_REPORTS, $uid);

            // Updating in place keeps lastSentAt, so nothing is sent again after the upgrade.
            if ($row) {
                $this->update(Table::FORMIE_SCHEDULED_REPORTS, $columns, ['id' => $row['id']], [], false);
            } else {
                $this->insert(Table::FORMIE_SCHEDULED_REPORTS, [...$columns, 'uid' => $uid]);
            }
        }

        $this->_softDeleteRows(Table::FORMIE_SCHEDULED_REPORTS, $removedUids);
    }

    private function _findRow(string $table, string $uid): ?array
    {
        return (new Query())->from($table)->select(['id'])->where(['uid' => $uid])->one($this->db) ?: null;
    }

    private function _softDeleteRows(string $table, array $uids): void
    {
        if ($uids !== []) {
            $this->update($table, ['dateDeleted' => Db::prepareDateForDb(new DateTime())], ['uid' => $uids, 'dateDeleted' => null], [], false);
        }
    }
}
