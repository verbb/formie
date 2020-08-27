<?php
namespace verbb\formie\base;

use verbb\formie\Formie;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\models\IntegrationField;

use Craft;
use craft\helpers\StringHelper;
use craft\helpers\UrlHelper;

abstract class Crm extends Integration implements IntegrationInterface
{
    // Static Methods
    // =========================================================================
    
    /**
     * @inheritDoc
     */
    public static function typeName(): string
    {
        return Craft::t('formie', 'CRM');
    }


    // Public Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
    public function getIconUrl(): string
    {
        $handle = StringHelper::toKebabCase($this->displayName());

        return Craft::$app->getAssetManager()->getPublishedUrl("@verbb/formie/web/assets/crm/dist/img/{$handle}.svg", true);
    }

    /**
     * @inheritDoc
     */
    public function getSettingsHtml(): string
    {
        $handle = StringHelper::toKebabCase($this->displayName());

        return Craft::$app->getView()->renderTemplate("formie/integrations/crm/{$handle}/_plugin-settings", [
            'integration' => $this,
        ]);
    }

    /**
     * @inheritDoc
     */
    public function getFormSettingsHtml(Form $form): string
    {
        $handle = StringHelper::toKebabCase($this->displayName());

        return Craft::$app->getView()->renderTemplate("formie/integrations/crm/{$handle}/_form-settings", [
            'integration' => $this,
            'form' => $form,
        ]);
    }

    /**
     * @inheritDoc
     */
    public function getCpEditUrl(): string
    {
        return UrlHelper::cpUrl('formie/settings/crm/edit/' . $this->id);
    }

    /**
     * @inheritDoc
     */
    public function getFormSettings($useCache = true)
    {
        $settings = parent::getFormSettings($useCache);

        // Convert back to models from the cache
        foreach ($settings as $key => $setting) {
            foreach ($setting as $k => $value) {
                // Probably re-structure this for CRM's, but check if its a 'field'
                if (isset($value['handle'])) {
                    $settings[$key][$k] = new IntegrationField($value);
                } if (isset($value['fields'])) {
                    foreach ($value['fields'] as $i => $fieldConfig) {
                        $settings[$key][$k]['fields'][$i] = new IntegrationField($fieldConfig);
                    }
                }
            }
        }

        return $settings;
    }
}
