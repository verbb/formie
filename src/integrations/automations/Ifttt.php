<?php
namespace verbb\formie\integrations\automations;

use verbb\formie\Formie;
use verbb\formie\base\Integration;
use verbb\formie\base\Automation;
use verbb\formie\elements\Submission;
use verbb\formie\models\IntegrationFormSettings;

use Craft;
use craft\helpers\App;
use craft\helpers\Json;

use GuzzleHttp\Client;

use Throwable;

class Ifttt extends Automation
{
    // Static Methods
    // =========================================================================

    public static function supportsConnection(): bool
    {
        return false;
    }

    public static function displayName(): string
    {
        return Craft::t('formie', 'IFTTT');
    }
    

    // Properties
    // =========================================================================
    
    public ?string $webhookKey = null;
    public ?string $eventName = null;
    

    // Public Methods
    // =========================================================================

    public function getDescription(): string
    {
        return Craft::t('formie', 'Send your form content to IFTTT.');
    }

    public function fetchFormSettings(): IntegrationFormSettings
    {
        $settings = [];

        try {
            $formId = Craft::$app->getRequest()->getParam('formId');
            $form = Formie::$plugin->getForms()->getFormById($formId);

            // Generate and send a test payload
            $submission = new Submission();
            $submission->setForm($form);

            Formie::$plugin->getSubmissions()->populateFakeSubmission($submission);

            $this->eventName = $form->settings->integrations[$this->handle]['eventName'] ?? $this->eventName;

            $payload = $this->generatePayloadValues($submission);

            $response = $this->deliverPayloadRequest($submission, $this->getUrl(), $payload);

            $rawResponse = (string)$response->getBody();
            $json = Json::decodeIfJson($rawResponse);

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

            $response = $this->deliverPayloadRequest($submission, $this->getUrl(), $payload);

            if ($response === false) {
                return true;
            }
        } catch (Throwable $e) {
            Integration::apiError($this, $e);

            return false;
        }

        return true;
    }

    public function getUrl(): string
    {
        $event = App::parseEnv($this->eventName);
        $key = App::parseEnv($this->webhookKey);

        return "https://maker.ifttt.com/trigger/$event/with/key/$key";
    }

    
    // Protected Methods
    // =========================================================================

    protected function defineRules(): array
    {
        $rules = parent::defineRules();

        $rules[] = [['webhookKey'], 'required'];
        $rules[] = [['eventName'], 'required', 'on' => [Integration::SCENARIO_FORM]];

        return $rules;
    }
}
