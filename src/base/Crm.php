<?php
namespace verbb\formie\base;

use verbb\formie\elements\Submission;
use verbb\formie\events\SendIntegrationPayloadEvent;

use Craft;
use craft\helpers\ArrayHelper;
use craft\helpers\Html;
use craft\helpers\StringHelper;
use craft\helpers\UrlHelper;

use yii\helpers\Markdown;

abstract class Crm extends Integration
{
    // Static Methods
    // =========================================================================

    public static function typeName(): string
    {
        return Craft::t('formie', 'CRM');
    }


    // Public Methods
    // =========================================================================

    public function getIconUrl(): string
    {
        $handle = $this->getClassHandle();

        return Craft::$app->getAssetManager()->getPublishedUrl('@verbb/formie/web/assets/cp/dist/', true, "img/crm/{$handle}.svg");
    }

    /**
     * @inheritDoc
     */
    public function getSettingsHtml(): ?string
    {
        $handle = $this->getClassHandle();
        $variables = $this->getSettingsHtmlVariables();

        return Craft::$app->getView()->renderTemplate("formie/integrations/crm/{$handle}/_plugin-settings", $variables);
    }

    public function getFormSettingsHtml($form): string
    {
        $handle = $this->getClassHandle();
        $variables = $this->getFormSettingsHtmlVariables($form);

        return Craft::$app->getView()->renderTemplate("formie/integrations/crm/{$handle}/_form-settings", $variables);
    }

    public function getCpEditUrl(): string
    {
        return UrlHelper::cpUrl('formie/settings/crm/edit/' . $this->id);
    }

    public function getFieldMappingValues(Submission $submission, $fieldMapping, $fieldSettings = [])
    {
        // A quick shortcut to keep CRM's simple, just pass in a string to the namespace
        if (is_string($fieldSettings)) {
            $fields = $this->getFormSettingValue($fieldSettings);
        } else {
            $fields = $fieldSettings;
        }

        return parent::getFieldMappingValues($submission, $fieldMapping, $fields);
    }

    /**
     * Returns the front-end JS variables.
     */
    public function getFrontEndJsVariables($field = null): ?array
    {
        return null;
    }
}
