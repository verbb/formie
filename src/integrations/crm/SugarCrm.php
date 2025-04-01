<?php
namespace verbb\formie\integrations\crm;

use verbb\formie\Formie;
use verbb\formie\base\Crm;
use verbb\formie\base\Integration;
use verbb\formie\elements\Submission;
use verbb\formie\helpers\StringHelper;
use verbb\formie\models\IntegrationField;
use verbb\formie\models\IntegrationFormSettings;

use Craft;
use craft\helpers\App;
use craft\helpers\Json;

use Throwable;

use verbb\auth\base\OAuthProviderInterface;
use verbb\auth\models\Token;
use verbb\auth\providers\Sugarcrm as SugarCrmProvider;

use League\OAuth1\Client\Credentials\TokenCredentials as OAuth1Token;
use League\OAuth2\Client\Token\AccessToken as OAuth2Token;

class SugarCrm extends Crm implements OAuthProviderInterface
{
    // Static Methods
    // =========================================================================

    public static function supportsOAuthConnection(): bool
    {
        return true;
    }

    public static function getOAuthProviderClass(): string
    {
        return SugarCrmProvider::class;
    }

    public static function displayName(): string
    {
        return Craft::t('formie', 'SugarCRM');
    }
    

    // Properties
    // =========================================================================
    
    public ?string $username = null;
    public ?string $password = null;
    public ?string $apiDomain = null;
    public bool $mapToContact = false;
    public bool $mapToLead = false;
    public bool $mapToOpportunity = false;
    public bool $mapToAccount = false;
    public ?array $contactFieldMapping = null;
    public ?array $leadFieldMapping = null;
    public ?array $opportunityFieldMapping = null;
    public ?array $accountFieldMapping = null;


    // Public Methods
    // =========================================================================

    public function __construct(array $config = [])
    {
        // Not really needed, due to `password` OAuth grant
        $config['clientId'] = 'sugar';
        $config['clientSecret'] = 'sugar';

        parent::__construct($config);
    }

    public function getUsername(): string
    {
        return App::parseEnv($this->username);
    }

    public function getPassword(): string
    {
        return App::parseEnv($this->password);
    }

    public function getApiDomain(): string
    {
        return App::parseEnv($this->apiDomain);
    }

    public function getOAuthProviderConfig(): array
    {
        $config = parent::getOAuthProviderConfig();
        $config['url'] = $this->getApiDomain();

        return $config;
    }

    public function getAuthorizationUrl(): ?string
    {
        // Not required for `password` grant
        return $this->getRedirectUri();
    }

    public function getAccessToken(): OAuth1Token|OAuth2Token|null
    {
        $oauthProvider = $this->getOAuthProvider();

        // SugarCRM doesn't support `authorization_code` grant
        $token = $oauthProvider->getAccessToken('password', [
            'username' => $this->getUsername(),
            'password' => $this->getPassword(),
            'platform' => 'formie',
        ]);

        return $token;
    }

    public function getDescription(): string
    {
        return Craft::t('formie', 'Manage your {name} customers by providing important information on their conversion on your site.', ['name' => static::displayName()]);
    }

    public function fetchFormSettings(): IntegrationFormSettings
    {
        $settings = [];

        try {
            if ($this->mapToContact) {
                $response = $this->request('GET', 'metadata', [
                    'query' => [
                        'type_filter' => 'modules',
                        'module_filter' => 'Contacts',
                    ],
                ]);

                $fields = $response['modules']['Contacts']['fields'] ?? [];
                $settings['contact'] = $this->_getCustomFields($fields);
            }

            if ($this->mapToLead) {
                $response = $this->request('GET', 'metadata', [
                    'query' => [
                        'type_filter' => 'modules',
                        'module_filter' => 'Leads',
                    ],
                ]);

                $fields = $response['modules']['Leads']['fields'] ?? [];
                $settings['lead'] = $this->_getCustomFields($fields);
            }

            if ($this->mapToOpportunity) {
                $response = $this->request('GET', 'metadata', [
                    'query' => [
                        'type_filter' => 'modules',
                        'module_filter' => 'Opportunities',
                    ],
                ]);

                $fields = $response['modules']['Opportunities']['fields'] ?? [];
                $settings['opportunity'] = $this->_getCustomFields($fields);
            }

            if ($this->mapToAccount) {
                $response = $this->request('GET', 'metadata', [
                    'query' => [
                        'type_filter' => 'modules',
                        'module_filter' => 'Accounts',
                    ],
                ]);

                $fields = $response['modules']['Accounts']['fields'] ?? [];
                $settings['account'] = $this->_getCustomFields($fields);
            }
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

        $rules[] = [['username', 'password', 'apiDomain'], 'required'];

        $contact = $this->getFormSettingValue('contact');
        $lead = $this->getFormSettingValue('lead');
        $opportunity = $this->getFormSettingValue('opportunity');
        $account = $this->getFormSettingValue('account');

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
            ['opportunityFieldMapping'], 'validateFieldMapping', 'params' => $opportunity, 'when' => function($model) {
                return $model->enabled && $model->mapToOpportunity;
            }, 'on' => [Integration::SCENARIO_FORM],
        ];

        $rules[] = [
            ['accountFieldMapping'], 'validateFieldMapping', 'params' => $account, 'when' => function($model) {
                return $model->enabled && $model->mapToAccount;
            }, 'on' => [Integration::SCENARIO_FORM],
        ];

        return $rules;
    }


    // Private Methods
    // =========================================================================

    private function _refreshToken(Token $token, $force = false): void
    {
        $time = time();

        // Must use a custom function here because of the specific grant required.
        if (($token->endOfLife && $token->refreshToken) || $force) {
            // Has token expired ?
            if ($time > $token->endOfLife || $force) {
                $newToken = $this->getOAuthProvider()->getAccessToken('refresh_token', [
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
                }
            }
        }
    }

    private function _convertFieldType(string $fieldType): string
    {
        $fieldTypes = [
            'date' => IntegrationField::TYPE_DATE,
            'datetime' => IntegrationField::TYPE_DATETIME,
        ];

        return $fieldTypes[$fieldType] ?? IntegrationField::TYPE_STRING;
    }

    private function _getCustomFields(array $fields): array
    {
        $customFields = [];

        $supportedFields = [
            'text',
            'varchar',
            'char',
            'enum',
            'multienum',
            'phone',
            'exact',
            'url',
            'link',
            'email',
            'name',
            'yim',
            'int',
            'bool',
            'time',
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
                'sourceType' => $type,
            ]);
        }

        return $customFields;
    }
}
