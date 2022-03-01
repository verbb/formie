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
use verbb\formie\models\Token;

use Craft;
use craft\helpers\ArrayHelper;
use craft\helpers\Json;
use craft\helpers\StringHelper;
use craft\web\View;

use League\OAuth2\Client\Grant\RefreshToken;

class SugarCrm extends Crm
{
    // Properties
    // =========================================================================

    public $username;
    public $password;
    public $apiDomain;
    public $mapToContact = false;
    public $mapToLead = false;
    public $mapToOpportunity = false;
    public $mapToAccount = false;
    public $contactFieldMapping;
    public $leadFieldMapping;
    public $opportunityFieldMapping;
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
    public function oauth2Legged(): bool
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function getAuthorizeUrl(): string
    {
        return '';
    }

    /**
     * @inheritDoc
     */
    public function getAccessTokenUrl(): string
    {
        $apiDomain = rtrim(Craft::parseEnv($this->apiDomain), '/');

        return "{$apiDomain}/rest/v11/oauth2/token";;
    }

    /**
     * @inheritDoc
     */
    public function getClientId(): string
    {
        return 'sugar';
    }

    /**
     * @inheritDoc
     */
    public function getClientSecret(): string
    {
        return '';
    }

    /**
     * @inheritDoc
     */
    public function oauthCallback()
    {
        $provider = $this->getOauthProvider();

        $this->beforeFetchAccessToken($provider);

        // Get a password grant, which is different from normal
        $token = $provider->getAccessToken('password', [
            'username' => Craft::parseEnv($this->username),
            'password' => Craft::parseEnv($this->password),
            'platform' => 'formie',
        ]);

        $this->afterFetchAccessToken($token);

        return [
            'success' => true,
            'token' => $token,
        ];
    }


    // Public Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
    public static function displayName(): string
    {
        return Craft::t('formie', 'SugarCRM');
    }

    /**
     * @inheritDoc
     */
    public function getDescription(): string
    {
        return Craft::t('formie', 'Manage your SugarCRM customers by providing important information on their conversion on your site.');
    }

