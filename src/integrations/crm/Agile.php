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

class Agile extends Crm
{
    // Static Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
    public static function displayName(): string
    {
        return Craft::t('formie', 'Agile CRM');
    }
    

    // Properties
    // =========================================================================

    public ?string $apiKey = null;
    public ?string $apiEmail = null;
    public ?string $apiDomain = null;
    public bool $mapToContact = false;
    public bool $mapToDeal = false;
    public bool $mapToTask = false;
    public ?array $contactFieldMapping = null;
    public ?array $dealFieldMapping = null;
    public ?array $taskFieldMapping = null;


    // Public Methods
    // =========================================================================

    public function getDescription(): string
    {
        return Craft::t('formie', 'Manage your Agile customers by providing important information on their conversion on your site.');
    }

    /**
     * @inheritDoc
     */
    public function defineRules(): array
    {
        $rules = parent::defineRules();

        $rules[] = [['apiKey', 'apiEmail', 'apiDomain'], 'required'];

        $contact = $this->getFormSettingValue('contact');
        $deal = $this->getFormSettingValue('deal');
        $task = $this->getFormSettingValue('task');

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
            // Get Contact fields
            $contacts = $this->request('GET', 'contacts');
            $fields = $contacts[0]['properties'] ?? [];

            $contactFields = array_merge([
                new IntegrationField([
                    'handle' => 'type',
                    'name' => Craft::t('formie', 'Type'),
                    'options' => [
                        'label' => Craft::t('formie', 'Types'),
                        'options' => [
                            [
                                'label' => Craft::t('formie', 'Person'),
                                'value' => 'PERSON',
                            ],
                            [
                                'label' => Craft::t('formie', 'Company'),
                                'value' => 'COMPANY',
                            ],
                        ],
                    ],
                ]),
                new IntegrationField([
                    'handle' => 'lead_score',
                    'name' => Craft::t('formie', 'Lead Score'),
                ]),
                new IntegrationField([
                    'handle' => 'contact_company_id',
                    'name' => Craft::t('formie', 'Contact Company ID'),
                ]),
                new IntegrationField([
                    'handle' => 'star_value',
                    'name' => Craft::t('formie', 'Star Value'),
                ]),
                new IntegrationField([
                    'handle' => 'tags',
                    'name' => Craft::t('formie', 'Tags'),
                    'type' => IntegrationField::TYPE_ARRAY,
                ]),
                new IntegrationField([
                    'handle' => 'first_name',
                    'name' => Craft::t('formie', 'First Name'),
                    'required' => true,
                ]),
                new IntegrationField([
                    'handle' => 'last_name',
                    'name' => Craft::t('formie', 'Last Name'),
                ]),
                new IntegrationField([
                    'handle' => 'company',
                    'name' => Craft::t('formie', 'Company'),
                ]),
                new IntegrationField([
                    'handle' => 'title',
                    'name' => Craft::t('formie', 'Title'),
                ]),
                new IntegrationField([
                    'handle' => 'email',
                    'name' => Craft::t('formie', 'Email'),
                ]),
                new IntegrationField([
                    'handle' => 'phone',
                    'name' => Craft::t('formie', 'Phone'),
                ]),
                new IntegrationField([
                    'handle' => 'website',
                    'name' => Craft::t('formie', 'Website'),
                ]),
            ], $this->_getCustomFields($fields));

            // Get Deal fields
            $deals = $this->request('GET', 'opportunity');
            $fields = $deals[0]['properties'] ?? [];

            $milestoneOptions = [];
            $pipelineOptions = [];
            $pipelines = $this->request('GET', 'milestone/pipelines');

            foreach ($pipelines as $pipeline) {
                $pipelineOptions[] = [
                    'label' => $pipeline['name'],
                    'value' => $pipeline['id'],
                ];

                foreach (explode(',', $pipeline['milestones']) as $milestone) {
                    $milestoneOptions[] = [
                        'label' => $milestone,
                        'value' => $milestone,
                    ];
                }
            }

            $dealFields = array_merge([
                new IntegrationField([
                    'handle' => 'name',
                    'name' => Craft::t('formie', 'Name'),
                    'required' => true,
                ]),
                new IntegrationField([
                    'handle' => 'description',
                    'name' => Craft::t('formie', 'Description'),
                ]),
                new IntegrationField([
                    'handle' => 'expected_value',
                    'name' => Craft::t('formie', 'Expected Value'),
                    'required' => true,
                ]),
                new IntegrationField([
                    'handle' => 'pipeline_id',
                    'name' => Craft::t('formie', 'Pipeline ID'),
                    'required' => true,
                    'options' => [
                        'label' => Craft::t('formie', 'Pipelines'),
                        'options' => $pipelineOptions,
                    ],
                ]),
                new IntegrationField([
                    'handle' => 'milestone',
                    'name' => Craft::t('formie', 'Milestone'),
                    'required' => true,
                    'options' => [
                        'label' => Craft::t('formie', 'Milestones'),
                        'options' => $milestoneOptions,
                    ],
                ]),
                new IntegrationField([
                    'handle' => 'probability',
                    'name' => Craft::t('formie', 'Probability'),
                ]),
                new IntegrationField([
                    'handle' => 'close_date',
                    'name' => Craft::t('formie', 'Close Date'),
                    'required' => true,
                ]),
                new IntegrationField([
                    'handle' => 'owner_id',
                    'name' => Craft::t('formie', 'Owner ID'),
                ]),
            ], $this->_getCustomFields($fields));

            $taskFields = array_merge([
                new IntegrationField([
                    'handle' => 'type',
                    'name' => Craft::t('formie', 'Type'),
                    'options' => [
                        'label' => Craft::t('formie', 'Types'),
                        'options' => [
                            [
                                'label' => Craft::t('formie', 'Call'),
                                'value' => 'CALL',
                            ],
                            [
                                'label' => Craft::t('formie', 'Email'),
                                'value' => 'EMAIL',
                            ],
                            [
                                'label' => Craft::t('formie', 'Follow Up'),
                                'value' => 'FOLLOW_UP',
                            ],
                            [
                                'label' => Craft::t('formie', 'Meeting'),
                                'value' => 'MEETING',
                            ],
                            [
                                'label' => Craft::t('formie', 'Milestone'),
                                'value' => 'MILESTONE',
                            ],
                            [
                                'label' => Craft::t('formie', 'Send'),
                                'value' => 'SEND',
                            ],
                            [
                                'label' => Craft::t('formie', 'Tweet'),
                                'value' => 'TWEET',
                            ],
                            [
                                'label' => Craft::t('formie', 'Other'),
                                'value' => 'OTHER',
                            ],
                        ],
                    ],
                    'required' => true,
                ]),
                new IntegrationField([
                    'handle' => 'priority_type',
                    'name' => Craft::t('formie', 'Priority Type'),
                    'options' => [
                        'label' => Craft::t('formie', 'Types'),
                        'options' => [
                            [
                                'label' => Craft::t('formie', 'High'),
                                'value' => 'HIGH',
                            ],
                            [
                                'label' => Craft::t('formie', 'Normal'),
                                'value' => 'NORMAL',
                            ],
                            [
                                'label' => Craft::t('formie', 'Low'),
                                'value' => 'LOW',
                            ],
                        ],
                    ],
                    'required' => true,
                ]),
                new IntegrationField([
                    'handle' => 'due',
                    'name' => Craft::t('formie', 'Due'),
                    'required' => true,
                ]),
                new IntegrationField([
                    'handle' => 'is_complete',
                    'name' => Craft::t('formie', 'Is Complete'),
                    'type' => IntegrationField::TYPE_BOOLEAN,
                ]),
                new IntegrationField([
                    'handle' => 'subject',
                    'name' => Craft::t('formie', 'Subject'),
                    'required' => true,
                ]),
                new IntegrationField([
                    'handle' => 'progress',
                    'name' => Craft::t('formie', 'Progress'),
                ]),
                new IntegrationField([
                    'handle' => 'status',
                    'name' => Craft::t('formie', 'Status'),
                    'options' => [
                        'label' => Craft::t('formie', 'Types'),
                        'options' => [
                            [
                                'label' => Craft::t('formie', 'Yet To Start'),
                                'value' => 'YET_TO_START',
                            ],
                            [
                                'label' => Craft::t('formie', 'In Progress'),
                                'value' => 'IN_PROGRESS',
                            ],
                            [
                                'label' => Craft::t('formie', 'Completed'),
                                'value' => 'COMPLETED',
                            ],
                        ],
                    ],
                ]),
            ], $this->_getCustomFields($fields));

            $settings = [
                'contact' => $contactFields,
                'deal' => $dealFields,
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
            $contactValues = $this->getFieldMappingValues($submission, $this->contactFieldMapping, 'contact');
            $dealValues = $this->getFieldMappingValues($submission, $this->dealFieldMapping, 'deal');
            $taskValues = $this->getFieldMappingValues($submission, $this->taskFieldMapping, 'task');

            // Save for later, before `_prepCustomFields()` modifies this.
            $email = $contactValues['email'] ?? '';

            // Directly modify the field values first
            $contactFields = $this->_prepCustomFields($contactValues, ['first_name', 'last_name', 'company', 'title']);
            $dealFields = $this->_prepCustomFields($dealValues);
            $taskFields = $this->_prepCustomFields($taskValues);

            $contactId = null;
            $dealId = null;
            $taskId = null;

            if ($this->mapToContact) {
                $contactPayload = array_merge($contactValues, [
                    'properties' => $contactFields,
                ]);

                // Check to see if this contact already exists, and update
                $response = $this->request('GET', "contacts/search/email/${email}");
                $existingContact = $response['id'] ?? '';

                if ($existingContact) {
                    $contactPayload = array_merge(['id' => $existingContact], $contactPayload);

                    // We have to update the contact properties, and a few other items in separate calls - gah.
                    $response = $this->deliverPayload($submission, "contacts/edit-properties", $contactPayload, 'PUT');

                    $tags = $contactPayload['tags'] ?? [];

                    if ($tags) {
                        $tagsPayload = ['id' => $existingContact, 'tags' => $tags];

                        $response = $this->deliverPayload($submission, 'contacts/edit/tags', $tagsPayload, 'PUT');
                    }
                } else {
                    $response = $this->deliverPayload($submission, 'contacts', $contactPayload);
                }

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

            if ($this->mapToDeal) {
                $dealPayload = array_merge($dealValues, [
                    'custom_data' => $dealFields,
                ]);

                if ($contactId) {
                    $dealPayload['contact_ids'] = [$contactId];
                }

                $response = $this->deliverPayload($submission, 'opportunity', $dealPayload);

                if ($response === false) {
                    return true;
                }

                $dealId = $response['id'] ?? '';

                if (!$dealId) {
                    Integration::error($this, Craft::t('formie', 'Missing return “dealId” {response}. Sent payload {payload}', [
                        'response' => Json::encode($response),
                        'payload' => Json::encode($dealPayload),
                    ]), true);

                    return false;
                }
            }

            if ($this->mapToTask) {
                $taskPayload = array_merge($taskValues, [
                    'custom_data' => $taskFields,
                ]);

                if ($contactId) {
                    $taskPayload['contact'] = [$contactId];
                }

                if ($dealId) {
                    $taskPayload['deal_ids'] = [$dealId];
                }

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
            $this->request('GET', 'contacts');
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
            'base_uri' => "$url/dev/api/",
            'auth' => [App::parseEnv($this->apiEmail), App::parseEnv($this->apiKey)],
            'headers' => [
                'Accept' => 'application/json',
            ],
        ]);
    }


    // Private Methods
    // =========================================================================

    private function _convertFieldType($fieldType): string
    {
        return IntegrationField::TYPE_STRING;
    }

    private function _getCustomFields($fields, $excludeNames = []): array
    {
        $customFields = [];

        foreach ($fields as $field) {
            if ($field['type'] !== 'CUSTOM') {
                continue;
            }

            $customFields[] = new IntegrationField([
                'handle' => 'custom:' . $field['name'],
                'name' => $field['name'],
                'type' => $this->_convertFieldType($field['type']),
            ]);
        }

        return $customFields;
    }

    private function _prepCustomFields(&$fields, $extras = []): array
    {
        $customFields = [];

        foreach ($fields as $key => $value) {
            if (StringHelper::startsWith($key, 'custom:')) {
                ArrayHelper::remove($fields, $key);

                $customFields[] = [
                    'type' => 'CUSTOM',
                    'name' => str_replace('custom:', '', $key),
                    'value' => $value,
                ];
            }

            if (in_array($key, $extras)) {
                ArrayHelper::remove($fields, $key);

                $customFields[] = [
                    'type' => 'SYSTEM',
                    'name' => $key,
                    'value' => $value,
                ];
            }

            if ($key === 'email') {
                ArrayHelper::remove($fields, $key);

                $customFields[] = [
                    'subtype' => 'work',
                    'name' => $key,
                    'value' => $value,
                ];
            }

            if ($key === 'phone') {
                ArrayHelper::remove($fields, $key);

                $customFields[] = [
                    'subtype' => 'work',
                    'name' => $key,
                    'value' => $value,
                ];
            }

            if ($key === 'website') {
                ArrayHelper::remove($fields, $key);

                $customFields[] = [
                    'subtype' => 'URL',
                    'name' => $key,
                    'value' => $value,
                ];
            }
        }

        return $customFields;
    }
}
