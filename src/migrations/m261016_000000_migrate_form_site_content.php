<?php
namespace verbb\formie\migrations;

use verbb\formie\Formie;
use verbb\formie\helpers\Table;

use Craft;
use craft\db\Migration;
use craft\db\Query;
use craft\helpers\Json;

class m261016_000000_migrate_form_site_content extends Migration
{
    // Public Methods
    // =========================================================================

    public function safeUp(): bool
    {
        if (!Craft::$app->getIsMultiSite()) {
            return true;
        }

        // These services hydrate current Form elements, so all schema migrations must run first.
        $this->_remapOverrideKeys();
        $this->_syncElementSiteTitles();

        return true;
    }

    public function safeDown(): bool
    {
        return true;
    }


    // Private Methods
    // =========================================================================

    private function _syncElementSiteTitles(): void
    {
        $formIds = (new Query())
            ->select(['f.id'])
            ->from(['f' => Table::FORMIE_FORMS])
            ->innerJoin(['e' => Table::ELEMENTS], '[[e.id]] = [[f.id]]')
            ->where(['e.dateDeleted' => null])
            ->column();

        $propagation = Formie::$plugin->getFormSitePropagation();

        foreach ($formIds as $formId) {
            $propagation->syncElementSiteTitles((int)$formId);
        }
    }

    private function _remapOverrideKeys(): void
    {
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
    }
}
