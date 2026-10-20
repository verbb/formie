<?php
namespace verbb\formie\helpers;

use verbb\formie\migrations\m260418_000000_field_definitions_and_form_fields;

use Craft;
use craft\base\PluginInterface;
use craft\db\Connection;
use craft\db\Query;

use RuntimeException;

class MigrationHelper
{
    // Static Methods
    // =========================================================================

    public static function savePluginSettingsIfAllowed(PluginInterface $plugin, array $settings): bool
    {
        if (!Craft::$app->getConfig()->getGeneral()->allowAdminChanges) {
            return true;
        }

        return Craft::$app->getPlugins()->savePluginSettings($plugin, $settings);
    }

    /**
     * Resume an interrupted field-table rename even if a later migration is pending.
     */
    public static function ensureFieldDefinitions(Connection $db): void
    {
        if ($db->tableExists(Table::FORMIE_FIELDS, true)) {
            return;
        }

        self::assertFieldDefinitionsRecoverable($db);
        (new m260418_000000_field_definitions_and_form_fields(['db' => $db]))->safeUp();
    }

    public static function assertFieldDefinitionsRecoverable(Connection $db): void
    {
        $schema = $db->getTableSchema(Table::FORMIE_FORM_FIELDS, true);
        $legacyColumns = ['id', 'syncId', 'type', 'label', 'handle', 'settings', 'dateCreated', 'dateUpdated', 'uid'];
        $hasLegacyDefinitions = $schema && !array_diff($legacyColumns, array_keys($schema->columns));

        // Once placements have been linked, their settings may already have been
        // reduced to instance overrides. They cannot replace a lost definition.
        $hasConvertedRows = $hasLegacyDefinitions && isset($schema->columns['fieldId'])
            && (new Query())->from(Table::FORMIE_FORM_FIELDS)->where(['not', ['fieldId' => null]])->exists($db);

        if (!$hasLegacyDefinitions || $hasConvertedRows) {
            throw new RuntimeException('Formie field definitions are missing and cannot be safely recovered from the remaining field placements. Restore a complete pre-update database backup and retry the update, or contact Verbb support with the backup and full migration log.');
        }
    }
}
