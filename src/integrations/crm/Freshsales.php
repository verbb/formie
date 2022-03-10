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
use craft\helpers\StringHelper;

use GuzzleHttp\Client;

use Throwable;

class Freshsales extends Crm
{
    // Static Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
    public static function displayName(): string
    {
        return Craft::t('formie', 'Freshsales');
    }
    

    // Properties
    // =========================================================================

    public ?string $apiKey = null;
    public ?string $apiDomain = null;
    public bool $mapToContact = false;
    public bool $mapToLead = false;
    public bool $mapToDeal = false;
    public bool $mapToAccount = false;
    public ?array $contactFieldMapping = null;
    public ?array $leadFieldMapping = null;
    public ?array $dealFieldMapping = null;
    public ?array $accountFieldMapping = null;


    // Public Methods
    // =========================================================================

    public function getDescription(): string
    {
        return Craft::t('formie', 'Manage your Freshsales customers by providing important information on their conversion on your site.');
    }

    /**
     * @inheritDoc
     */
    public function defineRules(): array
    {
        $rules = parent::defineRules();

        $rules[] = [['apiKey', 'apiDomain'], 'required'];

        $contact = $this->getFormSettingValue('contact');
        $lead = $this->getFormSettingValue('lead');
        $deal = $this->getFormSettingValue('deal');
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
            ['dealFieldMapping'], 'validateFieldMapping', 'params' => $deal, 'when' => function($model) {
                return $model->enabled && $model->mapToDeal;
            }, 'on' => [Integration::SCENARIO_FORM],
        ];

        $rules[] = [
            ['accountFieldMapping'], 'validateFieldMapping', 'params' => $account, 'when' => function($model) {
                return $model->enabled && $model->mapToAccount;
            }, 'on' => [Integration::SCENARIO_FORM],
        ];

