<?php
namespace verbb\formie\migrations;

use verbb\formie\Formie;
use verbb\formie\helpers\Table;

use Craft;
use craft\db\Migration;
use craft\db\Query;

class m260629_000000_propagate_form_element_site_titles extends Migration
{
    // Public Methods
    // =========================================================================

    public function safeUp(): bool
    {
        if (!Craft::$app->getIsMultiSite()) {
            return true;
        }

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

        return true;
    }

    public function safeDown(): bool
    {
        return true;
    }
}
