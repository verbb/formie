<?php
namespace verbb\formie\base;

use verbb\formie\Formie;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\events\ModifyAutomationPayloadEvent;
use verbb\formie\helpers\StringHelper;
use verbb\formie\models\Stencil;

use Craft;
use craft\helpers\App;
use craft\helpers\Html;
use craft\helpers\Json;
use craft\helpers\UrlHelper;

use yii\helpers\Markdown;

abstract class Automation extends Integration
{
    // Constants
    // =========================================================================

    public const EVENT_MODIFY_AUTOMATION_PAYLOAD = 'modifyAutomationPayload';

    // Backward-compatibility until Formie 4
    public const EVENT_MODIFY_WEBHOOK_PAYLOAD = 'modifyWebhookPayload';


    // Static Methods
    // =========================================================================

    public static function typeName(): string
    {
        return Craft::t('formie', 'Automations');
    }


    // Public Methods
    // =========================================================================

    public function getType(): string
    {
        return self::TYPE_AUTOMATION;
    }

    public function getCategory(): string
    {
        return self::CATEGORY_AUTOMATIONS;
    }

    public function getCpEditUrl(): ?string
    {
        return UrlHelper::cpUrl('formie/settings/automations/edit/' . $this->id);
    }

    public function getIconUrl(): string
    {
        $handle = $this->getClassHandle();

        return Craft::$app->getAssetManager()->getPublishedUrl('@verbb/formie/web/assets/cp/dist/', true, "img/automations/{$handle}.svg");
    }

    public function getSettingsHtml(): ?string
    {
        $handle = $this->getClassHandle();
        $variables = $this->getSettingsHtmlVariables();

        return Craft::$app->getView()->renderTemplate("formie/integrations/automations/{$handle}/_plugin-settings", $variables);
    }

    public function getFormSettingsHtml(Form|Stencil $form): string
    {
        $handle = $this->getClassHandle();
        $variables = $this->getFormSettingsHtmlVariables($form);

        return Craft::$app->getView()->renderTemplate("formie/integrations/automations/{$handle}/_form-settings", $variables);
    }


    // Protected Methods
    // =========================================================================

    protected function generatePayloadValues(Submission $submission): array
    {
        $payload = $this->generateSubmissionPayloadValues($submission);

        // Fire a 'modifyAutomationPayload' event
        $event = new ModifyAutomationPayloadEvent([
            'submission' => $submission,
            'payload' => $payload,
        ]);
        $this->trigger(self::EVENT_MODIFY_AUTOMATION_PAYLOAD, $event);

        // Backward-compatibility until Formie 4
        $this->trigger(self::EVENT_MODIFY_WEBHOOK_PAYLOAD, $event);

        return $event->payload;
    }

    protected function getEndpointUrl(string $url, Submission $submission): bool|string|null
    {
        $url = Formie::$plugin->getTemplates()->renderObjectTemplate($url, $submission);

        return App::parseEnv($url);
    }

    protected function getWebhookUrl($url, Submission $submission): bool|string|null
    {
        // Alias for backward compatibility. Remove at the next breakpoint
        Craft::$app->getDeprecator()->log(__METHOD__, 'The `getWebhookUrl` method has been deprecated. Use the `getEndpointUrl` method instead.');

        return $this->getEndpointUrl($url, $submission);
    }
}