        return $rules;
    }

    public function fetchFormSettings(): IntegrationFormSettings
    {
        $settings = [];

        try {
            // Prepare a bunch of extra details for options
            $ownerOptions = [];
            $stageOptions = [];
            $reasonOptions = [];
            $typeOptions = [];
            $sourceOptions = [];
            $campaignOptions = [];
            $pipelineOptions = [];

            $response = $this->request('GET', 'selector/owners');
            $owners = $response['users'] ?? [];

            foreach ($owners as $owner) {
                $ownerOptions[] = [
                    'label' => $owner['display_name'],
                    'value' => (string)$owner['id'],
                ];
            }

            $response = $this->request('GET', 'selector/deal_stages');
            $stages = $response['deal_stages'] ?? [];

            foreach ($stages as $stage) {
                $stageOptions[] = [
                    'label' => $stage['name'],
                    'value' => (string)$stage['id'],
                ];
            }

            $response = $this->request('GET', 'selector/deal_reasons');
            $reasons = $response['deal_reasons'] ?? [];

            foreach ($reasons as $reason) {
                $reasonOptions[] = [
                    'label' => $reason['name'],
                    'value' => (string)$reason['id'],
                ];
            }

            $response = $this->request('GET', 'selector/deal_types');
            $types = $response['deal_types'] ?? [];

            foreach ($types as $type) {
                $typeOptions[] = [
                    'label' => $type['name'],
                    'value' => (string)$type['id'],
                ];
            }

            $response = $this->request('GET', 'selector/lead_sources');
            $sources = $response['lead_sources'] ?? [];

            foreach ($sources as $source) {
                $sourceOptions[] = [
                    'label' => $source['name'],
                    'value' => (string)$source['id'],
                ];
            }

            $response = $this->request('GET', 'selector/campaigns');
            $campaigns = $response['campaigns'] ?? [];

            foreach ($campaigns as $campaign) {
                $campaignOptions[] = [
                    'label' => $campaign['name'],
                    'value' => (string)$campaign['id'],
                ];
            }

            $response = $this->request('GET', 'selector/deal_pipelines');
            $pipelines = $response['deal_pipelines'] ?? [];

            foreach ($pipelines as $pipeline) {
                $pipelineOptions[] = [
                    'label' => $pipeline['name'],
                    'value' => (string)$pipeline['id'],
                ];
            }

            // Get Contact fields
            $response = $this->request('GET', 'settings/contacts/fields');
            $fields = $response['fields'] ?? [];

            $contactFields = array_merge([
                new IntegrationField([
                    'handle' => 'first_name',
                    'name' => Craft::t('formie', 'First Name'),
                ]),
                new IntegrationField([
                    'handle' => 'last_name',
                    'name' => Craft::t('formie', 'Last Name'),
                ]),
                new IntegrationField([
                    'handle' => 'email',
                    'name' => Craft::t('formie', 'Email'),
                    'required' => true,
                ]),
                new IntegrationField([
                    'handle' => 'work_number',
                    'name' => Craft::t('formie', 'Work Number'),
                ]),
                new IntegrationField([
                    'handle' => 'mobile_number',
                    'name' => Craft::t('formie', 'Mobile'),
                ]),
                new IntegrationField([
                    'handle' => 'job_title',
                    'name' => Craft::t('formie', 'Job Title'),
                ]),
                new IntegrationField([
                    'handle' => 'address',
                    'name' => Craft::t('formie', 'Address'),
                ]),
                new IntegrationField([
                    'handle' => 'city',
                    'name' => Craft::t('formie', 'City'),
                ]),
                new IntegrationField([
                    'handle' => 'state',
                    'name' => Craft::t('formie', 'State'),
                ]),
                new IntegrationField([
                    'handle' => 'zipcode',
                    'name' => Craft::t('formie', 'Zipcode'),
                ]),
                new IntegrationField([
                    'handle' => 'country',
                    'name' => Craft::t('formie', 'Country'),
                ]),
                new IntegrationField([
                    'handle' => 'time_zone',
                    'name' => Craft::t('formie', 'Timezone'),
                ]),
            ], $this->_getCustomFields($fields));

            // Get Lead fields
            $response = $this->request('GET', 'settings/leads/fields');
            $fields = $response['fields'] ?? [];

            $leadFields = array_merge([
                new IntegrationField([
                    'handle' => 'first_name',
                    'name' => Craft::t('formie', 'First Name'),
                ]),
                new IntegrationField([
                    'handle' => 'last_name',
                    'name' => Craft::t('formie', 'Last Name'),
                ]),
                new IntegrationField([
                    'handle' => 'email',
                    'name' => Craft::t('formie', 'Email'),
                    'required' => true,
                ]),
                new IntegrationField([
                    'handle' => 'work_number',
                    'name' => Craft::t('formie', 'Work Number'),
                ]),
                new IntegrationField([
                    'handle' => 'mobile_number',
                    'name' => Craft::t('formie', 'Mobile'),
                ]),
                new IntegrationField([
                    'handle' => 'job_title',
                    'name' => Craft::t('formie', 'Job Title'),
                ]),
                new IntegrationField([
                    'handle' => 'address',
                    'name' => Craft::t('formie', 'Address'),
                ]),
                new IntegrationField([
                    'handle' => 'city',
                    'name' => Craft::t('formie', 'City'),
                ]),
                new IntegrationField([
                    'handle' => 'state',
                    'name' => Craft::t('formie', 'State'),
                ]),
                new IntegrationField([
                    'handle' => 'zipcode',
                    'name' => Craft::t('formie', 'Zipcode'),
                ]),
                new IntegrationField([
                    'handle' => 'country',
                    'name' => Craft::t('formie', 'Country'),
                ]),
                new IntegrationField([
                    'handle' => 'time_zone',
                    'name' => Craft::t('formie', 'Timezone'),
                ]),
                new IntegrationField([
                    'handle' => 'lead_stage_id',
                    'name' => Craft::t('formie', 'Lead Stage ID'),
                    'options' => [
                        'label' => Craft::t('formie', 'Stage'),
                        'options' => $stageOptions,
                    ],
                ]),
                new IntegrationField([
                    'handle' => 'lead_reason_id',
                    'name' => Craft::t('formie', 'Lead Reason ID'),
                    'options' => [
                        'label' => Craft::t('formie', 'Reason'),
                        'options' => $reasonOptions,
                    ],
                ]),
                new IntegrationField([
                    'handle' => 'lead_source_id',
                    'name' => Craft::t('formie', 'Lead Source ID'),
                    'options' => [
                        'label' => Craft::t('formie', 'Source'),
                        'options' => $sourceOptions,
                    ],
                ]),
                new IntegrationField([
                    'handle' => 'owner_id',
                    'name' => Craft::t('formie', 'Owner ID'),
                    'options' => [
                        'label' => Craft::t('formie', 'Owner'),
                        'options' => $ownerOptions,
                    ],
                ]),
                new IntegrationField([
                    'handle' => 'medium',
                    'name' => Craft::t('formie', 'Medium'),
                ]),
                new IntegrationField([
                    'handle' => 'campaign_id',
                    'name' => Craft::t('formie', 'Campaign ID'),
                    'options' => [
                        'label' => Craft::t('formie', 'Campaign'),
                        'options' => $campaignOptions,
                    ],
                ]),
                new IntegrationField([
                    'handle' => 'keyword',
                    'name' => Craft::t('formie', 'Keyword'),
                ]),
            ], $this->_getCustomFields($fields));

            // Get Deal fields
            $response = $this->request('GET', 'settings/deals/fields');
            $fields = $response['fields'] ?? [];

            $dealFields = array_merge([
                new IntegrationField([
                    'handle' => 'name',
                    'name' => Craft::t('formie', 'Name'),
                    'required' => true,
                ]),
                new IntegrationField([
                    'handle' => 'amount',
                    'name' => Craft::t('formie', 'Amount'),
                    'required' => true,
                ]),
                new IntegrationField([
                    'handle' => 'sales_account_id',
                    'name' => Craft::t('formie', 'Sales Account ID'),
                ]),
                new IntegrationField([
                    'handle' => 'deal_stage_id',
                    'name' => Craft::t('formie', 'Deal Stage ID'),
                    'options' => [
                        'label' => Craft::t('formie', 'Stage'),
                        'options' => $stageOptions,
                    ],
                ]),
                new IntegrationField([
                    'handle' => 'deal_reason_id',
                    'name' => Craft::t('formie', 'Deal Reason ID'),
                    'options' => [
                        'label' => Craft::t('formie', 'Reason'),
                        'options' => $reasonOptions,
                    ],
                ]),
                new IntegrationField([
                    'handle' => 'deal_type_id',
                    'name' => Craft::t('formie', 'Deal Type ID'),
                    'options' => [
                        'label' => Craft::t('formie', 'Type'),
                        'options' => $typeOptions,
                    ],
                ]),
                new IntegrationField([
                    'handle' => 'owner_id',
                    'name' => Craft::t('formie', 'Owner ID'),
                    'options' => [
                        'label' => Craft::t('formie', 'Owner'),
                        'options' => $ownerOptions,
                    ],
                ]),
                new IntegrationField([
                    'handle' => 'expected_close',
                    'name' => Craft::t('formie', 'Expected Close'),
                ]),
                new IntegrationField([
                    'handle' => 'closed_date',
                    'name' => Craft::t('formie', 'Closed Date'),
                ]),
                new IntegrationField([
                    'handle' => 'lead_source_id',
                    'name' => Craft::t('formie', 'Lead Source ID'),
                    'options' => [
                        'label' => Craft::t('formie', 'Source'),
                        'options' => $sourceOptions,
                    ],
                ]),
                new IntegrationField([
                    'handle' => 'campaign_id',
                    'name' => Craft::t('formie', 'Campaign ID'),
                    'options' => [
                        'label' => Craft::t('formie', 'Campaign'),
                        'options' => $campaignOptions,
                    ],
                ]),
                new IntegrationField([
                    'handle' => 'deal_product_id',
                    'name' => Craft::t('formie', 'Deal Product ID'),
                ]),
                new IntegrationField([
                    'handle' => 'deal_payment_status_id',
                    'name' => Craft::t('formie', 'Deal Payment Status ID'),
                ]),
                new IntegrationField([
                    'handle' => 'probability',
                    'name' => Craft::t('formie', 'Probability'),
                ]),
            ], $this->_getCustomFields($fields));

            // Get Account fields
            $response = $this->request('GET', 'settings/sales_accounts/fields');
            $fields = $response['fields'] ?? [];

            $accountFields = array_merge([
                new IntegrationField([
                    'handle' => 'name',
                    'name' => Craft::t('formie', 'Name'),
                    'required' => true,
                ]),
                new IntegrationField([
                    'handle' => 'address',
                    'name' => Craft::t('formie', 'Address'),
                ]),
                new IntegrationField([
                    'handle' => 'city',
                    'name' => Craft::t('formie', 'City'),
                ]),
                new IntegrationField([
                    'handle' => 'state',
                    'name' => Craft::t('formie', 'State'),
                ]),
                new IntegrationField([
                    'handle' => 'zipcode',
                    'name' => Craft::t('formie', 'Zipcode'),
                ]),
                new IntegrationField([
                    'handle' => 'country',
                    'name' => Craft::t('formie', 'Country'),
                ]),
                new IntegrationField([
                    'handle' => 'industry_type_id',
                    'name' => Craft::t('formie', 'Industry Type ID'),
                ]),
                new IntegrationField([
                    'handle' => 'business_type_id',
                    'name' => Craft::t('formie', 'Business Type ID'),
                ]),
                new IntegrationField([
                    'handle' => 'number_of_employees',
                    'name' => Craft::t('formie', 'Number of Employees'),
                ]),
                new IntegrationField([
                    'handle' => 'annual_revenue',
                    'name' => Craft::t('formie', 'Annual Revenue'),
                ]),
                new IntegrationField([
                    'handle' => 'website',
                    'name' => Craft::t('formie', 'Website'),
                ]),
                new IntegrationField([
                    'handle' => 'phone',
                    'name' => Craft::t('formie', 'Phone'),
                ]),
                new IntegrationField([
                    'handle' => 'facebook',
                    'name' => Craft::t('formie', 'Facebook'),
                ]),
                new IntegrationField([
                    'handle' => 'twitter',
                    'name' => Craft::t('formie', 'Twitter'),
                ]),
                new IntegrationField([
                    'handle' => 'linkedin',
                    'name' => Craft::t('formie', 'LinkedIn'),
                ]),
            ], $this->_getCustomFields($fields));

            $settings = [
                'contact' => $contactFields,
                'lead' => $leadFields,
                'deal' => $dealFields,
                'account' => $accountFields,
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
            $dealValues = $this->getFieldMappingValues($submission, $this->dealFieldMapping, 'deal');
            $accountValues = $this->getFieldMappingValues($submission, $this->accountFieldMapping, 'account');

            // Directly modify the field values first
            $contactFields = $this->_prepCustomFields($contactValues);
            $leadFields = $this->_prepCustomFields($leadValues);
            $dealFields = $this->_prepCustomFields($dealValues);
            $accountFields = $this->_prepCustomFields($accountValues);

            $contactId = null;

            // Send Contact payload
            if ($this->mapToContact) {
                $contactPayload = array_merge($contactValues, [
                    'custom_field' => $contactFields,
                ]);

                // Try and find the contact first, doesn't handle adding existing ones well
                $response = $this->request('POST', 'filtered_search/contact', [
                    'json' => [
                        'filter_rule' => [
                            [
                                'attribute' => 'contact_email.email',
                                'operator' => 'is_in',
                                'value' => $contactPayload['email'],
                            ],
                        ],
                    ],
                ]);

                $existingContact = $response['contacts'][0]['id'] ?? null;

                // Update or create
                if ($existingContact) {
                    $response = $this->deliverPayload($submission, "contacts/{$existingContact}", $contactPayload, 'PUT');
                } else {
                    $response = $this->deliverPayload($submission, 'contacts', $contactPayload);
                }

                if ($response === false) {
                    return true;
                }

                $contactId = $response['contact']['id'] ?? '';
            }

            $leadId = null;

            // Send Lead payload
            if ($this->mapToLead) {
                $leadPayload = array_merge($leadValues, [
                    'custom_field' => $leadFields,
                ]);

                $response = $this->deliverPayload($submission, 'leads', $leadPayload);

                if ($response === false) {
                    return true;
                }

                $leadId = $response['lead']['id'] ?? '';
            }

            $accountId = null;

            // Send Account payload
            if ($this->mapToAccount) {
                $accountPayload = array_merge($accountValues, [
                    'custom_field' => $accountFields,
                ]);

                $response = $this->deliverPayload($submission, 'sales_accounts', $accountPayload);

                if ($response === false) {
                    return true;
                }

                $accountId = $response['account']['id'] ?? '';
            }

            $dealId = null;

            // Send Deal payload
            if ($this->mapToDeal) {
                $dealPayload = array_merge($dealValues, [
                    'custom_field' => $dealFields,
                ]);

                if ($contactId) {
                    $dealPayload['contacts_added_list'] = [$contactId];
                }

                if ($accountId && !isset($dealPayload['sales_account_id'])) {
                    $dealPayload['sales_account_id'] = $accountId;
                }

                $response = $this->deliverPayload($submission, 'deals', $dealPayload);

                if ($response === false) {
                    return true;
                }

                $dealId = $response['deal']['id'] ?? '';
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
            $response = $this->request('GET', 'leads/filters');
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
            'base_uri' => "$url/api/",
            'headers' => [
                'Authorization' => 'Token token=' . App::parseEnv($this->apiKey),
                'Accept' => 'application/json',
                'Content-type' => 'application/json',
            ],
        ]);
    }


    // Private Methods
    // =========================================================================

    private function _convertFieldType($fieldType)
    {
        $fieldTypes = [
            'date' => IntegrationField::TYPE_DATETIME,
            'checkbox' => IntegrationField::TYPE_BOOLEAN,
            'decimal' => IntegrationField::TYPE_FLOAT,
            'number' => IntegrationField::TYPE_NUMBER,
        ];

        return $fieldTypes[$fieldType] ?? IntegrationField::TYPE_STRING;
    }

    private function _getCustomFields($fields, $excludeNames = []): array
    {
        $customFields = [];

        $supportedFields = [
            'text',
            'dropdown',
            'paragraph',
            'date',
            'checkbox',
            'decimal',
            'number',
        ];

        foreach ($fields as $key => $field) {
            if ($field['default']) {
                continue;
            }

            // Only allow supported types
            if (!in_array($field['type'], $supportedFields)) {
                continue;
            }

            // Exclude any names
            if (in_array($field['name'], $excludeNames)) {
                continue;
            }

            if (!StringHelper::startsWith($field['name'], 'cf_')) {
                continue;
            }

            $customFields[] = new IntegrationField([
                'handle' => 'custom:' . $field['name'],
                'name' => $field['label'],
                'type' => $this->_convertFieldType($field['type']),
                'required' => $field['required'],
            ]);
        }

        return $customFields;
    }

    private function _prepCustomFields(&$fields): array
    {
        $customFields = [];

        foreach ($fields as $key => $value) {
            if (StringHelper::startsWith($key, 'custom:')) {
                $field = ArrayHelper::remove($fields, $key);

                $customFields[str_replace('custom:', '', $key)] = $value;
            }
        }

        return $customFields;
    }
}