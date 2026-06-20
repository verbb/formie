<?php
namespace verbb\formie\migrations;

use verbb\formie\Formie;
use verbb\formie\helpers\Table;

use Craft;
use craft\db\Migration;
use craft\db\Query;
use craft\helpers\Json;

class m260630_000000_remap_form_site_override_keys extends Migration
{
    // Public Methods
    // =========================================================================

    public function safeUp(): bool
    {
        if (!Craft::$app->getIsMultiSite()) {
            return true;
        }

        $service = Formie::$plugin->getFormSiteOverrides();
        $rows = (new Query())
            ->select(['id', 'formId', 'overrides'])
            ->from(Table::FORMIE_FORM_SITE_OVERRIDES)
            ->all();

        foreach ($rows as $row) {
            $overrides = Json::decodeIfJson($row['overrides'] ?? null);

            if (!is_array($overrides) || $overrides === []) {
                continue;
            }

            $remapped = $service->remapOverrideKeysForForm((int)$row['formId'], $overrides);

            if (Json::encode($remapped) === Json::encode($overrides)) {
                continue;
            }

            $this->update(
                Table::FORMIE_FORM_SITE_OVERRIDES,
                ['overrides' => $remapped],
                ['id' => $row['id']],
            );
        }

        return true;
    }

    public function safeDown(): bool
    {
        return true;
    }
}
