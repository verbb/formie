<?php
namespace verbb\formie\base;

use Craft;
use craft\helpers\UrlHelper;

abstract class Messaging extends Integration implements DispatchableIntegrationInterface
{
    // Static Methods
    // =========================================================================

    public static function typeName(): string
    {
        return Craft::t('formie', 'Messaging');
    }


    // Traits
    // =========================================================================

    use DispatchableIntegrationTrait;


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
        return UrlHelper::cpUrl('formie/integrations/messaging/edit/' . $this->id);
    }

    public function getIconUrl(): string
    {
        $handle = $this->getClassHandle();

        return Craft::$app->getAssetManager()->getPublishedUrl('@verbb/formie/web/assets/cp/dist/', true, "icons/messaging/{$handle}.svg");
    }

    public function getSettingsHtml(): ?string
    {
        $handle = $this->getClassHandle();
        $variables = $this->getSettingsHtmlVariables();

        return Craft::$app->getView()->renderTemplate("formie/integrations/messaging/{$handle}/_plugin-settings", $variables);
    }


    // Protected Methods
    // =========================================================================

    protected function defineFormSettingsSchema(FormInterface $form): array
    {
        $schema = parent::defineFormSettingsSchema($form);
        $schema[] = $this->getOptInFieldSchema();

        return $schema;
    }
}
