<?php
namespace verbb\formie\integrations\crm;

use verbb\formie\Formie;
use verbb\formie\base\Crm;
use verbb\formie\base\Integration;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\errors\IntegrationException;
use verbb\formie\events\SendIntegrationPayloadEvent;
use verbb\formie\models\IntegrationCollection;
use verbb\formie\models\IntegrationField;
use verbb\formie\models\IntegrationFormSettings;

use Craft;
use craft\helpers\ArrayHelper;
use craft\helpers\Json;
use craft\web\View;

class Zoho extends Crm
{
    // Properties
    // =========================================================================

    public $clientId;
    public $clientSecret;
    public $apiServer;
    public $apiLocation;
    public $apiDomain;
    public $mapToContact = false;
    public $mapToDeal = false;
    public $mapToLead = false;
    public $mapToAccount = false;
    public $contactFieldMapping;
    public $dealFieldMapping;
    public $leadFieldMapping;
    public $accountFieldMapping;


    // OAuth Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
    public static function supportsOauthConnection(): bool
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function getAuthorizeUrl(): string
    {
        return 'https://accounts.zoho.com/oauth/v2/auth';
    }

    /**
     * @inheritDoc
     */
    public function getAccessTokenUrl(): string
    {
        // Populated after OAuth connection
        $url = $this->apiServer ?: 'https://accounts.zoho.com';
        $url = rtrim($url, '/');

        return "$url/oauth/v2/token";
    }

    /**
     * @inheritDoc
     */
    public function getClientId(): string
    {
        return Craft::parseEnv($this->clientId);
    }

    /**
     * @inheritDoc
     */
    public function getClientSecret(): string
    {
        return Craft::parseEnv($this->clientSecret);
    }

    /**
     * @inheritDoc
     */
    public function getOauthScope(): array
    {
        return [
            'ZohoCRM.modules.ALL',
            'ZohoCRM.settings.ALL',
        ];
    }

    /**
     * @inheritDoc
     */
    public function getOauthAuthorizationOptions(): array
    {
        return [
            'access_type' => 'offline',
        ];
    }

    /**
     * @inheritDoc
     */
    public function beforeFetchAccessToken(&$provider)
    {
        // Save these properties for later...
        $this->apiLocation = Craft::$app->getRequest()->getParam('location');
        $this->apiServer = Craft::$app->getRequest()->getParam('accounts-server');

        // We have to update the OAuth provider objecy with the new URLs provided by Zoho.
        // Annoyingly, can't just edit the provider, so create it again.
        $provider = $this->getOauthProvider();
    }

    /**
     * @inheritDoc
     */
    public function afterFetchAccessToken($token)
    {
        // Save these properties for later...
        $this->apiDomain = $token->getValues()['api_domain'] ?? '';

        if (!$this->apiDomain) {
            throw new \Exception('Zoho response missing `api_domain`.');
        }
    }


    // Public Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
    public static function displayName(): string
    {
        return Craft::t('formie', 'Zoho');
    }

    /**
     * @inheritDoc
     */
    public function getDescription(): string
    {
        return Craft::t('formie', 'Manage your Zoho customers by providing important information on their conversion on your site.');
    }

