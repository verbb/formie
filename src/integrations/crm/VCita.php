<?php
namespace verbb\formie\integrations\crm;

use verbb\formie\base\Crm;
use verbb\formie\base\Integration;
use verbb\formie\elements\Submission;
use verbb\formie\models\IntegrationField;
use verbb\formie\models\IntegrationFormSettings;

use Craft;
use craft\helpers\App;
use craft\helpers\Json;

use GuzzleHttp\Client;

use Throwable;

class VCita extends Crm
{
    // Static Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
    public static function displayName(): string
    {
        return Craft::t('formie', 'vCita');
    }
    

    // Properties
    // =========================================================================
    
    public ?string $apiKey = null;
    public bool $mapToClient = false;
    public ?array $clientFieldMapping = null;


    // Public Methods
    // =========================================================================

    public function getDescription(): string
    {
        return Craft::t('formie', 'Manage your vCita customers by providing important information on their conversion on your site.');
    }

    /**
     * @inheritDoc
     */
    public function defineRules(): array
    {
        $rules = parent::defineRules();

        $rules[] = [['apiKey'], 'required'];

        $client = $this->getFormSettingValue('client');

        // Validate the following when saving form settings
        $rules[] = [
            ['clientFieldMapping'], 'validateFieldMapping', 'params' => $client, 'when' => function($model) {
                return $model->enabled && $model->mapToClient;
            }, 'on' => [Integration::SCENARIO_FORM],
        ];

        return $rules;
    }

    public function fetchFormSettings(): IntegrationFormSettings
    {
        $settings = [];

        try {
            $response = $this->request('GET', 'fields');
            $fields = $response['data'] ?? [];

            $clientFields = array_merge([
                new IntegrationField([
                    'handle' => 'email',
                    'name' => Craft::t('formie', 'Email'),
                    'required' => true,
                ]),
                new IntegrationField([
                    'handle' => 'first_name',
                    'name' => Craft::t('formie', 'First Name'),
                    'required' => true,
                ]),
                new IntegrationField([
                    'handle' => 'last_name',
                    'name' => Craft::t('formie', 'Last Name'),
                ]),
                new IntegrationField([
                    'handle' => 'phone',
                    'name' => Craft::t('formie', 'Phone Number'),
                ]),
                new IntegrationField([
                    'handle' => 'address',
                    'name' => Craft::t('formie', 'Address'),
                ]),
                new IntegrationField([
                    'handle' => 'source_campaign',
                    'name' => Craft::t('formie', 'Source Campaign'),
                ]),
                new IntegrationField([
                    'handle' => 'source_channel',
                    'name' => Craft::t('formie', 'Source Channel'),
                ]),
                new IntegrationField([
                    'handle' => 'source_name',
                    'name' => Craft::t('formie', 'Source Name'),
                ]),
                new IntegrationField([
                    'handle' => 'source_url',
                    'name' => Craft::t('formie', 'Source URL'),
                ]),
                new IntegrationField([
                    'handle' => 'staff_id',
                    'name' => Craft::t('formie', 'Staff ID'),
                ]),
                new IntegrationField([
                    'handle' => 'status',
                    'name' => Craft::t('formie', 'Status'),
                ]),
                new IntegrationField([
                    'handle' => 'tags',
                    'name' => Craft::t('formie', 'Tags'),
                ]),
            ], $this->_getCustomFields($fields));

            $settings = [
                'client' => $clientFields,
            ];
        } catch (Throwable $e) {
            Integration::apiError($this, $e);
        }

        return new IntegrationFormSettings($settings);
    }

    public function sendPayload(Submission $submission): bool
    {
        try {
            $clientValues = $this->getFieldMappingValues($submission, $this->clientFieldMapping, 'client');

            // Special processing on this due to nested content in payload
            $clientPayload = $clientValues;

            // Can't handle update and create, so check first
            $response = $this->request('GET', 'clients', [
                'query' => [
                    'search_by' => 'email',
                    'search_term' => $clientPayload['email'] ?? '',
                ],
            ]);
            $existingClient = $response['data']['clients'][0]['id'] ?? '';

            if ($existingClient) {
                $response = $this->deliverPayload($submission, "clients/{$existingClient}", $clientPayload, 'PUT');
            } else {
                $response = $this->deliverPayload($submission, 'clients', $clientPayload);
            }

            if ($response === false) {
                return true;
            }

            $clientId = $response['data']['client']['id'] ?? '';

            if (!$clientId) {
                Integration::error($this, Craft::t('formie', 'Missing return “clientId” {response}. Sent payload {payload}', [
                    'response' => Json::encode($response),
                    'payload' => Json::encode($clientPayload),
                ]), true);

                return false;
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
            $response = $this->request('GET', 'clients');
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

        return $this->_client = Craft::createGuzzleClient([
            'base_uri' => 'https://api.vcita.biz/platform/v1/',
            'headers' => [
                'Authorization' => 'Bearer ' . App::parseEnv($this->apiKey),
                'Content-Type' => 'application/json',
            ],
        ]);
    }


    // Private Methods
    // =========================================================================

    private function _convertFieldType($fieldType)
    {
        $fieldTypes = [
            'datepicker' => IntegrationField::TYPE_DATE,
        ];

        return $fieldTypes[$fieldType] ?? IntegrationField::TYPE_STRING;
    }

    private function _getCustomFields($fields, $excludeNames = []): array
    {
        $customFields = [];

        // Lots of fields aren't supported, but have to use their name. No clear definition on custom and core field.
        $unsupportedFields = [
            // types
            'email',
            'firstname',
            'lastname',
            'phone',
            'address',

            // labels
            'Job title',
            'Company name',
            'Your website URL',
            'Birthday',
        ];

        foreach ($fields as $key => $field) {
            // Only allow supported types
            if (in_array($field['type'], $unsupportedFields) || in_array($field['label'], $unsupportedFields)) {
                continue;
            }

            $customFields[] = new IntegrationField([
                'handle' => $field['label'],
                'name' => $field['label'],
                'type' => $this->_convertFieldType($field['type']),
                'required' => $field['required'],
            ]);
        }

        return $customFields;
    }
}