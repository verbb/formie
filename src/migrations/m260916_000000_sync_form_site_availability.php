<?php
namespace verbb\formie\migrations;

use verbb\formie\elements\Form;
use verbb\formie\Formie;

use Craft;
use craft\db\Migration;

class m260916_000000_sync_form_site_availability extends Migration
{
    // Public Methods
    // =========================================================================

    public function safeUp(): bool
    {
        if (!Craft::$app->getIsMultiSite()) {
            return true;
        }

        // Older nonlocalized forms may only have a primary-site row. Populate
        // availability from their current policy without resaving form content.
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
}
