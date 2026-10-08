<?php
namespace verbb\formie\migrations;

use verbb\formie\Formie;
use verbb\formie\elements\Form;
use verbb\formie\helpers\Table;

use Craft;
use craft\db\Migration;
use craft\db\Query;
use craft\helpers\Json;

class m260916_000000_sync_form_site_availability extends Migration
{
    // Public Methods
    // =========================================================================

    public function safeUp(): bool
    {
        if (!Craft::$app->getIsMultiSite()) {
            return true;
        }

        $this->_normalizeFormSettingsForHydration();

        // Older nonlocalized forms may only have a primary-site row. Populate availability from their current policy without resaving form content.
        $forms = Form::find()->forProjectConfig()->site('*')->unique()->status(null)->all();
        $overrides = Formie::$plugin->getFormSiteOverrides();
        $propagation = Formie::$plugin->getFormSitePropagation();

        foreach ($forms as $form) {
            $form->title = $overrides->resolveCanonicalFormTitle($form);
            $propagation->syncFormSites($form);
        }

        return true;
    }

    public function safeDown(): bool
    {
        return true;
    }

    private function _normalizeFormSettingsForHydration(): void
    {
        if (!$this->db->tableExists(Table::FORMIE_FORMS) || !$this->db->columnExists(Table::FORMIE_FORMS, 'settings')) {
            return;
        }

        foreach ((new Query())->select(['id', 'settings'])->from(Table::FORMIE_FORMS)->each() as $row) {
            $data = Json::decodeIfJson($row['settings'] ?? null);

            if (!is_array($data)) {
                continue;
            }

            $normalized = m260929_000000_form_integration_policy::normalize($data);

            if ($normalized !== $data) {
                $this->update(Table::FORMIE_FORMS, ['settings' => Json::encode($normalized)], ['id' => $row['id']], [], false);
            }
        }
    }
}
