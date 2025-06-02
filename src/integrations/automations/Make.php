<?php
namespace verbb\formie\integrations\automations;

use verbb\formie\Formie;
use verbb\formie\base\Integration;
use verbb\formie\base\Automation;
use verbb\formie\elements\Submission;
use verbb\formie\models\IntegrationFormSettings;

use Craft;
use craft\helpers\Json;

use GuzzleHttp\Client;

use Throwable;

class Make extends Automation
{
    // Static Methods
    // =========================================================================

    public static function supportsConnection(): bool
    {
        return false;
    }

    public static function displayName(): string
    {
        return Craft::t('formie', 'Make');
    }
    

    // Properties
    // =========================================================================
    
    public ?string $webhook = null;
    

    // Public Methods
    // =========================================================================

    public function getDescription(): string
    {
        return Craft::t('formie', 'Send your form content to Make.');
    }

    public function fetchFormSettings(): IntegrationFormSettings
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
            $response = $this->deliverPayloadRequest($submission, $this->getEndpointUrl($webhook, $submission), $payload);

            $rawResponse = (string)$response->getBody();
            $json = Json::decodeIfJson($rawResponse);

            $settings = [
                'response' => $response,
                'json' => $json,
            ];
        } catch (Throwable $e) {
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

    public function sendPayload(Submission $submission): bool
    {
        $payload = [];
        $response = [];

        try {
            $payload = $this->generatePayloadValues($submission);

            $response = $this->deliverPayloadRequest($submission, $this->getEndpointUrl($this->webhook, $submission), $payload);

            if ($response === false) {
                return true;
            }
        } catch (Throwable $e) {
            // Save a different payload to logs
            Integration::error($this, Craft::t('formie', 'API error: “{message}” {file}:{line}. Payload: “{payload}”. Response: “{response}”', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'payload' => Json::encode($payload),
                'response' => Json::encode($response),
            ]));

            Integration::apiError($this, $e);

            return false;
        }

        return true;
    }

    public function allowedGqlSettings(): array
    {
        return [
            'webhook' => $this->webhook,
        ];
    }

    
    // Protected Methods
    // =========================================================================

    protected function defineRules(): array
    {
        $rules = parent::defineRules();

        $rules[] = [['webhook'], 'required', 'on' => [Integration::SCENARIO_FORM]];

        return $rules;
    }
}