<?php
namespace verbb\formie\migrations;

use verbb\formie\enums\CompletionBehavior;
use verbb\formie\helpers\Table;

use craft\db\Migration;
use craft\db\Query;
use craft\helpers\Json;

class m260912_000000_completion_vocabulary extends Migration
{
    // Public Methods
    // =========================================================================

    public function safeUp(): bool
    {
        $this->_renameEntryColumns(Table::FORMIE_FORMS);
        $this->_renameEntryColumns(Table::FORMIE_STENCILS);
        $this->_migrateJsonColumn(Table::FORMIE_FORMS, 'settings');
        $this->_migrateJsonColumn(Table::FORMIE_STENCILS, 'data', true);

        return true;
    }

    public function safeDown(): bool
    {
        echo "m260912_000000_completion_vocabulary cannot be reverted.\n";

        return false;
    }


    // Private Methods
    // =========================================================================

    private function _renameEntryColumns(string $table): void
    {
        foreach ([
            'submitActionEntryId' => 'redirectEntryId',
            'submitActionEntrySiteId' => 'redirectEntrySiteId',
        ] as $legacy => $canonical) {
            if ($this->db->columnExists($table, $legacy) && !$this->db->columnExists($table, $canonical)) {
                $this->renameColumn($table, $legacy, $canonical);
            }
        }
    }

    private function _migrateJsonColumn(string $table, string $column, bool $nestedSettings = false): void
    {
        foreach ((new Query())->select(['id', $column])->from($table)->each() as $row) {
            $decoded = Json::decodeIfJson($row[$column] ?? null);

            if (!is_array($decoded)) {
                continue;
            }

            $migrated = $decoded;

            if ($nestedSettings) {
                if (isset($migrated['settings']) && is_array($migrated['settings'])) {
                    $migrated['settings'] = $this->_migrateSettings($migrated['settings']);
                }
            } else {
                $migrated = $this->_migrateSettings($migrated);
            }

            if ($migrated !== $decoded) {
                $this->update($table, [$column => Json::encode($migrated)], ['id' => $row['id']], [], false);
            }
        }
    }

    private function _migrateSettings(array $data): array
    {
        $aliases = [
            'submitActionTab' => 'redirectTarget',
            'submitActionUrl' => 'redirectUrl',
            'submitActionEntry' => 'redirectEntry',
            'submitActionFormHide' => 'hideFormAfterSubmit',
            'submitActionMessage' => 'successMessage',
            'submitActionMessageTimeout' => 'successMessageTimeout',
            'submitActionMessagePosition' => 'successMessagePosition',
        ];

        foreach ($aliases as $legacy => $canonical) {
            $canonicalMissing = !array_key_exists($canonical, $data);

            if ($legacy === 'submitActionUrl') {
                $canonicalMissing = $canonicalMissing || $data[$canonical] === null || $data[$canonical] === '';
            }

            if (array_key_exists($legacy, $data) && $canonicalMissing) {
                $data[$canonical] = $data[$legacy];
            }
            unset($data[$legacy]);
        }

        if (isset($data['submitAction']) && in_array($data['submitAction'], ['message', 'entry', 'url', 'reload', 'reset'], true)) {
            $action = $data['submitAction'];
            $data['completionBehavior'] ??= in_array($action, ['entry', 'url'], true) ? CompletionBehavior::Redirect->value : $action;
            $data['completionRedirectSource'] ??= $action === 'entry' ? 'entry' : 'url';
            unset($data['submitAction']);
        }

        return $data;
    }
}
