<?php
namespace verbb\formie\integrations\crm;

use verbb\formie\Formie;
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
use Exception;

class Zoho extends Crm
{
    // Static Methods
    // =========================================================================

    public static function supportsOauthConnection(): bool
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public static function displayName(): string
    {
        return Craft::t('formie', 'Zoho');
    }
    

    // Properties
    // =========================================================================
    
    public ?string $clientId = null;
    public ?string $clientSecret = null;
    public ?string $apiServer = null;
    public ?string $apiLocation = null;
    public ?string $apiDomain = null;
    public bool|string $useDeveloper = false;
    public bool $mapToContact = false;
    public bool $mapToDeal = false;
    public bool $mapToLead = false;
    public bool $mapToAccount = false;
    public bool $mapToQuote = false;
    public ?array $contactFieldMapping = null;
    public ?array $dealFieldMapping = null;
    public ?array $leadFieldMapping = null;
    public ?array $accountFieldMapping = null;
    public ?array $quoteFieldMapping = null;


    // Public Methods
    // =========================================================================

    public function getAuthorizeUrl(): string
    {
        return 'https://accounts.zoho.com/oauth/v2/auth';
    }

    public function getAccessTokenUrl(): string
    {
        // Populated after OAuth connection
        $url = $this->apiServer ?: 'https://accounts.zoho.com';
        $url = rtrim($url, '/');

        return "$url/oauth/v2/token";
    }

    public function getClientId(): string
    {
        return App::parseEnv($this->clientId);
    }

    public function getClientSecret(): string
    {
        return App::parseEnv($this->clientSecret);
    }

    /**
     * @inheritDoc
     */
    public function getUseDeveloper(): string
    {
        return App::parseBooleanEnv($this->useDeveloper);
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

    public function getOauthAuthorizationOptions(): array
    {
        return [
            'access_type' => 'offline',
        ];
    }

    public function beforeFetchAccessToken(&$provider): void
    {
        // Save these properties for later...
        $this->apiLocation = Craft::$app->getRequest()->getParam('location');
        $this->apiServer = Craft::$app->getRequest()->getParam('accounts-server');

        // We have to update the OAuth provider object with the new URLs provided by Zoho.
        // Annoyingly, can't just edit the provider, so create it again.
        $provider = $this->getOauthProvider();
    }
    
    public function afterFetchAccessToken($token): void
    {
        // Save these properties for later...
        $this->apiDomain = $token->getValues()['api_domain'] ?? '';

        if (!$this->apiDomain) {
            throw new Exception('Zoho response missing `api_domain`.');
        }
    }

    public function extraAttributes(): array
    {
        return ['apiDomain'];
    }

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
        $quote = $this->getFormSettingValue('quote');

        // Validate the following when saving form settings
        $rules[] = [
            ['contactFieldMapping'], 'validateFieldMapping', 'params' => $contact, 'when' => function($model) {
                return $model->enabled && $model->mapToContact;
            }, 'on' => [Integration::SCENARIO_FORM],
        ];

        $rules[] = [
            ['dealFieldMapping'], 'validateFieldMapping', 'params' => $deal, 'when' => function($model) {
                return $model->enabled && $model->mapToDeal;
            }, 'on' => [Integration::SCENARIO_FORM],
        ];

        $rules[] = [
            ['leadFieldMapping'], 'validateFieldMapping', 'params' => $lead, 'when' => function($model) {
                return $model->enabled && $model->mapToLead;
            }, 'on' => [Integration::SCENARIO_FORM],
        ];

        $rules[] = [
            ['accountFieldMapping'], 'validateFieldMapping', 'params' => $account, 'when' => function($model) {
                return $model->enabled && $model->mapToAccount;
            }, 'on' => [Integration::SCENARIO_FORM],
        ];

        $rules[] = [
            ['quoteFieldMapping'], 'validateFieldMapping', 'params' => $quote, 'when' => function($model) {
                return $model->enabled && $model->mapToQuote;
            }, 'on' => [Integration::SCENARIO_FORM],
        ];

        return $rules;
    }

    public function fetchFormSettings(): IntegrationFormSettings
    {
        $settings = [];

        try {
            $contactFields = $this->_getModuleFields('Contacts');
            $dealFields = $this->_getModuleFields('Deals');
            $leadsFields = $this->_getModuleFields('Leads');
            $accountFields = $this->_getModuleFields('Accounts');
            $quoteFields = $this->_getModuleFields('Quotes');

            $settings = [
                'contact' => $contactFields,
                'deal' => $dealFields,
                'lead' => $leadsFields,
                'account' => $accountFields,
                'quote' => $quoteFields,
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
            $dealValues = $this->getFieldMappingValues($submission, $this->dealFieldMapping, 'deal');
            $leadValues = $this->getFieldMappingValues($submission, $this->leadFieldMapping, 'lead');
            $accountValues = $this->getFieldMappingValues($submission, $this->accountFieldMapping, 'account');
            $quoteValues = $this->getFieldMappingValues($submission, $this->quoteFieldMapping, 'quote');

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

            if ($this->mapToQuote) {
                $quotePayload = [
                    'data' => [$quoteValues],
                    'duplicate_check_fields' => ['Account_Name'],
                ];

                $response = $this->deliverPayload($submission, 'Quotes/upsert', $quotePayload);

                if ($response === false) {
                    return true;
                }

                $quoteId = $response['data'][0]['details']['id'] ?? '';

                if (!$quoteId) {
                    Integration::error($this, Craft::t('formie', 'Missing return “quoteId” {response}. Sent payload {payload}', [
                        'response' => Json::encode($response),
                        'payload' => Json::encode($quotePayload),
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

        $token = $this->getToken();

        if (!$token) {
            Integration::apiError($this, 'Token not found for integration.', true);
        }

        // Populated after OAuth connection
        $url = $this->apiDomain ?? 'https://www.zohoapis.com';
        $url = rtrim($url, '/');

        if ($this->useDeveloper) {
            $url = 'https://developer.zohoapis.com';
        }

        $this->_client = Craft::createGuzzleClient([
            'base_uri' => "$url/crm/v2/",
            'headers' => [
                'Authorization' => 'Bearer ' . ($token->accessToken ?? 'empty'),
                'Content-Type' => 'application/json',
            ],
        ]);

        // Always provide an authenticated client - so check first.
        // We can't always rely on the EOL of the token.
        try {
            $response = $this->request('GET', 'Deals');
        } catch (Throwable $e) {
            if ($e->getCode() === 401) {
                // Force-refresh the token
                Formie::$plugin->getTokens()->refreshToken($token, true);

                // Then try again, with the new access token
                $this->_client = Craft::createGuzzleClient([
                    'base_uri' => "$url/crm/v2/",
                    'headers' => [
                        'Authorization' => 'Bearer ' . ($token->accessToken ?? 'empty'),
                        'Content-Type' => 'application/json',
                    ],
                ]);
            }
        }

        return $this->_client;
    }


    // Private Methods
    // =========================================================================

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

    private function _getCustomFields($fields, $excludeNames = []): array
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

            foreach ($pickListValues as $pickListValue) {
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

    private function _getModuleFields($module): array
    {
        // Ignore any errors like 'the module name given seems to be invalid' - just means the module is hidden.
        try {
            $response = $this->request('GET', 'settings/fields', ['query' => ['module' => $module]]);
            $fields = $response['fields'] ?? [];

            return $this->_getCustomFields($fields);
        } catch (Throwable $e) {
            // Just log the error, and keep going with other modules
            Integration::apiError($this, $e, false);
        }

        return [];
    }
}
