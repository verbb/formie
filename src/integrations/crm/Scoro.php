<?php
namespace verbb\formie\integrations\crm;

use verbb\formie\base\Crm;
use verbb\formie\base\Integration;
use verbb\formie\elements\Submission;
use verbb\formie\models\IntegrationField;
use verbb\formie\models\IntegrationFormSettings;

use Craft;
use craft\helpers\App;
use craft\helpers\ArrayHelper;
use craft\helpers\Json;
use craft\helpers\StringHelper;

use GuzzleHttp\Client;

use Throwable;

class Scoro extends Crm
{
    // Static Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
    public static function displayName(): string
    {
        return Craft::t('formie', 'Scoro');
    }
    

    // Properties
    // =========================================================================
    
    public ?string $apiKey = null;
    public ?string $apiDomain = null;
    public bool $mapToContact = false;
    public ?array $contactFieldMapping = null;


    // Public Methods
    // =========================================================================

    public function getDescription(): string
    {
        return Craft::t('formie', 'Manage your Scoro customers by providing important information on their conversion on your site.');
    }

    /**
     * @inheritDoc
     */
    public function defineRules(): array
    {
        $rules = parent::defineRules();

        $rules[] = [['apiKey', 'apiDomain'], 'required'];

        $contact = $this->getFormSettingValue('contact');

        // Validate the following when saving form settings
        $rules[] = [
            ['contactFieldMapping'], 'validateFieldMapping', 'params' => $contact, 'when' => function($model) {
                return $model->enabled && $model->mapToContact;
            }, 'on' => [Integration::SCENARIO_FORM],
        ];

        return $rules;
    }

    public function fetchFormSettings(): IntegrationFormSettings
    {
        $settings = [];

        try {
            $response = $this->request('GET', 'customFields/list');
            $fields = $response['data'] ?? [];

            $contactFields = array_merge([
                new IntegrationField([
                    'handle' => 'name',
                    'name' => Craft::t('formie', 'Name'),
                ]),
                new IntegrationField([
                    'handle' => 'lastname',
                    'name' => Craft::t('formie', 'Last Name'),
                ]),
                new IntegrationField([
                    'handle' => 'contact_type',
                    'name' => Craft::t('formie', 'Contact Type'),
                    'options' => [
                        'label' => Craft::t('formie', 'Contact Type'),
                        'options' => [
                            [
                                'label' => Craft::t('formie', 'Person'),
                                'value' => 'person',
                            ],
                            [
                                'label' => Craft::t('formie', 'Company'),
                                'value' => 'company',
                            ],
                        ],
                    ],
                ]),
                new IntegrationField([
                    'handle' => 'id_code',
                    'name' => Craft::t('formie', 'ID Code'),
                ]),
                new IntegrationField([
                    'handle' => 'birthday',
                    'name' => Craft::t('formie', 'Birthday'),
                ]),
                new IntegrationField([
                    'handle' => 'position',
                    'name' => Craft::t('formie', 'Position'),
                ]),
                new IntegrationField([
                    'handle' => 'comments',
                    'name' => Craft::t('formie', 'Comments'),
                ]),
                new IntegrationField([
                    'handle' => 'sex',
                    'name' => Craft::t('formie', 'Sex'),
                ]),
                new IntegrationField([
                    'handle' => 'vatno',
                    'name' => Craft::t('formie', 'VAT Number'),
                ]),
                new IntegrationField([
                    'handle' => 'timezone',
                    'name' => Craft::t('formie', 'Timezone'),
                ]),
                new IntegrationField([
                    'handle' => 'manager_id',
                    'name' => Craft::t('formie', 'Manager ID'),
                ]),
                new IntegrationField([
                    'handle' => 'manager_email',
                    'name' => Craft::t('formie', 'Manager Email'),
                ]),
                new IntegrationField([
                    'handle' => 'is_supplier',
                    'name' => Craft::t('formie', 'Is Supplier'),
                ]),
                new IntegrationField([
                    'handle' => 'is_client',
                    'name' => Craft::t('formie', 'Is Client'),
                ]),
                new IntegrationField([
                    'handle' => 'client_profile_id',
                    'name' => Craft::t('formie', 'Client Profile ID'),
                ]),
                new IntegrationField([
                    'handle' => 'reference_no',
                    'name' => Craft::t('formie', 'Reference Number'),
                ]),
            ], $this->_getCustomFields($fields));

            $settings = [
                'contact' => $contactFields,
            ];
        } catch (Throwable $e) {
            Integration::apiError($this, $e);
        }

        return new IntegrationFormSettings($settings);
    }

    public function sendPayload(Submission $submission): bool
    {
        try {
            $contactValues = $this->getFieldMappingValues($submission, $this->contactFieldMapping, 'contact');

            // Special processing on this due to nested content in payload
            $contactPayload = [
                'request' => $this->_prepContactPayload($contactValues),
                'apiKey' => App::parseEnv($this->apiKey),
                'company_account_id' => $this->_getCompanyAccountId(),
            ];

            $response = $this->deliverPayload($submission, 'contacts/modify', $contactPayload);

            if ($response === false) {
                return true;
            }

            $contactId = $response['data']['contact_id'] ?? '';

            if (!$contactId) {
                Integration::error($this, Craft::t('formie', 'Missing return “contactId” {response}. Sent payload {payload}', [
                    'response' => Json::encode($response),
                    'payload' => Json::encode($contactPayload),
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
            $response = $this->request('GET', 'contacts');
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

        $url = rtrim(App::parseEnv($this->apiDomain), '/');

        return $this->_client = Craft::createGuzzleClient([
            'base_uri' => "$url/api/v2/",
            'query' => ['apiKey' => App::parseEnv($this->apiKey)],
        ]);
    }


    // Private Methods
    // =========================================================================

    private function _convertFieldType($fieldType)
    {
        $fieldTypes = [
            'date' => IntegrationField::TYPE_DATE,
            'datetime' => IntegrationField::TYPE_DATETIME,
            'number' => IntegrationField::TYPE_NUMBER,
        ];

        return $fieldTypes[$fieldType] ?? IntegrationField::TYPE_STRING;
    }

    private function _getCompanyAccountId(): string
    {
        if (App::parseEnv($this->apiDomain)) {
            $parsedUrl = parse_url(App::parseEnv($this->apiDomain));
            $host = explode('.', $parsedUrl['host']);

            return $host[0] ?? '';
        }

        return '';
    }

    private function _getCustomFields($fields, $excludeNames = []): array
    {
        $customFields = [];

        foreach ($fields as $key => $field) {
            $customFields[] = new IntegrationField([
                'handle' => 'custom:' . $field['id'],
                'name' => $field['name'],
                'type' => $this->_convertFieldType($field['type']),
            ]);
        }

        return $customFields;
    }

    private function _prepContactPayload($fields): array
    {
        $payload = $fields;

        foreach ($payload as $key => $value) {
            if (StringHelper::startsWith($key, 'custom:')) {
                $field = ArrayHelper::remove($payload, $key);

                $payload['custom_fields'][str_replace('custom:', '', $key)] = $value;
            }
        }

        return $payload;
    }
}