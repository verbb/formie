<?php
namespace verbb\formie\migrations;

use verbb\formie\helpers\Table;

use Craft;
use craft\db\Migration;
use craft\db\Query;

class m261121_000000_non_primary_form_references extends Migration
{
    // Public Methods
    // =========================================================================

    public function safeUp(): bool
    {
        if (!Craft::$app->getIsMultiSite()) {
            return true;
        }

        // Formie 3 forms weren't localized, so a form may only have had rows on a site that is no longer primary.
        // Earlier upgrades looked forms up on the primary site and left these with Formie 3 field handles.
        // Site availability has since added rows on every site, but such a form's source site isn't the primary one.
        $formIds = (new Query())
            ->select(['id'])
            ->from(Table::FORMIE_FORMS)
            ->where(['not', ['sourceSiteId' => null]])
            ->andWhere(['not', ['sourceSiteId' => Craft::$app->getSites()->getPrimarySite()->id]])
            ->column($this->db);

        if (!$formIds) {
            return true;
        }

        $config = ['db' => $this->db, 'formIds' => array_map('intval', $formIds)];

        return (new m260913_000000_notification_field_references($config))->safeUp()
            && (new m260913_000001_integration_field_references($config))->safeUp()
            && (new m260928_010000_reference_contract($config))->safeUp();
    }

    public function safeDown(): bool
    {
        echo "m261121_000000_non_primary_form_references cannot be reverted.\n";

        return false;
    }
}
