<?php
namespace verbb\formie\integrations\crm;

use verbb\formie\base\Crm;
use verbb\formie\base\Integration;
use verbb\formie\elements\Submission;
use verbb\formie\helpers\ArrayHelper;
use verbb\formie\helpers\StringHelper;
use verbb\formie\models\IntegrationField;
use verbb\formie\models\IntegrationFormSettings;

use Craft;
use craft\helpers\App;
use craft\helpers\Json;

use GuzzleHttp\Client;

use Throwable;

class Outseta extends Crm
{
    // Static Methods
    // =========================================================================

    public static function displayName(): string
    {
        return Craft::t('formie', 'Outseta');
    }
    

    // Properties
    // =========================================================================
    
    public ?string $apiKey = null;
    public ?string $secretKey = null;
    public ?string $apiDomain = null;
    public bool $mapToPeople = false;
    public ?array $peopleFieldMapping = null;


    // Public Methods
    // =========================================================================

    public function getDescription(): string
    {
        return Craft::t('formie', 'Manage your {name} customers by providing important information on their conversion on your site.', ['name' => static::displayName()]);
    }

    public function fetchFormSettings(): IntegrationFormSettings
    {
        $settings = [];

        try {
            if ($this->mapToPeople) {
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

        return new IntegrationFormSettings($settings);
    }

    public function sendPayload(Submission $submission): bool
    {
        try {
            if ($this->mapToPeople) {
                $peopleValues = $this->getFieldMappingValues($submission, $this->peopleFieldMapping, 'people');

                $payload = ArrayHelper::expand($peopleValues);

                $response = $this->request('GET', 'people', [
                    'Email' => $payload['Email'],
                ]);

                $personId = $response['items'][0]['Uid'] ?? null;

                if ($personId) {
                    $response = $this->deliverPayload($submission, "people/$personId" , $payload, 'PUT');
                } else {
                    $response = $this->deliverPayload($submission, 'people', $payload);
                }

                if ($response === false) {
                    return true;
                }

                $peopleId = $response['Uid'] ?? '';

                if (!$peopleId) {
                    Integration::error($this, Craft::t('formie', 'Missing return “peopleId” {response}. Sent payload {payload}', [
                        'response' => Json::encode($response),
                        'payload' => Json::encode($payload),
                    ]), true);

                    return false;
                }
            }
        } catch (Throwable $e) {
            Integration::apiError($this, $e);

            return false;
        }

        return true;
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

        $people = $this->getFormSettingValue('people');

        // Validate the following when saving form settings
        $rules[] = [
            ['peopleFieldMapping'], 'validateFieldMapping', 'params' => $people, 'when' => function($model) {
                return $model->enabled && $model->mapToPeople;
            }, 'on' => [Integration::SCENARIO_FORM],
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
}