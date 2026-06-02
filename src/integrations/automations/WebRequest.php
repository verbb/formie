<?php
namespace verbb\formie\integrations\automations;

use verbb\formie\Formie;
use verbb\formie\base\FormInterface;
use verbb\formie\base\Integration;
use verbb\formie\base\Automation;
use verbb\formie\elements\Submission;
use verbb\formie\helpers\SchemaHelper;
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

    public function getDescription(): string
    {
        return Craft::t('formie', 'Send your form content to any URL you provide.');
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

            $url = $form->settings->integrations[$this->handle]['url'] ?? $this->url;

            $response = $this->deliverPayload($submission, $this->getEndpointUrl($url, $submission), $payload, $this->method, $this->requestType);

            $rawResponse = (string)$response->getBody();
            $json = Json::decodeIfJson($rawResponse);

            $settings = [
                'response' => $response,
                'json' => $json,
            ];
        } catch (Throwable $e) {
            // Save a different payload to logs
            Integration::error($this, Craft::t('formie', 'API error: “{message}” {file}:{line}. Payload: “{payload}”. Response: “{response}”', [
                'message' => Integration::getExceptionLogMessage($e),
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

            $response = $this->deliverPayload($submission, $this->getEndpointUrl($this->url, $submission), $payload, $this->method, $this->requestType);

            if ($response === false) {
                return true;
            }
        } catch (Throwable $e) {
            // Save a different payload to logs
            Integration::error($this, Craft::t('formie', 'API error: “{message}” {file}:{line}. Payload: “{payload}”. Response: “{response}”', [
                'message' => Integration::getExceptionLogMessage($e),
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
            'url' => $this->url,
        ];
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

    protected function defineFormSettingsSchema(FormInterface $form): array
    {
        $schema = parent::defineFormSettingsSchema($form);
        $schema[] = SchemaHelper::textField([
            'label' => Craft::t('formie', 'URL'),
            'instructions' => Craft::t('formie', 'Enter the URL that will be triggered when a submission is made.'),
            'name' => 'url',
            'required' => true,
        ]);
        $schema[] = SchemaHelper::selectField([
            'label' => Craft::t('formie', 'HTTP Method'),
            'instructions' => Craft::t('formie', 'Select the HTTP Method used to send data.'),
            'name' => 'method',
            'required' => true,
            'defaultValue' => $this->method ?: 'POST',
            'options' => [
                ['label' => Craft::t('formie', 'GET'), 'value' => 'GET'],
                ['label' => Craft::t('formie', 'POST'), 'value' => 'POST'],
                ['label' => Craft::t('formie', 'PUT'), 'value' => 'PUT'],
                ['label' => Craft::t('formie', 'PATCH'), 'value' => 'PATCH'],
                ['label' => Craft::t('formie', 'DELETE'), 'value' => 'DELETE'],
            ],
        ]);
        $schema[] = SchemaHelper::selectField([
            'label' => Craft::t('formie', 'Request Type'),
            'instructions' => Craft::t('formie', 'Select the Request Type used to send data.'),
            'name' => 'requestType',
            'required' => true,
            'defaultValue' => $this->requestType ?: 'json',
            'options' => [
                ['label' => Craft::t('formie', 'JSON Body'), 'value' => 'json'],
                ['label' => Craft::t('formie', 'Raw Body'), 'value' => 'body'],
                ['label' => Craft::t('formie', 'Query String'), 'value' => 'query'],
                ['label' => Craft::t('formie', 'Form Params'), 'value' => 'form_params'],
                ['label' => Craft::t('formie', 'Multipart'), 'value' => 'multipart'],
            ],
        ]);
        $schema[] = SchemaHelper::tableField([
            'label' => Craft::t('formie', 'Headers'),
            'instructions' => Craft::t('formie', 'Provide any parameters for the request header.'),
            'name' => 'headers',
            'columns' => [
                ['name' => 'key', 'label' => Craft::t('formie', 'Key'), 'type' => 'text'],
                ['name' => 'value', 'label' => Craft::t('formie', 'Value'), 'type' => 'text'],
            ],
        ]);
        $schema[] = SchemaHelper::tableField([
            'label' => Craft::t('formie', 'HTTP Authentication'),
            'instructions' => Craft::t('formie', 'If using Basic HTTP Authentication, provide the Username and Password for the provider.'),
            'name' => 'httpAuth',
            'columns' => [
                ['name' => 'username', 'label' => Craft::t('formie', 'Username'), 'type' => 'text'],
                ['name' => 'password', 'label' => Craft::t('formie', 'Password'), 'type' => 'text'],
            ],
        ]);

        return $schema;
    }

}