<?php
namespace verbb\formie\base;

use verbb\formie\elements\Submission;
use verbb\formie\events\ModifyMiscellaneousPayloadEvent;

use Craft;
use craft\helpers\Html;
use craft\helpers\Json;
use craft\helpers\StringHelper;
use craft\helpers\UrlHelper;

use yii\helpers\Markdown;

abstract class HelpDesk extends Integration
{
    // Static Methods
    // =========================================================================

    public static function typeName(): string
    {
        return Craft::t('formie', 'Help Desk');
    }


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
        return UrlHelper::cpUrl('formie/settings/help-desk/edit/' . $this->id);
    }

    public function getIconUrl(): string
    {
        $handle = $this->getClassHandle();

        return Craft::$app->getAssetManager()->getPublishedUrl('@verbb/formie/web/assets/cp/dist/', true, "img/helpdesk/{$handle}.svg");
    }

    public function getSettingsHtml(): ?string
    {
        $handle = $this->getClassHandle();
        $variables = $this->getSettingsHtmlVariables();

        return Craft::$app->getView()->renderTemplate("formie/integrations/help-desk/{$handle}/_plugin-settings", $variables);
    }

    public function getFormSettingsHtml($form): string
    {
        $handle = $this->getClassHandle();
        $variables = $this->getFormSettingsHtmlVariables($form);

        return Craft::$app->getView()->renderTemplate("formie/integrations/help-desk/{$handle}/_form-settings", $variables);
    }

    public function getFrontEndJsVariables($field = null): ?array
    {
        return null;
    }

    public function getFieldMappingValues(Submission $submission, $fieldMapping, $fieldSettings = [])
    {
        // A quick shortcut to keep CRM's simple, just pass in a string to the namespace
        $fields = $this->getFormSettingValue($fieldSettings);

        return parent::getFieldMappingValues($submission, $fieldMapping, $fields);
    }
}