    /**
     * @inheritDoc
     */
    public function defineRules(): array
    {
        $rules = parent::defineRules();

        $rules[] = [['username', 'password', 'apiDomain'], 'required'];

        $contact = $this->getFormSettingValue('contact');
        $lead = $this->getFormSettingValue('lead');
        $opportunity = $this->getFormSettingValue('opportunity');
        $account = $this->getFormSettingValue('account');

        // Validate the following when saving form settings
        $rules[] = [['contactFieldMapping'], 'validateFieldMapping', 'params' => $contact, 'when' => function($model) {
            return $model->enabled && $model->mapToContact;
        }, 'on' => [Integration::SCENARIO_FORM]];

        $rules[] = [['leadFieldMapping'], 'validateFieldMapping', 'params' => $lead, 'when' => function($model) {
            return $model->enabled && $model->mapToLead;
        }, 'on' => [Integration::SCENARIO_FORM]];

        $rules[] = [['opportunityFieldMapping'], 'validateFieldMapping', 'params' => $opportunity, 'when' => function($model) {
            return $model->enabled && $model->mapToOpportunity;
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
            $response = $this->request('GET', 'metadata', [
                'query' => [
                    'type_filter' => 'modules',
                    'module_filter' => 'Contacts',
                ],
            ]);

            $fields = $response['modules']['Contacts']['fields'] ?? [];
            $contactFields = $this->_getCustomFields($fields);

            $response = $this->request('GET', 'metadata', [
                'query' => [
                    'type_filter' => 'modules',
                    'module_filter' => 'Leads',
                ],
            ]);

            $fields = $response['modules']['Leads']['fields'] ?? [];
            $leadFields = $this->_getCustomFields($fields);

            $response = $this->request('GET', 'metadata', [
                'query' => [
                    'type_filter' => 'modules',
                    'module_filter' => 'Opportunities',
                ],
            ]);

            $fields = $response['modules']['Opportunities']['fields'] ?? [];
            $opportunityFields = $this->_getCustomFields($fields);

            $response = $this->request('GET', 'metadata', [
                'query' => [
                    'type_filter' => 'modules',
                    'module_filter' => 'Accounts',
                ],
            ]);

            $fields = $response['modules']['Accounts']['fields'] ?? [];
            $accountFields = $this->_getCustomFields($fields);

            $settings = [
                'contact' => $contactFields,
                'lead' => $leadFields,
                'opportunity' => $opportunityFields,
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
            $leadValues = $this->getFieldMappingValues($submission, $this->leadFieldMapping, 'lead');
            $opportunityValues = $this->getFieldMappingValues($submission, $this->opportunityFieldMapping, 'opportunity');
            $accountValues = $this->getFieldMappingValues($submission, $this->accountFieldMapping, 'account');

            $contactPayload = $contactValues;
            $leadPayload = $leadValues;
            $opportunityPayload = $opportunityValues;
            $accountPayload = $accountValues;

            if ($this->mapToContact) {
                $response = $this->deliverPayload($submission, 'Contacts', $contactPayload);

                if ($response === false) {
                    return true;
                }

                $contactId = $response['id'] ?? '';

                if (!$contactId) {
                    Integration::error($this, Craft::t('formie', 'Missing return “contactId” {response}. Sent payload {payload}', [
                        'response' => Json::encode($response),
                        'payload' => Json::encode($contactPayload),
                    ]), true);

                    return false;
                }
            }

            if ($this->mapToLead) {
                $response = $this->deliverPayload($submission, 'Leads', $leadPayload);

                if ($response === false) {
                    return true;
                }

                $leadId = $response['id'] ?? '';

                if (!$leadId) {
                    Integration::error($this, Craft::t('formie', 'Missing return “leadId” {response}. Sent payload {payload}', [
                        'response' => Json::encode($response),
                        'payload' => Json::encode($leadPayload),
                    ]), true);

                    return false;
                }
            }

            if ($this->mapToOpportunity) {
                $response = $this->deliverPayload($submission, 'Opportunities', $opportunityPayload);

                if ($response === false) {
                    return true;
                }

                $opportunityId = $response['id'] ?? '';

                if (!$opportunityId) {
                    Integration::error($this, Craft::t('formie', 'Missing return “opportunityId” {response}. Sent payload {payload}', [
                        'response' => Json::encode($response),
                        'payload' => Json::encode($opportunityPayload),
                    ]), true);

                    return false;
                }
            }

            if ($this->mapToAccount) {
                $response = $this->deliverPayload($submission, 'Accounts', $accountPayload);

                if ($response === false) {
                    return true;
                }

                $accountId = $response['id'] ?? '';

                if (!$accountId) {
                    Integration::error($this, Craft::t('formie', 'Missing return “accountId” {response}. Sent payload {payload}', [
                        'response' => Json::encode($response),
                        'payload' => Json::encode($accountPayload),
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

        $apiDomain = rtrim(Craft::parseEnv($this->apiDomain), '/');
        $token = $this->getToken();

        $this->_client = Craft::createGuzzleClient([
            'base_uri' => "{$apiDomain}/rest/v11/",
            'headers' => [
                'Authorization' => 'Bearer ' . ($token->accessToken ?? 'empty'),
                'Content-Type' => 'application/json',
            ],
        ]);

        // Always provide an authenticated client - so check first.
        // We can't always rely on the EOL of the token.
        try {
            $response = $this->request('GET', 'ping');
        } catch (\Throwable $e) {
            if ($e->getCode() === 401) {
                // Force-refresh the token
                $this->_refreshToken($token, true);

                // Then try again, with the new access token
                $this->_client = Craft::createGuzzleClient([
                    'base_uri' => "{$apiDomain}/rest/v11/",
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

    /**
     * @inheritDoc
     */
    private function _refreshToken(Token $token, $force = false)
    {
        $time = time();

        // Must use a custom function here because of the specific grant required.
        if ($token->endOfLife && $token->refreshToken || $force) {
            // Has token expired ?
            if ($time > $token->endOfLife || $force) {
                $newToken = $this->getOauthProvider()->getAccessToken('refresh_token', [
                    'refresh_token' => $token->refreshToken,
                    'platform' => 'formie',
                ]);

                if ($newToken) {
                    $token->accessToken = $newToken->getToken();
                    $token->endOfLife = $newToken->getExpires();

                    $newRefreshToken = $newToken->getRefreshToken();

                    if (!empty($newRefreshToken)) {
                        $token->refreshToken = $newToken->getRefreshToken();
                    }

                    Formie::$plugin->getTokens()->saveToken($token);

                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @inheritDoc
     */
    private function _convertFieldType($fieldType)
    {
        $fieldTypes = [
            'date' => IntegrationField::TYPE_DATE,
            'datetime' => IntegrationField::TYPE_DATETIME,
        ];

        return $fieldTypes[$fieldType] ?? IntegrationField::TYPE_STRING;
    }

    /**
     * @inheritDoc
     */
    private function _getCustomFields($fields)
    {
        $customFields = [];

        $supportedFields = [
            'text',
            'varchar',
            'phone',
            'exact',
            'email',
            'name',
            'date',
            'datetime',
        ];

        foreach ($fields as $key => $field) {
            $name = $field['name'] ?? '';
            $type = $field['type'] ?? '';

            if (!$type || !$name) {
                continue;
            }

            $name = StringHelper::titleize(str_replace('_', ' ', $name));

            // Only allow supported types
            if (!in_array($type, $supportedFields)) {
                 continue;
            }

            $customFields[] = new IntegrationField([
                'handle' => $key,
                'name' => $name,
                'type' => $this->_convertFieldType($type),
            ]);
        }

        return $customFields;
    }
}
