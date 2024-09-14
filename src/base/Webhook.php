<?php
namespace verbb\formie\base;

use verbb\formie\Formie;
use verbb\formie\elements\Submission;
use verbb\formie\events\ModifyWebhookPayloadEvent;

use Craft;
use craft\helpers\App;
use craft\helpers\Html;
use craft\helpers\Json;
use craft\helpers\StringHelper;
use craft\helpers\UrlHelper;

use yii\helpers\Markdown;

abstract class Webhook extends Integration
{
    // Constants
    // =========================================================================

    public const EVENT_MODIFY_WEBHOOK_PAYLOAD = 'modifyWebhookPayload';


    // Static Methods
    // =========================================================================

    public static function typeName(): string
    {
        return Craft::t('formie', 'Webhooks');
    }


    // Public Methods
    // =========================================================================

    public function getIconUrl(): string
    {
        $handle = $this->getClassHandle();

        return Craft::$app->getAssetManager()->getPublishedUrl('@verbb/formie/web/assets/cp/dist/', true, "img/webhooks/{$handle}.svg");
    }

    /**
     * @inheritDoc
     */
    public function getSettingsHtml(): ?string
    {
        $handle = $this->getClassHandle();
        $variables = $this->getSettingsHtmlVariables();

        return Craft::$app->getView()->renderTemplate("formie/integrations/webhooks/{$handle}/_plugin-settings", $variables);
    }

    public function getFormSettingsHtml($form): string
    {
        $handle = $this->getClassHandle();
        $variables = $this->getFormSettingsHtmlVariables($form);

        return Craft::$app->getView()->renderTemplate("formie/integrations/webhooks/{$handle}/_form-settings", $variables);
    }

    public function getCpEditUrl(): string
    {
        return UrlHelper::cpUrl('formie/settings/webhooks/edit/' . $this->id);
    }


    // Protected Methods
    // =========================================================================

    protected function generatePayloadValues(Submission $submission): array
    {
        $payload = $this->generateSubmissionPayloadValues($submission);

        // Fire a 'modifyWebhookPayload' event
        $event = new ModifyWebhookPayloadEvent([
            'submission' => $submission,
            'payload' => $payload,
        ]);
        $this->trigger(self::EVENT_MODIFY_WEBHOOK_PAYLOAD, $event);

        return $event->payload;
    }

    protected function getWebhookUrl($url, Submission $submission): bool|string|null
    {
        $url = Formie::$plugin->getTemplates()->renderObjectTemplate($url, $submission);

        return App::parseEnv($url);
    }
}
