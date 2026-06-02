<?php
namespace verbb\formie\integrations\crm;

use verbb\formie\Formie;
use verbb\formie\base\Crm;
use verbb\formie\base\FormInterface;
use verbb\formie\base\Integration;
use verbb\formie\helpers\SchemaHelper;
use verbb\formie\elements\Submission;
use verbb\formie\helpers\ArrayHelper;
use verbb\formie\models\IntegrationField;
use verbb\formie\models\IntegrationFormSettings;

use Craft;
use craft\helpers\App;
use craft\helpers\Json;

use Throwable;

use verbb\auth\base\OAuthProviderInterface;
use verbb\auth\models\Token;
use verbb\auth\providers\OneCrm as OneCrmProvider;

class OneCrm extends Crm implements OAuthProviderInterface
{
    // Static Methods
    // =========================================================================

    public static function supportsOAuthConnection(): bool
    {
        return true;
    }

    public static function getOAuthProviderClass(): string
    {
        return OneCrmProvider::class;
    }

    public static function displayName(): string
    {
        return Craft::t('formie', '1CRM');
    }


    // Properties
    // =========================================================================

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

    public function getApiDomain(): string
    {
        return App::parseEnv($this->apiDomain);
    }

    public function getOAuthProviderConfig(): array
    {
        $config = parent::getOAuthProviderConfig();
        $config['apiDomain'] = $this->getApiDomain();

        return $config;
    }

