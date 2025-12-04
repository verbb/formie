<?php
namespace verbb\formie\integrations\crm;

use verbb\formie\Formie;
use verbb\formie\base\Crm;
use verbb\formie\base\Integration;
use verbb\formie\base\OAuthIntegrationTrait;
use verbb\formie\elements\Submission;
use verbb\formie\models\IntegrationField;
use verbb\formie\models\IntegrationFormSettings;

use Craft;
use craft\helpers\App;
use craft\helpers\Json;

use Throwable;
use Exception;

use verbb\auth\base\OAuthProviderInterface;
use verbb\auth\models\Token;
use verbb\auth\providers\Zoho as ZohoProvider;

class Zoho extends Crm implements OAuthProviderInterface
{
    // Static Methods
    // =========================================================================

    public static function supportsOAuthConnection(): bool
    {
        return true;
    }

    public static function getOAuthProviderClass(): string
    {
        return ZohoProvider::class;
    }

    public static function displayName(): string
    {
        return Craft::t('formie', 'Zoho');
    }
    

    // Properties
    // =========================================================================
    
    public bool|string $useDeveloper = false;
    public ?string $dataCenter = 'US';
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

    public function __construct($config = [])
    {
        unset($config['apiServer'], $config['apiLocation'], $config['apiDomain']);

        parent::__construct($config);
    }

    public function getUseDeveloper(): string
    {
        return App::parseBooleanEnv($this->useDeveloper);
    }

    public function getDataCenter(): ?string
    {
        return App::parseEnv($this->dataCenter);
    }

    public function getBaseApiUrl(?Token $token): ?string
    {
        $url = parent::getBaseApiUrl($token);

        return "$url/crm/v2/";
    }

    public function getOAuthProviderConfig(): array
    {
        $config = parent::getOAuthProviderConfig();
        $config['baseApiUrl'] = fn(?Token $token) => $this->getBaseApiUrl($token);
        $config['dc'] = $this->getDataCenter();
        $config['useDeveloper'] = $this->getUseDeveloper();

        return $config;
    }

    public function getAuthorizationUrlOptions(): array
    {
        $options = parent::getAuthorizationUrlOptions();
        $options['access_type'] = 'offline';
        $options['prompt'] = 'consent';

        $options['scope'] = [
            'ZohoCRM.modules.ALL',
            'ZohoCRM.settings.ALL',
        ];
        
        return $options;
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
                $settings['contact'] = $this->_getModuleFields('Contacts');
            }

            if ($this->mapToDeal) {
                $settings['deal'] = $this->_getModuleFields('Deals');
            }

            if ($this->mapToLead) {
                $settings['lead'] = $this->_getModuleFields('Leads');
            }

            if ($this->mapToAccount) {
                $settings['account'] = $this->_getModuleFields('Accounts');
            }

            if ($this->mapToQuote) {
                $settings['quote'] = $this->_getModuleFields('Quotes');
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

                    $response = $this->deliverPayload($submission, "Contacts/{$contactId}/Deals/{$dealId}", $payload, 'PUT');

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

    
    // Protected Methods
    // =========================================================================

    protected function defineRules(): array
    {
        $rules = parent::defineRules();

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


    // Private Methods
    // =========================================================================

    private function _convertFieldType(string $fieldType): string
    {
        $fieldTypes = [
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

    private function _getCustomFields(array $fields, array $excludeNames = []): array
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
                'sourceType' => $fieldType,
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
