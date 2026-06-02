<?php
namespace verbb\formie\base;

use verbb\formie\base\FormInterface;
use verbb\formie\elements\Submission;
use verbb\formie\events\ModifyMiscellaneousPayloadEvent;
use verbb\formie\helpers\SchemaHelper;

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