    /**
     * @inheritDoc
     */
    public function defineRules(): array
    {
        $rules = parent::defineRules();

        $rules[] = [['clientId', 'clientSecret'], 'required'];

        $contact = $this->getFormSettingValue('contact');
        $deal = $this->getFormSettingValue('deal');
        $lead = $this->getFormSettingValue('lead');
        $account = $this->getFormSettingValue('account');

        // Validate the following when saving form settings
        $rules[] = [['contactFieldMapping'], 'validateFieldMapping', 'params' => $contact, 'when' => function($model) {
            return $model->enabled && $model->mapToContact;
        }, 'on' => [Integration::SCENARIO_FORM]];

        $rules[] = [['dealFieldMapping'], 'validateFieldMapping', 'params' => $deal, 'when' => function($model) {
            return $model->enabled && $model->mapToDeal;
        }, 'on' => [Integration::SCENARIO_FORM]];

        $rules[] = [['leadFieldMapping'], 'validateFieldMapping', 'params' => $lead, 'when' => function($model) {
            return $model->enabled && $model->mapToLead;
        }, 'on' => [Integration::SCENARIO_FORM]];

        $rules[] = [['accountFieldMapping'], 'validateFieldMapping', 'params' => $account, 'when' => function($model) {
            return $model->enabled && $model->mapToAccount;
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
            $response = $this->request('GET', 'settings/fields', ['query' => ['module' => 'Contacts']]);
            $fields = $response['fields'] ?? [];
            $contactFields = $this->_getCustomFields($fields);

            $response = $this->request('GET', 'settings/fields', ['query' => ['module' => 'Deals']]);
            $fields = $response['fields'] ?? [];
            $dealFields = $this->_getCustomFields($fields);

            $response = $this->request('GET', 'settings/fields', ['query' => ['module' => 'Leads']]);
            $fields = $response['fields'] ?? [];
            $leadsFields = $this->_getCustomFields($fields);

            $response = $this->request('GET', 'settings/fields', ['query' => ['module' => 'Accounts']]);
            $fields = $response['fields'] ?? [];
            $accountFields = $this->_getCustomFields($fields);

            $settings = [
                'contact' => $contactFields,
                'deal' => $dealFields,
                'lead' => $leadsFields,
                'account' => $accountFields,
            ];
        } catch (\Throwable $e) {
            Integration::apiError($this, $e);
        }

        return new IntegrationFormSettings($settings);
    }

    /**
     * @inheritDoc
     */
    public function sendPayload(Submission $submission): bool
    {
        try {
            $contactValues = $this->getFieldMappingValues($submission, $this->contactFieldMapping, 'contact');
            $dealValues = $this->getFieldMappingValues($submission, $this->dealFieldMapping, 'deal');
            $leadValues = $this->getFieldMappingValues($submission, $this->leadFieldMapping, 'lead');
            $accountValues = $this->getFieldMappingValues($submission, $this->accountFieldMapping, 'account');

            $contactId = null;

            if ($this->mapToContact) {
                $contactPayload = [
                    'data' => [$contactValues],
                    'duplicate_check_fields' => ['Email'],
                ];

                $response = $this->deliverPayload($submission, 'Contacts/upsert', $contactPayload);

                if ($response === false) {
                    return true;
                }

                $contactId = $response['data'][0]['details']['id'] ?? '';

                if (!$contactId) {
                    Integration::error($this, Craft::t('formie', 'Missing return “contactId” {response}. Sent payload {payload}', [
                        'response' => Json::encode($response),
                        'payload' => Json::encode($contactPayload),
                    ]), true);

                    return false;
                }
            }

            if ($this->mapToAccount) {
                $accountPayload = [
                    'data' => [$accountValues],
                    'duplicate_check_fields' => ['Account_Name'],
                ];

                $response = $this->deliverPayload($submission, 'Accounts/upsert', $accountPayload);

                if ($response === false) {
                    return true;
                }

                $accountId = $response['data'][0]['details']['id'] ?? '';

                if (!$accountId) {
                    Integration::error($this, Craft::t('formie', 'Missing return “accountId” {response}. Sent payload {payload}', [
                        'response' => Json::encode($response),
                        'payload' => Json::encode($accountPayload),
                    ]), true);

                    return false;
                }
            }

            if ($this->mapToDeal) {
                $dealPayload = [
                    'data' => [$dealValues],
                ];

                $response = $this->deliverPayload($submission, 'Deals', $dealPayload);

                if ($response === false) {
                    return true;
                }

                $dealId = $response['data'][0]['details']['id'] ?? '';

                if (!$dealId) {
                    Integration::error($this, Craft::t('formie', 'Missing return “dealId” {response}. Sent payload {payload}', [
                        'response' => Json::encode($response),
                        'payload' => Json::encode($dealPayload),
                    ]), true);

                    return false;
                }

                // Connect Contact to Deal
                if ($contactId) {
                    $payload = [
                        'data' => [
                            ['Contact_Role' => '4201883000000006871'],
                        ],
                    ];

                    $response = $this->deliverPayload($submission, "/Contacts/{$contactId}/Deals/{$dealId}", $payload, 'PUT');

                    if ($response === false) {
                        return true;
                    }
                }
            }

            if ($this->mapToLead) {
                $leadPayload = [
                    'data' => [$leadValues],
                ];

                $response = $this->deliverPayload($submission, 'Leads', $leadPayload);

                if ($response === false) {
                    return true;
                }

                $leadId = $response['data'][0]['details']['id'] ?? '';

                if (!$leadId) {
                    Integration::error($this, Craft::t('formie', 'Missing return “leadId” {response}. Sent payload {payload}', [
                        'response' => Json::encode($response),
                        'payload' => Json::encode($leadPayload),
                    ]), true);

                    return false;
                }
            }
        } catch (\Throwable $e) {
            Integration::apiError($this, $e);

            return false;
        }

        return true;
    }

    /**
     * @inheritDoc
     */
    public function getClient()
    {
        if ($this->_client) {
            return $this->_client;
        }

        // Populated after OAuth connection
        $url = $this->apiDomain ?? 'https://www.zohoapis.com';
        $url = rtrim($url, '/');

        $token = $this->getToken();

        $this->_client = Craft::createGuzzleClient([
            'base_uri' => "$url/crm/v2/",
            'headers' => [
                'Authorization' => 'Bearer ' . $token->accessToken ?? '',
                'Content-Type' => 'application/json',
            ],
        ]);

        // Always provide an authenticated client - so check first.
        // We can't always rely on the EOL of the token.
        try {
            $response = $this->request('GET', 'Deals');
        } catch (\Throwable $e) {
            if ($e->getCode() === 401) {
                // Force-refresh the token
                Formie::$plugin->getTokens()->refreshToken($token, true);

                // Then try again, with the new access token
                $this->_client = Craft::createGuzzleClient([
                    'base_uri' => "$url/crm/v2/",
                    'headers' => [
                        'Authorization' => 'Bearer ' . $token->accessToken ?? '',
                        'Content-Type' => 'application/json',
                    ],
                ]);
            }
        }

        return $this->_client;
    }


    // Private Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
    private function _convertFieldType($fieldType)
    {
        $fieldTypes = [
            'jsonobject' => IntegrationField::TYPE_ARRAY,
            'jsonarray' => IntegrationField::TYPE_ARRAY,
            'date' => IntegrationField::TYPE_DATE,
            'datetime' => IntegrationField::TYPE_DATETIME,
            'timestamp' => IntegrationField::TYPE_DATETIME,
            'boolean' => IntegrationField::TYPE_BOOLEAN,
            'integer' => IntegrationField::TYPE_NUMBER,
            'number' => IntegrationField::TYPE_FLOAT,
            'bigint' => IntegrationField::TYPE_NUMBER,
            'currency' => IntegrationField::TYPE_FLOAT,
            'double' => IntegrationField::TYPE_FLOAT,
            'decimal' => IntegrationField::TYPE_FLOAT,
        ];

        return $fieldTypes[$fieldType] ?? IntegrationField::TYPE_STRING;
    }

    /**
     * @inheritDoc
     */
    private function _getCustomFields($fields, $excludeNames = [])
    {
        $customFields = [];

        foreach ($fields as $key => $field) {
            if ($field['read_only'] || $field['field_read_only']) {
                continue;
            }

            $fieldType = $field['json_type'] ?? $field['data_type'] ?? '';

            // Exclude any names
            if (in_array($field['api_name'], $excludeNames)) {
                 continue;
            }

            $options = [];
            $pickListValues = $field['pick_list_values'] ?? [];

            foreach ($pickListValues as $key => $pickListValue) {
                $options[] = [
                    'label' => $pickListValue['display_value'],
                    'value' => $pickListValue['id'] ?? $pickListValue['actual_value'],
                ];
            }

            if ($options) {
                $options = [
                    'label' => $field['field_label'],
                    'options' => $options,
                ];
            }

            $customFields[] = new IntegrationField([
                'handle' => $field['api_name'],
                'name' => $field['field_label'],
                'type' => $this->_convertFieldType($fieldType),
                'required' => $field['system_mandatory'] ?? false,
                'options' => $options,
            ]);
        }

        return $customFields;
    }
}