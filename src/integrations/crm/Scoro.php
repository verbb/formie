<?php
namespace verbb\formie\integrations\crm;

use verbb\formie\base\Crm;
use verbb\formie\base\Integration;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\errors\IntegrationException;
use verbb\formie\events\SendIntegrationPayloadEvent;
use verbb\formie\models\IntegrationField;
use verbb\formie\models\EmailMarketingList;

use Craft;
use craft\helpers\ArrayHelper;
use craft\helpers\Json;
use craft\helpers\StringHelper;
use craft\web\View;

class Scoro extends Crm
{
    // Properties
    // =========================================================================

    public $apiKey;
    public $apiDomain;
    public $mapToContact = false;
    public $contactFieldMapping;


    // Public Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
    public static function displayName(): string
    {
        return Craft::t('formie', 'Scoro');
    }

    /**
     * @inheritDoc
     */
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

        $contact = $this->getFormSettings()['contact'] ?? [];

        // Validate the following when saving form settings
        $rules[] = [['contactFieldMapping'], 'validateFieldMapping', 'params' => $contact, 'when' => function($model) {
            return $model->enabled && $model->mapToContact;
        }, 'on' => [Integration::SCENARIO_FORM]];

        return $rules;
    }

    /**
     * @inheritDoc
     */
    public function fetchFormSettings()
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
        } catch (\Throwable $e) {
            Integration::error($this, Craft::t('formie', 'API error: “{message}” {file}:{line}', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]), true);
        }

        return $settings;
    }

    /**
     * @inheritDoc
     */
    public function sendPayload(Submission $submission): bool
    {
        try {
            $contactValues = $this->getFieldMappingValues($submission, $this->contactFieldMapping);

            // Special processing on this due to nested content in payload
            $contactPayload = [
                'request' => $this->_prepContactPayload($contactValues),
                'apiKey' => $this->apiKey,
                'company_account_id' => $this->_getCompanyAccountId(),
            ];

            $response = $this->deliverPayload($submission, 'contacts/modify', $contactPayload);

            if ($response === false) {
                return false;
            }

            $contactId = $response['data']['contact_id'] ?? '';

            if (!$contactId) {
                Integration::error($this, Craft::t('formie', 'Missing return “contactId” {response}', [
                    'response' => Json::encode($response),
                ]), true);

                return false;
            }
        } catch (\Throwable $e) {
            Integration::error($this, Craft::t('formie', 'API error: “{message}” {file}:{line}', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]), true);

            return false;
        }

        return true;
    }

    /**
     * @inheritDoc
     */
    public function fetchConnection(): bool
    {
        try {
            $response = $this->request('GET', 'contacts');
        } catch (\Throwable $e) {
            Integration::error($this, Craft::t('formie', 'API error: “{message}” {file}:{line}', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]), true);

            return false;
        }

        return true;
    }


    // Protected Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
    protected function getClient()
    {
        if ($this->_client) {
            return $this->_client;
        }

        $url = rtrim($this->apiDomain, '/');

        return $this->_client = Craft::createGuzzleClient([
            'base_uri' => "$url/api/v2/",
            'query' => ['apiKey' => $this->apiKey],
        ]);
    }


    // Private Methods
    // =========================================================================
    
    /**
     * @inheritDoc
     */
    private function _getCompanyAccountId()
    {
        if ($this->apiDomain) {
            $parsedUrl = parse_url($this->apiDomain);
            $host = explode('.', $parsedUrl['host']);

            return $host[0] ?? '';
        }

        return '';
    }

    /**
     * @inheritDoc
     */
    private function _getCustomFields($fields, $excludeNames = [])
    {
        $customFields = [];

        foreach ($fields as $key => $field) {
            $customFields[] = new IntegrationField([
                'handle' => 'custom:' . $field['id'],
                'name' => $field['name'],
                'type' => $field['type'],
            ]);
        }

        return $customFields;
    }

    /**
     * @inheritDoc
     */
    private function _prepContactPayload($fields)
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