    public function getAuthorizationUrlOptions(): array
    {
        $options = parent::getAuthorizationUrlOptions();

        $options['scope'] = [
            'read',
            'write',
            'profile',
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

        // Populate some options for some values
        try {
            if ($this->mapToContact && $this->settingsContext->dataKey === 'contact') {
                $response = $this->request('GET', 'meta/fields/Contact');
                $fields = $response['fields'] ?? [];

                $settings['contact'] = array_merge([
                    // Extracted to make required
                    new IntegrationField([
                        'handle' => 'email1',
                        'name' => Craft::t('formie', 'Email'),
                        'required' => true,
                    ]),
                ], $this->_getCustomFields($fields, ['email1']));
            }

            if ($this->mapToLead && $this->settingsContext->dataKey === 'lead') {
                $response = $this->request('GET', 'meta/fields/Lead');
                $fields = $response['fields'] ?? [];

                $settings['lead'] = array_merge([
                    // Extracted to make required
                    new IntegrationField([
                        'handle' => 'email1',
                        'name' => Craft::t('formie', 'Email'),
                        'required' => true,
                    ]),
                ], $this->_getCustomFields($fields, ['email1']));
            }

            if ($this->mapToAccount && $this->settingsContext->dataKey === 'account') {
                $response = $this->request('GET', 'meta/fields/Account');
                $fields = $response['fields'] ?? [];

                $settings['account'] = array_merge([
                    // Extracted to make required
                    new IntegrationField([
                        'handle' => 'email1',
                        'name' => Craft::t('formie', 'Email'),
                        'required' => true,
                    ]),
                ], $this->_getCustomFields($fields, ['email1']));
            }

            if ($this->mapToOpportunity && $this->settingsContext->dataKey === 'opportunity') {
                $response = $this->request('GET', 'meta/fields/Opportunity');
                $fields = $response['fields'] ?? [];

                $settings['opportunity'] = array_merge([
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
                $existingContactId = null;
                $email = $contactValues['email1'] ?? null;

                if ($email) {
                    $response = $this->request('GET', 'data/Contact', [
                        'query' => [
                            'filters' => ['any_email' => $email],
                        ],
                    ]);

                    $existingContactId = $response['records'][0]['id'] ?? null;
                }

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
                $existingLeadId = null;
                $email = $leadValues['email1'] ?? null;

                if ($email) {
                    $response = $this->request('GET', 'data/Lead', [
                        'json' => [
                            'filters' => ['any_email' => $email],
                        ],
                    ]);

                    $existingLeadId = $response['records'][0]['id'] ?? null;
                }

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
                $existingAccountId = null;
                $email = $accountValues['email1'] ?? null;

                if ($email) {
                    $response = $this->request('GET', 'data/Account', [
                        'json' => [
                            'filters' => ['any_email' => $email],
                        ],
                    ]);

                    $existingAccountId = $response['records'][0]['id'] ?? null;
                }

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


    // Protected Methods
    // =========================================================================

    protected function defineRules(): array
    {
        $rules = parent::defineRules();

        $rules[] = [['apiDomain'], 'required'];

        $contact = $this->getFormSettingValue('contact');
        $lead = $this->getFormSettingValue('lead');
        $account = $this->getFormSettingValue('account');
        $opportunity = $this->getFormSettingValue('opportunity');

        // Validate the following when saving form settings
        $rules[] = [
            ['contactFieldMapping'], 'validateFieldMapping', 'params' => $contact, 'when' => function($model) {
                return $model->enabled && $model->mapToContact;
            }, 'on' => [Integration::SCENARIO_FORM], 'skipOnEmpty' => false,
        ];

        $rules[] = [
            ['leadFieldMapping'], 'validateFieldMapping', 'params' => $lead, 'when' => function($model) {
                return $model->enabled && $model->mapToLead;
            }, 'on' => [Integration::SCENARIO_FORM], 'skipOnEmpty' => false,
        ];

        $rules[] = [
            ['accountFieldMapping'], 'validateFieldMapping', 'params' => $account, 'when' => function($model) {
                return $model->enabled && $model->mapToAccount;
            }, 'on' => [Integration::SCENARIO_FORM], 'skipOnEmpty' => false,
        ];

        $rules[] = [
            ['opportunityFieldMapping'], 'validateFieldMapping', 'params' => $opportunity, 'when' => function($model) {
                return $model->enabled && $model->mapToOpportunity;
            }, 'on' => [Integration::SCENARIO_FORM], 'skipOnEmpty' => false,
        ];

        return $rules;
    }

    protected function defineFormSettingsSchema(FormInterface $form): array
    {
        $schema = parent::defineFormSettingsSchema($form);
        $schema[] = SchemaHelper::lightswitchField([
            'name' => 'mapToContact',
            'label' => Craft::t('formie', 'Map to {name}', ['name' => 'Contact']),
            'instructions' => Craft::t('formie', 'Whether to map form data to {name} {label}.', ['name' => $this->displayName(), 'label' => 'Contacts']),
        ]);
        $schema[] = $this->getIntegrationFieldMappingField([
            'name' => 'contactFieldMapping',
            'if' => 'mapToContact',
            'dataLabel' => 'Contact',
            'dataKey' => 'contact',
        ]);
        $schema[] = SchemaHelper::lightswitchField([
            'name' => 'mapToLead',
            'label' => Craft::t('formie', 'Map to {name}', ['name' => 'Lead']),
            'instructions' => Craft::t('formie', 'Whether to map form data to {name} {label}.', ['name' => $this->displayName(), 'label' => 'Leads']),
        ]);
        $schema[] = $this->getIntegrationFieldMappingField([
            'name' => 'leadFieldMapping',
            'if' => 'mapToLead',
            'dataLabel' => 'Lead',
            'dataKey' => 'lead',
        ]);
        $schema[] = SchemaHelper::lightswitchField([
            'name' => 'mapToAccount',
            'label' => Craft::t('formie', 'Map to {name}', ['name' => 'Account']),
            'instructions' => Craft::t('formie', 'Whether to map form data to {name} {label}.', ['name' => $this->displayName(), 'label' => 'Accounts']),
        ]);
        $schema[] = $this->getIntegrationFieldMappingField([
            'name' => 'accountFieldMapping',
            'if' => 'mapToAccount',
            'dataLabel' => 'Account',
            'dataKey' => 'account',
        ]);
        $schema[] = SchemaHelper::lightswitchField([
            'name' => 'mapToOpportunity',
            'label' => Craft::t('formie', 'Map to {name}', ['name' => 'Opportunity']),
            'instructions' => Craft::t('formie', 'Whether to map form data to {name} {label}.', ['name' => $this->displayName(), 'label' => 'Opportunities']),
        ]);
        $schema[] = $this->getIntegrationFieldMappingField([
            'name' => 'opportunityFieldMapping',
            'if' => 'mapToOpportunity',
            'dataLabel' => 'Opportunity',
            'dataKey' => 'opportunity',
        ]);

        return $schema;
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
                'sourceType' => $type,
                'options' => $options,
            ]);
        }

        return $customFields;
    }
}