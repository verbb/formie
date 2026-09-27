<?php
namespace verbb\formie\migrations;

use verbb\formie\helpers\Table;

use Craft;
use craft\db\Migration;

class m260927_030000_signature_access extends Migration
{
    // Public Methods
    // =========================================================================

    public function safeUp(): bool
    {
        $hadAccessKey = $this->db->columnExists(Table::FORMIE_SUBMISSIONS, 'signatureAccessKey');
        $storedSchemaVersion = Craft::$app->getPlugins()->getStoredPluginInfo('formie')['schemaVersion'] ?? null;
        $isLegacyUpgrade = is_string($storedSchemaVersion) && version_compare($storedSchemaVersion, '4.0.0', '<');

        if (!$hadAccessKey) {
            $this->addColumn(Table::FORMIE_SUBMISSIONS, 'signatureAccessKey', $this->string(64)->after('ipAddress'));
        }

        if (!$this->db->columnExists(Table::FORMIE_SUBMISSIONS, 'legacySignatureAccess')) {
            $this->addColumn(
                Table::FORMIE_SUBMISSIONS,
                'legacySignatureAccess',
                $this->boolean()->notNull()->defaultValue(false)->after('signatureAccessKey'),
            );
        }

        if ($isLegacyUpgrade && $hadAccessKey) {
            // Formie 3's nullable key is an intentional per-submission legacy marker.
            // Preserve it while keeping already-signed submissions on the signed path.
            $this->update(Table::FORMIE_SUBMISSIONS, ['legacySignatureAccess' => true], [
                'or',
                ['signatureAccessKey' => null],
                ['signatureAccessKey' => ''],
            ]);
        } elseif ($isLegacyUpgrade) {
            // Formie 2/3 submissions predate signature capabilities. Grandfather
            // them without granting unsigned access to native Formie 4 beta rows.
            $this->update(Table::FORMIE_SUBMISSIONS, ['legacySignatureAccess' => true]);
        }

        return true;
    }

    public function safeDown(): bool
    {
        echo "m260927_030000_signature_access cannot be reverted without losing Signature access state.\n";

        return false;
    }
}
