<?php
namespace verbb\formie\integrations\webhooks;

use verbb\formie\Formie;
use verbb\formie\base\Integration;
use verbb\formie\base\Webhook as BaseWebhook;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\models\IntegrationCollection;
use verbb\formie\models\IntegrationField;
use verbb\formie\models\IntegrationFormSettings;

use Craft;
use craft\helpers\ArrayHelper;
use craft\helpers\Json;
use craft\web\View;

class Webhook extends BaseWebhook
{
    // Properties
    // =========================================================================

    public $webhook;


    // Public Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
    public static function supportsConnection(): bool
    {
        return false;
    }

    /**
     * @inheritDoc
     */
    public static function displayName(): string
    {
        return Craft::t('formie', 'Webhook');
    }

    /**
     * @inheritDoc
     */
    public function getDescription(): string
    {
        return Craft::t('formie', 'Send your form content to any URL you provide.');
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

    /**
     * @inheritDoc
     */
    public function fetchFormSettings()
    {
        $settings = [];
        $payload = [];

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

            $rawResponse = (string)$response->getBody();
            $json = Json::decode($rawResponse);

            $settings = [
                'response' => $response,
                'json' => $json,
            ];
        } catch (\Throwable $e) {
            // Save a different payload to logs
            Integration::error($this, Craft::t('formie', 'API error: “{message}” {file}:{line}. Payload: “{payload}”. Response: “{response}”', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'payload' => Json::encode($payload),
                'response' => $rawResponse ?? '',
            ]));

            Integration::apiError($this, $e);
        }

        return new IntegrationFormSettings($settings);
    }

    /**
     * @inheritDoc
     */
    public function sendPayload(Submission $submission): bool
    {
        $payload = [];

        try {
            $payload = $this->generatePayloadValues($submission);

            $response = $this->getClient()->request('POST', $this->getWebhookUrl($this->webhook, $submission), $payload);
        } catch (\Throwable $e) {
            // Save a different payload to logs
            Integration::error($this, Craft::t('formie', 'API error: “{message}” {file}:{line}. Payload: “{payload}”. Response: “{response}”', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'payload' => Json::encode($payload),
                'response' => $rawResponse ?? '',
            ]));

            Integration::apiError($this, $e);

            return false;
        }

        return true;
    }


    // Private Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
    protected function getClient()
    {
        if ($this->_client) {
            return $this->_client;
        }

        $options = [];

        // Disable SSL verification for local dev (devMode enabled) to save some heartache.
        if (Craft::$app->getConfig()->getGeneral()->devMode) {
            $options['verify'] = false;
        }

        return $this->_client = Craft::createGuzzleClient($options);
    }
}