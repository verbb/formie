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

abstract class Messaging extends Integration
{
    // Static Methods
    // =========================================================================

    public static function typeName(): string
    {
        return Craft::t('formie', 'Messaging');
    }


    // Public Methods
    // =========================================================================

    public function getType(): string
    {
        return self::TYPE_MESSAGING;
    }

    public function getCategory(): string
    {
        return self::CATEGORY_MESSAGING;
    }

    public function getCpEditUrl(): string
    {
        return UrlHelper::cpUrl('formie/settings/messaging/edit/' . $this->id);
    }

    public function getIconUrl(): string
    {
        $handle = $this->getClassHandle();

        return Craft::$app->getAssetManager()->getPublishedUrl('@verbb/formie/web/assets/cp/dist/', true, "img/messaging/{$handle}.svg");
    }

    public function getSettingsHtml(): ?string
    {
        $handle = $this->getClassHandle();
        $variables = $this->getSettingsHtmlVariables();

        return Craft::$app->getView()->renderTemplate("formie/integrations/messaging/{$handle}/_plugin-settings", $variables);
    }

    public function getFormSettingsHtml($form): string
    {
        $handle = $this->getClassHandle();
        $variables = $this->getFormSettingsHtmlVariables($form);

        return Craft::$app->getView()->renderTemplate("formie/integrations/messaging/{$handle}/_form-settings", $variables);
    }

    public function getFrontEndJsVariables($field = null): ?array
    {
        return null;
    }
}
