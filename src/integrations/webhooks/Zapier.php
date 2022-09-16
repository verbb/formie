<?php
namespace verbb\formie\integrations\webhooks;

use verbb\formie\Formie;
use verbb\formie\base\Integration;
use verbb\formie\base\Webhook;
use verbb\formie\elements\Submission;
use verbb\formie\models\IntegrationFormSettings;

use Craft;
use craft\helpers\Json;

use GuzzleHttp\Client;

use Throwable;

class Zapier extends Webhook
{
    // Static Methods
    // =========================================================================

    public static function supportsConnection(): bool
    {
        return false;
    }

    /**
     * @inheritDoc
     */
    public static function displayName(): string
    {
        return Craft::t('formie', 'Zapier');
    }
    

    // Properties
    // =========================================================================
    
    public ?string $webhook = null;
    

    // Public Methods
    // =========================================================================

    public function getDescription(): string
    {
        return Craft::t('formie', 'Send your form content to Zapier.');
    }

    /**
     * @inheritDoc
     */
    public function defineRules(): array
    {
        $rules = parent::defineRules();

        $rules[] = [['webhook'], 'required', 'on' => [Integration::SCENARIO_FORM]];

        return $rules;
    }

    public function fetchFormSettings(): IntegrationFormSettings
    {
        $settings = [];

        try {
            $formId = Craft::$app->getRequest()->getParam('formId');
            $form = Formie::$plugin->getForms()->getFormById($formId);

            // Generate and send a test payload to Zapier
            $submission = new Submission();
            $submission->setForm($form);

            Formie::$plugin->getSubmissions()->populateFakeSubmission($submission);

            // Ensure we're fetching the webhook from the form settings, or global integration settings
            $webhook = $form->settings->integrations[$this->handle]['webhook'] ?? $this->webhook;

            $payload = $this->generatePayloadValues($submission);
            $response = $this->getClient()->request('POST', $this->getWebhookUrl($webhook, $submission), $payload);

            $json = Json::decode((string)$response->getBody());

            $settings = [
                'response' => $response,
                'json' => $json,
            ];
        } catch (Throwable $e) {
            Integration::apiError($this, $e);
        }

        return new IntegrationFormSettings($settings);
    }

    public function sendPayload(Submission $submission): bool
    {
        try {
            $payload = $this->generatePayloadValues($submission);

            $response = $this->deliverPayload($submission, $this->getWebhookUrl($this->webhook, $submission), $payload);

            if ($response === false) {
                return true;
            }
        } catch (Throwable $e) {
            Integration::apiError($this, $e);

            return false;
        }

        return true;
    }

    public function getClient(): Client
    {
        if ($this->_client) {
            return $this->_client;
        }

        return $this->_client = Craft::createGuzzleClient();
    }
}