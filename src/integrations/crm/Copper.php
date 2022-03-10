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

class Copper extends Crm
{
    // Static Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
    public static function displayName(): string
    {
        return Craft::t('formie', 'Copper');
    }
    

    // Properties
    // =========================================================================

    public ?string $apiKey = null;
    public ?string $apiEmail = null;
    public bool $mapToPeople = false;
    public bool $mapToLead = false;
    public bool $mapToOpportunity = false;
    public bool $mapToTask = false;
    public ?array $peopleFieldMapping = null;
    public ?array $leadFieldMapping = null;
    public ?array $opportunityFieldMapping = null;
    public ?array $taskFieldMapping = null;


    // Public Methods
    // =========================================================================

    public function getDescription(): string
    {
        return Craft::t('formie', 'Manage your Copper customers by providing important information on their conversion on your site.');
    }

    /**
     * @inheritDoc     */
    public function defineRules(): array
    {
        $rules = parent::defineRules();

        $rules[] = [['apiKey', 'apiEmail'], 'required'];

        $people = $this->getFormSettingValue('people');
        $lead = $this->getFormSettingValue('lead');
        $opportunity = $this->getFormSettingValue('opportunity');
        $task = $this->getFormSettingValue('task');

        // Validate the following when saving form settings
        $rules[] = [
            ['peopleFieldMapping'], 'validateFieldMapping', 'params' => $people, 'when' => function($model) {
                return $model->enabled && $model->mapToPeople;
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
            ['taskFieldMapping'], 'validateFieldMapping', 'params' => $task, 'when' => function($model) {
                return $model->enabled && $model->mapToTask;
            }, 'on' => [Integration::SCENARIO_FORM],
        ];

        return $rules;
    }

    public function fetchFormSettings(): IntegrationFormSettings
    {
        $settings = [];
        try {
            // Get Custom fields
            $fields = $this->request('GET', 'custom_field_definitions');

            // Get People fields
            $contactTypeOptions = [];
            $contactTypes = $this->request('GET', 'contact_types');

            foreach ($contactTypes as $contactType) {
                $contactTypeOptions[] = [
                    'label' => $contactType['name'],
                    'value' => $contactType['id'],
                ];
            }

            $peopleFields = array_merge([
                new IntegrationField([
                    'handle' => 'name',
                    'name' => Craft::t('formie', 'Name'),
                    'required' => true,
                ]),
                new IntegrationField([
                    'handle' => 'assignee_id',
                    'name' => Craft::t('formie', 'Assignee'),
                ]),
                new IntegrationField([
                    'handle' => 'company_id',
                    'name' => Craft::t('formie', 'Company'),
                ]),
                new IntegrationField([
                    'handle' => 'company_name',
                    'name' => Craft::t('formie', 'Company Name'),
                ]),
                new IntegrationField([
                    'handle' => 'contact_type_id',
                    'name' => Craft::t('formie', 'Contact Type'),
                    'options' => [
                        'label' => Craft::t('formie', 'contact_type'),
                        'options' => $contactTypeOptions,
                    ],
                ]),
                new IntegrationField([
                    'handle' => 'details',
                    'name' => Craft::t('formie', 'Details'),
                ]),
                new IntegrationField([
                    'handle' => 'emails',
                    'name' => Craft::t('formie', 'Email'),
                ]),
                new IntegrationField([
                    'handle' => 'phone_numbers',
                    'name' => Craft::t('formie', 'Phone Number'),
                ]),
                new IntegrationField([
                    'handle' => 'socials',
                    'name' => Craft::t('formie', 'Social'),
                ]),
                new IntegrationField([
                    'handle' => 'title',
                    'name' => Craft::t('formie', 'Title'),
                ]),
                new IntegrationField([
                    'handle' => 'websites',
                    'name' => Craft::t('formie', 'Website'),
                ]),
            ], $this->_getCustomFields($fields, 'person'));

            // Get Lead fields
            $customerSourceOptions = [];
            $customerSources = $this->request('GET', 'customer_sources');

            foreach ($customerSources as $customerSource) {
                $customerSourceOptions[] = [
                    'label' => $customerSource['name'],
                    'value' => $customerSource['id'],
                ];
            }

            $leadFields = array_merge([
                new IntegrationField([
                    'handle' => 'name',
                    'name' => Craft::t('formie', 'Name'),
                    'required' => true,
                ]),
                new IntegrationField([
                    'handle' => 'assignee_id',
                    'name' => Craft::t('formie', 'Assignee'),
                ]),
                new IntegrationField([
                    'handle' => 'company_name',
                    'name' => Craft::t('formie', 'Company Name'),
                ]),
                new IntegrationField([
                    'handle' => 'customer_source_id',
                    'name' => Craft::t('formie', 'Customer Source'),
                    'options' => [
                        'label' => Craft::t('formie', 'Customer Source'),
                        'options' => $customerSourceOptions,
                    ],
                ]),
                new IntegrationField([
                    'handle' => 'details',
                    'name' => Craft::t('formie', 'Details'),
                ]),
                new IntegrationField([
                    'handle' => 'email',
                    'name' => Craft::t('formie', 'Email'),
                ]),
                new IntegrationField([
                    'handle' => 'monetary_value',
                    'name' => Craft::t('formie', 'Monetary Value'),
                ]),
                new IntegrationField([
                    'handle' => 'phone_numbers',
                    'name' => Craft::t('formie', 'Phone Number'),
                ]),
                new IntegrationField([
                    'handle' => 'socials',
                    'name' => Craft::t('formie', 'Social'),
                ]),
                new IntegrationField([
                    'handle' => 'status',
                    'name' => Craft::t('formie', 'Status'),
                    'options' => [
                        'label' => Craft::t('formie', 'Status'),
                        'options' => [
                            [
                                'label' => Craft::t('formie', 'New'),
                                'value' => 'New',
                            ],
                            [
                                'label' => Craft::t('formie', 'Unqualified'),
                                'value' => 'Unqualified',
                            ],
                            [
                                'label' => Craft::t('formie', 'Contacted'),
                                'value' => 'Contacted',
                            ],
                            [
                                'label' => Craft::t('formie', 'Qualified'),
                                'value' => 'Qualified',
                            ],
                        ],
                    ],
                ]),
                new IntegrationField([
                    'handle' => 'websites',
                    'name' => Craft::t('formie', 'Website'),
                ]),
            ], $this->_getCustomFields($fields, 'lead'));

            // Get Opportunity fields
            $sourceOptions = [];
            $lossReasonOptions = [];
            $pipelineOptions = [];
            $stageOptions = [];

            $customerSources = $this->request('GET', 'customer_sources');
            $lossReasons = $this->request('GET', 'loss_reasons');
            $pipelines = $this->request('GET', 'pipelines');
            $pipelineStages = $this->request('GET', 'pipeline_stages');

            foreach ($customerSources as $customerSource) {
                $sourceOptions[] = [
                    'label' => $customerSource['name'],
                    'value' => $customerSource['id'],
                ];
            }

            foreach ($lossReasons as $lossReason) {
                $lossReasonOptions[] = [
                    'label' => $lossReason['name'],
                    'value' => $lossReason['id'],
                ];
            }

            foreach ($pipelines as $pipeline) {
                $pipelineOptions[] = [
                    'label' => $pipeline['name'],
                    'value' => $pipeline['id'],
                ];
            }

            foreach ($pipelineStages as $pipelineStage) {
                $stageOptions[] = [
                    'label' => $pipelineStage['name'],
                    'value' => $pipelineStage['id'],
                ];
            }

            $opportunityFields = array_merge([
                new IntegrationField([
                    'handle' => 'name',
                    'name' => Craft::t('formie', 'Name'),
                    'required' => true,
                ]),
                new IntegrationField([
                    'handle' => 'assignee_id',
                    'name' => Craft::t('formie', 'Assignee'),
                ]),
                new IntegrationField([
                    'handle' => 'close_date',
                    'name' => Craft::t('formie', 'Close Date'),
                ]),
                new IntegrationField([
                    'handle' => 'company_id',
                    'name' => Craft::t('formie', 'Company'),
                ]),
                new IntegrationField([
                    'handle' => 'company_name',
                    'name' => Craft::t('formie', 'Company Name'),
                ]),
                new IntegrationField([
                    'handle' => 'customer_source_id',
                    'name' => Craft::t('formie', 'Customer Source'),
                    'options' => [
                        'label' => Craft::t('formie', 'Customer Source'),
                        'options' => $sourceOptions,
                    ],
                ]),
                new IntegrationField([
                    'handle' => 'details',
                    'name' => Craft::t('formie', 'Details'),
                ]),
                new IntegrationField([
                    'handle' => 'loss_reason_id',
                    'name' => Craft::t('formie', 'Loss Reason'),
                    'options' => [
                        'label' => Craft::t('formie', 'Loss Reason'),
                        'options' => $lossReasonOptions,
                    ],
                ]),
                new IntegrationField([
                    'handle' => 'monetary_value',
                    'name' => Craft::t('formie', 'Monetary Value'),
                ]),
                new IntegrationField([
                    'handle' => 'pipeline_id',
                    'name' => Craft::t('formie', 'pipeline_id'),
                    'options' => [
                        'label' => Craft::t('formie', 'Pipelines'),
                        'options' => $pipelineOptions,
                    ],
                ]),
                new IntegrationField([
                    'handle' => 'priority',
                    'name' => Craft::t('formie', 'Priority'),
                    'options' => [
                        'label' => Craft::t('formie', 'Priority'),
                        'options' => [
                            [
                                'label' => Craft::t('formie', 'None'),
                                'value' => 'None',
                            ],
                            [
                                'label' => Craft::t('formie', 'Low'),
                                'value' => 'Low',
                            ],
                            [
                                'label' => Craft::t('formie', 'Medium'),
                                'value' => 'Medium',
                            ],
                            [
                                'label' => Craft::t('formie', 'High'),
                                'value' => 'High',
                            ],
                        ],
                    ],
                ]),
                new IntegrationField([
                    'handle' => 'pipeline_stage_id',
                    'name' => Craft::t('formie', 'Pipeline Stage'),
                    'options' => [
                        'label' => Craft::t('formie', 'Stages'),
                        'options' => $stageOptions,
                    ],
                ]),
                new IntegrationField([
                    'handle' => 'status',
                    'name' => Craft::t('formie', 'Status'),
                    'options' => [
                        'label' => Craft::t('formie', 'Status'),
                        'options' => [
                            [
                                'label' => Craft::t('formie', 'Open'),
                                'value' => 'Open',
                            ],
                            [
                                'label' => Craft::t('formie', 'Won'),
                                'value' => 'Won',
                            ],
                            [
                                'label' => Craft::t('formie', 'Lost'),
                                'value' => 'Lost',
                            ],
                            [
                                'label' => Craft::t('formie', 'Abandoned'),
                                'value' => 'Abandoned',
                            ],
                        ],
                    ],
                ]),
                new IntegrationField([
                    'handle' => 'win_probability',
                    'name' => Craft::t('formie', 'Win Probability'),
                ]),
            ], $this->_getCustomFields($fields, 'opportunity'));

            $taskFields = array_merge([
                new IntegrationField([
                    'handle' => 'name',
                    'name' => Craft::t('formie', 'Name'),
                    'required' => true,
                ]),
                new IntegrationField([
                    'handle' => 'related_resource',
                    'name' => Craft::t('formie', 'Related Resource'),
                ]),
                new IntegrationField([
                    'handle' => 'assignee_id',
                    'name' => Craft::t('formie', 'Assignee'),
                ]),
                new IntegrationField([
                    'handle' => 'due_date',
                    'name' => Craft::t('formie', 'Due Date'),
                ]),
                new IntegrationField([
                    'handle' => 'reminder_date',
                    'name' => Craft::t('formie', 'Reminder Date'),
                ]),
                new IntegrationField([
                    'handle' => 'priority',
                    'name' => Craft::t('formie', 'Priority'),
                    'options' => [
                        'label' => Craft::t('formie', 'Priority'),
                        'options' => [
                            [
                                'label' => Craft::t('formie', 'None'),
                                'value' => 'None',
                            ],
                            [
                                'label' => Craft::t('formie', 'High'),
                                'value' => 'High',
                            ],
                        ],
                    ],
                ]),
                new IntegrationField([
                    'handle' => 'status',
                    'name' => Craft::t('formie', 'Status'),
                    'options' => [
                        'label' => Craft::t('formie', 'Status'),
                        'options' => [
                            [
                                'label' => Craft::t('formie', 'Open'),
                                'value' => 'Open',
                            ],
                            [
                                'label' => Craft::t('formie', 'Completed'),
                                'value' => 'Completed',
                            ],
                        ],
                    ],
                ]),
                new IntegrationField([
                    'handle' => 'details',
                    'name' => Craft::t('formie', 'Details'),
                ]),
            ], $this->_getCustomFields($fields, 'task'));

            $settings = [
                'people' => $peopleFields,
                'lead' => $leadFields,
                'opportunity' => $opportunityFields,
                'task' => $taskFields,
            ];
        } catch (Throwable $e) {
            Integration::apiError($this, $e);
        }

        return new IntegrationFormSettings($settings);
    }

    public function sendPayload(Submission $submission): bool
    {
        try {
            $peopleValues = $this->getFieldMappingValues($submission, $this->peopleFieldMapping, 'people');
            $leadValues = $this->getFieldMappingValues($submission, $this->leadFieldMapping, 'lead');
            $opportunityValues = $this->getFieldMappingValues($submission, $this->opportunityFieldMapping, 'opportunity');
            $taskValues = $this->getFieldMappingValues($submission, $this->taskFieldMapping, 'task');

            // Directly modify the field values first
            $peopleFields = $this->_prepCustomFields($peopleValues);
            $leadFields = $this->_prepCustomFields($leadValues);
            $opportunityFields = $this->_prepCustomFields($opportunityValues);
            $taskFields = $this->_prepCustomFields($taskValues);

            $peopleId = null;
            $leadId = null;
            $opportunityId = null;
            $taskId = null;

            if ($this->mapToPeople) {
                $peoplePayload = array_merge($peopleValues, [
                    'custom_fields' => $peopleFields,
                ]);

                // Try and find the person first, doesn't handle adding existing ones well
                // `people/fetch_by_email` no longer seems to work...
                $response = $this->request('POST', 'people/search', [
                    'json' => [
                        'emails' => [$peoplePayload['emails'][0]['email'] ?? ''],
                    ],
                ]);

                $existingPersonId = $response[0]['id'] ?? null;

                // Update or create
                if ($existingPersonId) {
                    $response = $this->deliverPayload($submission, "people/{$existingPersonId}", $peoplePayload, 'PUT');
                } else {
                    $response = $this->deliverPayload($submission, 'people', $peoplePayload);
                }

                if ($response === false) {
                    return true;
                }

                $peopleId = $response['id'] ?? '';

                if (!$peopleId) {
                    Integration::error($this, Craft::t('formie', 'Missing return “peopleId” {response}. Sent payload {payload}', [
                        'response' => Json::encode($response),
                        'payload' => Json::encode($peoplePayload),
                    ]), true);

                    return false;
                }
            }

            if ($this->mapToLead) {
                $leadPayload = array_merge($leadValues, [
                    'custom_fields' => $peopleFields,
                ]);

                $response = $this->deliverPayload($submission, 'leads', $leadPayload);

                if ($response === false) {
                    return true;
                }

                $leadId = $response['id'] ?? '';

                if (!$peopleId) {
                    Integration::error($this, Craft::t('formie', 'Missing return “leadId” {response}. Sent payload {payload}', [
                        'response' => Json::encode($response),
                        'payload' => Json::encode($leadPayload),
                    ]), true);

                    return false;
                }
            }

            if ($this->mapToOpportunity) {
                $opportunityPayload = array_merge($opportunityValues, [
                    'custom_fields' => $opportunityFields,
                ]);

                if ($peopleId) {
                    $opportunityPayload['primary_contact_id'] = $peopleId;
                }

                $response = $this->deliverPayload($submission, 'opportunities', $opportunityPayload);

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

            if ($this->mapToTask) {
                $taskPayload = array_merge($taskValues, [
                    'custom_fields' => $taskFields,
                ]);

                $response = $this->deliverPayload($submission, 'tasks', $taskPayload);

                if ($response === false) {
                    return true;
                }

                $taskId = $response['id'] ?? '';

                if (!$taskId) {
                    Integration::error($this, Craft::t('formie', 'Missing return “taskId” {response}. Sent payload {payload}', [
                        'response' => Json::encode($response),
                        'payload' => Json::encode($taskPayload),
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

    public function fetchConnection(): bool
    {
        try {
            $response = $this->request('GET', 'account');
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

        return $this->_client = Craft::createGuzzleClient([
            'base_uri' => 'https://api.copper.com/developer_api/v1/',
            'headers' => [
                'X-PW-AccessToken' => App::parseEnv($this->apiKey),
                'X-PW-Application' => 'developer_api',
                'X-PW-UserEmail' => App::parseEnv($this->apiEmail),
                'Content-Type' => 'application/json',
            ],
        ]);
    }


    // Private Methods
    // =========================================================================

    private function _convertFieldType($fieldType)
    {
        $fieldTypes = [
            'Float' => IntegrationField::TYPE_FLOAT,
            'Checkbox' => IntegrationField::TYPE_BOOLEAN,
            'Date' => IntegrationField::TYPE_DATE,
            'MultiSelect' => IntegrationField::TYPE_ARRAY,
        ];

        return $fieldTypes[$fieldType] ?? IntegrationField::TYPE_STRING;
    }

    private function _getCustomFields($fields, $namespace): array
    {
        $customFields = [];

        foreach ($fields as $key => $field) {
            if (!in_array($namespace, $field['available_on'])) {
                continue;
            }

            $customFields[] = new IntegrationField([
                'handle' => 'custom:' . $field['id'],
                'name' => $field['name'],
                'type' => $this->_convertFieldType($field['data_type']),
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

                $customFields[] = [
                    'custom_field_definition_id' => str_replace('custom:', '', $key),
                    'value' => $value,
                ];
            }

            if ($key === 'email') {
                $fields[$key] = [
                    'category' => 'work',
                    'email' => $value,
                ];
            }

            if ($key === 'emails') {
                $fields[$key] = [
                    [
                        'category' => 'work',
                        'email' => $value,
                    ],
                ];
            }

            if ($key === 'phone_numbers') {
                $fields[$key] = [
                    [
                        'category' => 'work',
                        'number' => $value,
                    ],
                ];
            }

            if ($key === 'socials') {
                $fields[$key] = [
                    [
                        'category' => '',
                        'url' => $value,
                    ],
                ];
            }

            if ($key === 'websites') {
                $fields[$key] = [
                    [
                        'category' => 'work',
                        'url' => $value,
                    ],
                ];
            }
        }

        return $customFields;
    }
}