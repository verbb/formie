<?php
namespace verbb\formie\integrations\crm;

use verbb\formie\attributes\Sensitive;
use verbb\formie\attributes\FormIntegrationSetting;
use verbb\formie\base\Crm;
use verbb\formie\base\FormInterface;
use verbb\formie\base\Integration;
use verbb\formie\elements\Submission;
use verbb\formie\helpers\ArrayHelper;
use verbb\formie\helpers\SchemaHelper;
use verbb\formie\helpers\StringHelper;
use verbb\formie\models\IntegrationField;
use verbb\formie\models\IntegrationConfig;
use verbb\formie\models\IntegrationResult;

use Craft;
use craft\helpers\App;
use craft\helpers\Json;

use Throwable;

use GuzzleHttp\Client;

class Outseta extends Crm
{
    // Static Methods
    // =========================================================================

    public static function displayName(): string
    {
        return 'Outseta';
    }


    // Properties
    // =========================================================================

    #[Sensitive]
    public ?string $apiKey = null;
    #[Sensitive]
    public ?string $secretKey = null;
    public ?string $apiDomain = null;
    #[FormIntegrationSetting]
    public bool $mapToPeople = false;
    #[FormIntegrationSetting]
    public ?array $peopleFieldMapping = null;


    // Public Methods
    // =========================================================================

    public function getDescription(): string
    {
        return Craft::t('formie', 'Manage your {name} customers by providing important information on their conversion on your site.', ['name' => static::displayName()]);
    }
    public function fetchConfig(): IntegrationConfig
    {
        $settings = [];

        try {
            if ($this->mapToPeople && $this->settingsContext->dataKey === 'people') {
                $settings['people'] = [
                    new IntegrationField([
                        'handle' => 'Email',
                        'name' => Craft::t('formie', 'Email'),
                        'required' => true,
                    ]),
                    new IntegrationField([
                        'handle' => 'FirstName',
                        'name' => Craft::t('formie', 'First Name'),
                    ]),
                    new IntegrationField([
                        'handle' => 'LastName',
                        'name' => Craft::t('formie', 'Last Name'),
                    ]),
                    new IntegrationField([
                        'handle' => 'MailingAddress.AddressLine1',
                        'name' => Craft::t('formie', 'Mailing Address: Address Line 1'),
                    ]),
                    new IntegrationField([
                        'handle' => 'MailingAddress.AddressLine2',
                        'name' => Craft::t('formie', 'Mailing Address: Address Line 2'),
                    ]),
                    new IntegrationField([
                        'handle' => 'MailingAddress.AddressLine3',
                        'name' => Craft::t('formie', 'Mailing Address: Address Line 3'),
                    ]),
                    new IntegrationField([
                        'handle' => 'MailingAddress.City',
                        'name' => Craft::t('formie', 'Mailing Address: City'),
                    ]),
                    new IntegrationField([
                        'handle' => 'MailingAddress.State',
                        'name' => Craft::t('formie', 'Mailing Address: State'),
                    ]),
                    new IntegrationField([
                        'handle' => 'MailingAddress.PostalCode',
                        'name' => Craft::t('formie', 'Mailing Address: Postal Code'),
                    ]),
                ];
            }
        } catch (Throwable $e) {
            Integration::apiError($this, $e);
        }

        return new IntegrationConfig($settings);
    }

    protected function executePayload(Submission $submission): IntegrationResult
    {
        $this->beginPayloadDelivery($submission);

        try {
            if ($this->mapToPeople) {
                $peopleValues = $this->getFieldMappingValues($submission, $this->peopleFieldMapping, 'people');

                $payload = ArrayHelper::expand($peopleValues);

                $response = $this->request('GET', 'people', [
                    'Email' => $payload['Email'],
                ]);

                $personId = $response['items'][0]['Uid'] ?? null;

                if ($personId) {
                    $response = $this->deliverPayload($submission, "people/$personId", $payload, 'PUT');
                } else {
                    $response = $this->deliverPayload($submission, 'people', $payload);
                }

                if ($response === false) {
                    return $this->resultForPayload(true);
                }

                $peopleId = $response['Uid'] ?? '';

                if (!$peopleId) {
                    Integration::error($this, Craft::t('formie', 'Missing return “peopleId” {response}. Sent payload {payload}', [
                        'response' => Json::encode($response),
                        'payload' => Json::encode($payload),
                    ]), true);

                    return $this->resultForPayload(false);
                }
            }
        } catch (Throwable $e) {
            Integration::apiError($this, $e);

            return $this->resultForPayload(false);
        }

        return $this->resultForPayload(true);
    }

    public function fetchConnection(): bool
    {
        try {
            $response = $this->request('GET', 'people');
        } catch (Throwable $e) {
            Integration::apiError($this, $e);

            return false;
        }

        return true;
    }


    // Protected Methods
    // =========================================================================

    protected function defineRules(): array
    {
        $rules = parent::defineRules();

        $rules[] = [['apiKey', 'secretKey', 'apiDomain'], 'required'];

        $people = $this->getConfigValue('people');

        // Validate the following when saving form settings
        $rules[] = [
            ['peopleFieldMapping'], 'validateFieldMapping', 'params' => $people, 'when' => function($model) {
                return $model->enabled && $model->mapToPeople;
            }, 'on' => [Integration::SCENARIO_FORM], 'skipOnEmpty' => false,
        ];

        return $rules;
    }

    protected function defineClient(): Client
    {
        $url = rtrim(App::parseEnv($this->apiDomain), '/');

        return Craft::createGuzzleClient([
            'base_uri' => "$url/api/v1/crm/",
            'headers' => ['Authorization' => 'Outseta ' . App::parseEnv($this->apiKey) . ':' . App::parseEnv($this->secretKey)],
        ]);
    }

    protected function defineFormSettingsSchema(FormInterface $form): array
    {
        $schema = parent::defineFormSettingsSchema($form);
        $schema[] = SchemaHelper::lightswitchField([
            'name' => 'mapToPeople',
            'label' => Craft::t('formie', 'Map to {name}', ['name' => 'People']),
            'instructions' => Craft::t('formie', 'Whether to map form data to {name} {label}.', ['name' => $this->displayName(), 'label' => 'People']),
        ]);
        $schema[] = $this->getIntegrationFieldMappingField([
            'name' => 'peopleFieldMapping',
            'if' => 'mapToPeople',
            'dataLabel' => 'People',
            'dataKey' => 'people',
        ]);

        return $schema;
    }
}
