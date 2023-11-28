<?php
namespace verbb\formie\integrations\crm;

use verbb\formie\Formie;
use verbb\formie\auth\OneCrmProvider;
use verbb\formie\base\Crm;
use verbb\formie\base\Integration;
use verbb\formie\elements\Submission;
use verbb\formie\helpers\ArrayHelper;
use verbb\formie\models\IntegrationField;
use verbb\formie\models\IntegrationFormSettings;

use Craft;
use craft\helpers\App;
use craft\helpers\Json;

use GuzzleHttp\Client;

use Throwable;

use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth1\Client\Server\Server as Oauth1Provider;

class OneCrm extends Crm
{
    // Static Methods
    // =========================================================================

    public static function supportsOauthConnection(): bool
    {
        return true;
    }

    public static function displayName(): string
    {
        return Craft::t('formie', '1CRM');
    }


    // Properties
    // =========================================================================

    public ?string $clientId = null;
    public ?string $clientSecret = null;
    public ?string $apiDomain = null;
    public bool $mapToContact = false;
    public bool $mapToLead = false;
    public bool $mapToAccount = false;
    public bool $mapToOpportunity = false;
    public ?array $contactFieldMapping = null;
    public ?array $leadFieldMapping = null;
    public ?array $accountFieldMapping = null;
    public ?array $opportunityFieldMapping = null;


    // Public Methods
    // =========================================================================

    public function getClassHandle(): string
    {
        return 'one-crm';
    }

    public function getAuthorizeUrl(): string
    {
        return $this->getApiDomain() . 'auth/user/authorize';
    }

    public function getAccessTokenUrl(): string
    {
        return $this->getApiDomain() . 'auth/user/access_token';
    }

    public function getClientId(): string
    {
        return App::parseEnv($this->clientId);
    }

    public function getClientSecret(): string
    {
        return App::parseEnv($this->clientSecret);
    }

    public function getApiDomain(): string
    {
        return rtrim(App::parseEnv($this->apiDomain), '/') . '/api.php/';
    }

    public function getOauthScope(): array
    {
        return [
            'read',
            'write',
            'profile',
        ];
    }

    public function getOauthProviderConfig(): array
    {
        return array_merge(parent::getOauthProviderConfig(), [
            'scopeSeparator' => ' ',
        ]);
    }

    public function getOauthProvider(): AbstractProvider|Oauth1Provider
    {
        return new OneCrmProvider($this->getOauthProviderConfig());
    }

    public function getDescription(): string
    {
        return Craft::t('formie', 'Manage your 1CRM customers by providing important information on their conversion on your site.');
    }

