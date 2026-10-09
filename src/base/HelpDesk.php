<?php
namespace verbb\formie\base;

use verbb\formie\elements\Submission;

use Craft;
use craft\helpers\UrlHelper;

abstract class HelpDesk extends Integration implements DispatchableIntegrationInterface
{
    // Static Methods
    // =========================================================================

    public static function typeName(): string
    {
        return Craft::t('formie', 'Help Desk');
    }


    // Traits
    // =========================================================================

    use DispatchableIntegrationTrait;


    // Public Methods
    // =========================================================================

    public function getType(): string
    {
        return self::TYPE_HELP_DESK;
    }

    public function getCategory(): string
    {
        return self::CATEGORY_HELP_DESK;
    }

    public function getCpEditUrl(): string
    {
        return UrlHelper::cpUrl('formie/integrations/help-desk/edit/' . $this->id);
    }

    public function getIconUrl(): string
    {
        $handle = $this->getClassHandle();

        return Craft::$app->getAssetManager()->getPublishedUrl('@verbb/formie/web/assets/cp/dist/', true, "icons/helpdesk/{$handle}.svg");
    }

    public function getSettingsHtml(): ?string
    {
        $handle = $this->getClassHandle();
        $variables = $this->getSettingsHtmlVariables();

        return Craft::$app->getView()->renderTemplate("formie/integrations/help-desk/{$handle}/_plugin-settings", $variables);
    }

    public function getFieldMappingValues(Submission $submission, $fieldMapping, $fieldSettings = null)
    {
        // A quick shortcut to keep CRM's simple, just pass in a string to the namespace
        $fields = is_string($fieldSettings) ? $this->getConfigValue($fieldSettings) : $fieldSettings;

        return parent::getFieldMappingValues($submission, $fieldMapping, $fields);
    }
}
