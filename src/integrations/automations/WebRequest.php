<?php
namespace verbb\formie\integrations\automations;

use verbb\formie\Formie;
use verbb\formie\base\Integration;
use verbb\formie\base\Automation;
use verbb\formie\elements\Submission;
use verbb\formie\helpers\ArrayHelper;
use verbb\formie\models\IntegrationFormSettings;

use Craft;
use craft\helpers\App;
use craft\helpers\Json;

use GuzzleHttp\Client;

use Throwable;

class WebRequest extends Automation
{
    // Static Methods
    // =========================================================================

    public static function supportsConnection(): bool
    {
        return false;
    }

    public static function displayName(): string
    {
        return Craft::t('formie', 'Web Request');
    }
    

    // Properties
    // =========================================================================

    public ?string $url = null;
    public string $method = 'POST';
    public string $requestType = 'json';
    public array $headers = [];
    public array $httpAuth = [];


    // Public Methods
    // =========================================================================

    public function __construct($config = [])
    {
        // Config normalization
        if (array_key_exists('webhook', $config)) {
            $config['url'] = ArrayHelper::remove($config, 'webhook');
        }

        parent::__construct($config);
    }

    public function getDescription(): string
    {
        return Craft::t('formie', 'Send your form content to any URL you provide.');
    }

    public function getFormSettingsHtml($form): string
    {
        $view = Craft::$app->getView();

        $view->startJsBuffer();
        $bodyHtml = parent::getFormSettingsHtml($form);
        $footHtml = $view->clearJsBuffer(false);

        // Hack around using Craft jQuery in Vue app.
        $footHtml = str_replace(['\u002D', '\u005B', '\u005D'], ['-', '[', ']'], $footHtml);
        $footHtml = '$(document).one("formie:integration-tab-' . $this->handle . '", function() {' . $footHtml . '});';

        $view->js[] = [$footHtml];

        return $bodyHtml;
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
            $payload = $this->generatePayloadValues($submission);

            $response = $this->deliverPayloadRequest($submission, $this->getEndpointUrl($this->url, $submission), $payload, $this->method, $this->requestType);

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

            $response = $this->deliverPayloadRequest($submission, $this->getEndpointUrl($this->url, $submission), $payload, $this->method, $this->requestType);

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
            // Alias for backward compatibility. Remove at the next breakpoint
            'webhook' => $this->url,
            'url' => $this->url,
        ];
    }

    public function beforeSaveForm(array $settings): void
    {
        unset($settings['webhook']);
    }


    // Protected Methods
    // =========================================================================

    protected function defineRules(): array
    {
        $rules = parent::defineRules();

        $rules[] = [['url'], 'required', 'on' => [Integration::SCENARIO_FORM]];

        return $rules;
    }

    protected function defineClient(): Client
    {
        $config = [];

        if ($this->headers) {
            foreach ($this->headers as $header) {
                $config['headers'][App::parseEnv($header['key'])] = App::parseEnv($header['value']);
            }
        }

        if ($this->httpAuth) {
            $username = $this->httpAuth['username'] ?? '';
            $password = $this->httpAuth['password'] ?? '';

            if ($username || $password) {
                $config['auth'] = [App::parseEnv($username), App::parseEnv($password)];
            }
        }

        return Craft::createGuzzleClient($config);
    }    
}