    public function fetchFormSettings(): IntegrationFormSettings
    {
        $settings = [];

        // Populate some options for some values
        try {
            $response = $this->request('GET', 'meta/fields/Contact');
            $fields = $response['fields'] ?? [];

            $contactFields = array_merge([
                new IntegrationField([
                    'handle' => 'email1',
                    'name' => Craft::t('formie', 'Email'),
                    'required' => true,
                ]),
            ], $this->_getCustomFields($fields, ['email1']));

            $response = $this->request('GET', 'meta/fields/Lead');
            $fields = $response['fields'] ?? [];

            $leadFields = array_merge([
                new IntegrationField([
                    'handle' => 'email1',
                    'name' => Craft::t('formie', 'Email'),
                    'required' => true,
                ]),
            ], $this->_getCustomFields($fields, ['email1']));

            $response = $this->request('GET', 'meta/fields/Account');
            $fields = $response['fields'] ?? [];

            $accountFields = array_merge([
                new IntegrationField([
                    'handle' => 'email1',
                    'name' => Craft::t('formie', 'Email'),
                    'required' => true,
                ]),
            ], $this->_getCustomFields($fields, ['email1']));

            $response = $this->request('GET', 'meta/fields/Opportunity');
            $fields = $response['fields'] ?? [];

            $opportunityFields = array_merge([
                new IntegrationField([
                    'handle' => 'name',
                    'name' => Craft::t('formie', 'Opportunity Name'),
                    'required' => true,
                ]),
                new IntegrationField([
                    'handle' => 'amount',
                    'name' => Craft::t('formie', 'Amount'),
                    'required' => true,
                ]),
                new IntegrationField([
                    'handle' => 'date_closed',
                    'name' => Craft::t('formie', 'Date Closed'),
                    'required' => true,
                    'type' => IntegrationField::TYPE_DATETIME,
                ]),
            ], $this->_getCustomFields($fields, ['name', 'amount', 'amount_usdollar']));

            $settings = [
                'contact' => $contactFields,
                'lead' => $leadFields,
                'account' => $accountFields,
                'opportunity' => $opportunityFields,
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
            $leadValues = $this->getFieldMappingValues($submission, $this->leadFieldMapping, 'lead');
            $accountValues = $this->getFieldMappingValues($submission, $this->accountFieldMapping, 'account');
            $opportunityValues = $this->getFieldMappingValues($submission, $this->opportunityFieldMapping, 'opportunity');

            $contactId = null;
            $leadId = null;
            $accountId = null;
            $opportunityId = null;

            if ($this->mapToContact) {
                $contactPayload = [
                    'data' => $contactValues,
                ];

                // Find existing contacts
                $response = $this->request('GET', 'data/Contact', [
                    'json' => [
                        'filters' => ['email' => $contactValues['email'] ?? ''],
                    ],
                ]);

                $existingContactId = $response['records'][0]['id'] ?? null;

                // Update or create
                if ($existingContactId) {
                    $response = $this->deliverPayload($submission, "data/Contact/{$existingContactId}", $contactPayload, 'PATCH');
                } else {
                    $response = $this->deliverPayload($submission, 'data/Contact', $contactPayload);
                }

                if ($response === false) {
                    return true;
                }

                $contactId = $existingContactId ?? $response['id'] ?? '';
            }

            if ($this->mapToLead) {
                $leadPayload = [
                    'data' => $leadValues,
                ];

                // Find existing leads
                $response = $this->request('GET', 'data/Lead', [
                    'json' => [
                        'filters' => ['email' => $leadValues['email'] ?? ''],
                    ],
                ]);

                $existingLeadId = $response['records'][0]['id'] ?? null;

                // Update or create
                if ($existingLeadId) {
                    $response = $this->deliverPayload($submission, "data/Lead/{$existingLeadId}", $leadPayload, 'PATCH');
                } else {
                    $response = $this->deliverPayload($submission, 'data/Lead', $leadPayload);
                }

                if ($response === false) {
                    return true;
                }

                $leadId = $existingLeadId ?? $response['id'] ?? '';
            }

            if ($this->mapToAccount) {
                $accountPayload = [
                    'data' => $accountValues,
                ];

                // Find existing accounts
                $response = $this->request('GET', 'data/Account', [
                    'json' => [
                        'filters' => ['email' => $accountValues['email'] ?? ''],
                    ],
                ]);

                $existingAccountId = $response['records'][0]['id'] ?? null;

                // Update or create
                if ($existingAccountId) {
                    $response = $this->deliverPayload($submission, "data/Account/{$existingAccountId}", $accountPayload, 'PATCH');
                } else {
                    $response = $this->deliverPayload($submission, 'data/Account', $accountPayload);
                }

                if ($response === false) {
                    return true;
                }

                $accountId = $existingAccountId ?? $response['id'] ?? '';
            }

            if ($this->mapToOpportunity) {
                $opportunityPayload = [
                    'data' => $opportunityValues,
                ];

                if ($accountId) {
                    $opportunityPayload['data']['account_id'] = $accountId;
                }

                $response = $this->deliverPayload($submission, 'data/Opportunity', $opportunityPayload);

                if ($response === false) {
                    return true;
                }

                $opportunityId = $existingOpportunityId ?? $response['id'] ?? '';
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
            Integration::error($this, 'Token not found for integration.', true);
        }

        $this->_client = Craft::createGuzzleClient([
            'base_uri' => $this->getApiDomain(),
            'headers' => [
                'Authorization' => 'Bearer ' . ($token->accessToken ?? 'empty'),
                'Content-Type' => 'application/json',
            ],
        ]);

        // Always provide an authenticated client - so check first.
        // We can't always rely on the EOL of the token.
        try {
            $response = $this->request('GET', 'account');
        } catch (Throwable $e) {
            if ($e->getCode() === 401) {
                // Force-refresh the token
                Formie::$plugin->getTokens()->refreshToken($token, true);

                // Then try again, with the new access token
                $this->_client = Craft::createGuzzleClient([
                    'base_uri' => $this->getApiDomain(),
                    'headers' => [
                        'Authorization' => 'Bearer ' . ($token->accessToken ?? 'empty'),
                        'Content-Type' => 'application/json',
                    ],
                ]);
            }
        }

        return $this->_client;
    }


    // Protected Methods
    // =========================================================================

    protected function defineRules(): array
    {
        $rules = parent::defineRules();

        $rules[] = [['clientId', 'clientSecret', 'apiDomain'], 'required'];

        $contact = $this->getFormSettingValue('contact');
        $lead = $this->getFormSettingValue('lead');
        $account = $this->getFormSettingValue('account');
        $opportunity = $this->getFormSettingValue('opportunity');

        // Validate the following when saving form settings
        $rules[] = [
            ['contactFieldMapping'], 'validateFieldMapping', 'params' => $contact, 'when' => function($model) {
                return $model->enabled && $model->mapToContact;
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
            ['opportunityFieldMapping'], 'validateFieldMapping', 'params' => $opportunity, 'when' => function($model) {
                return $model->enabled && $model->mapToOpportunity;
            }, 'on' => [Integration::SCENARIO_FORM],
        ];

        return $rules;
    }


    // Private Methods
    // =========================================================================

    private function _convertFieldType(string $fieldType): string
    {
        $fieldTypes = [
            'bool' => IntegrationField::TYPE_BOOLEAN,
            'int' => IntegrationField::TYPE_NUMBER,
            'double' => IntegrationField::TYPE_FLOAT,
            'date' => IntegrationField::TYPE_DATE,
            'datetime' => IntegrationField::TYPE_DATETIME,
        ];

        return $fieldTypes[$fieldType] ?? IntegrationField::TYPE_STRING;
    }

    private function _getCustomFields(array $fields, array $excludeNames = []): array
    {
        $customFields = [];

        foreach ($fields as $field) {
            $handle = $field['name'] ?? null;
            $name = $field['vname'] ?? null;
            $type = $field['type'] ?? null;
            $editable = $field['editable'] ?? true;

            if (!$editable || !$name || !$handle) {
                continue;
            }

            // Exclude any names
            if (in_array($handle, $excludeNames)) {
                continue;
            }

            // Add in any options for some fields
            $options = [];

            foreach (($field['options'] ?? []) as $fieldOption) {
                $options[] = [
                    'label' => $fieldOption['label'],
                    'value' => $fieldOption['value'],
                ];
            }

            if ($options) {
                $options = [
                    'label' => $name,
                    'options' => $options,
                ];
            }

            $customFields[] = new IntegrationField([
                'handle' => (string)$handle,
                'name' => (string)$name,
                'type' => $this->_convertFieldType($type),
                'options' => $options,
            ]);
        }

        return $customFields;
    }
}