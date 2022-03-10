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


class Capsule extends Crm
{
    // Static Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
    public static function displayName(): string
    {
        return Craft::t('formie', 'Capsule');
    }
    

    // Properties
    // =========================================================================

    public ?string $apiKey = null;
    public bool $mapToPeople = false;
    public bool $mapToOpportunity = false;
    public bool $mapToTask = false;
    public ?array $peopleFieldMapping = null;
    public ?array $opportunityFieldMapping = null;
    public ?array $taskFieldMapping = null;


    // Public Methods
    // =========================================================================

    public function getDescription(): string
    {
        return Craft::t('formie', 'Manage your Capsule customers by providing important information on their conversion on your site.');
    }

    /**
     * @inheritDoc
     */
    public function defineRules(): array
    {
        $rules = parent::defineRules();

        $rules[] = [['apiKey'], 'required'];

        $people = $this->getFormSettingValue('people');
        $opportunity = $this->getFormSettingValue('opportunity');
        $task = $this->getFormSettingValue('task');

        // Validate the following when saving form settings
        $rules[] = [
            ['peopleFieldMapping'], 'validateFieldMapping', 'params' => $people, 'when' => function($model) {
                return $model->enabled && $model->mapToPeople;
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
            // Get People fields
            $fields = $this->request('GET', 'parties/fields/definitions')['definitions'] ?? [];

            $peopleFields = array_merge([
                new IntegrationField([
                    'handle' => 'firstName',
                    'name' => Craft::t('formie', 'First Name'),
                ]),
                new IntegrationField([
                    'handle' => 'lastName',
                    'name' => Craft::t('formie', 'Last Name'),
                ]),
                new IntegrationField([
                    'handle' => 'title',
                    'name' => Craft::t('formie', 'Title'),
                ]),
                new IntegrationField([
                    'handle' => 'jobTitle',
                    'name' => Craft::t('formie', 'Job Title'),
                ]),
                new IntegrationField([
                    'handle' => 'organisation',
                    'name' => Craft::t('formie', 'Organisation'),
                ]),
                new IntegrationField([
                    'handle' => 'about',
                    'name' => Craft::t('formie', 'About'),
                ]),
                new IntegrationField([
                    'handle' => 'phoneNumbers',
                    'name' => Craft::t('formie', 'Phone Number'),
                ]),
                new IntegrationField([
                    'handle' => 'websites',
                    'name' => Craft::t('formie', 'Websites'),
                ]),
                new IntegrationField([
                    'handle' => 'emailAddresses',
                    'name' => Craft::t('formie', 'Email Addresses'),
                ]),
            ], $this->_getCustomFields($fields));

            // Get Opportunity fields
            $milestoneOptions = [];
            $lostReasonOptions = [];

            $milestones = $this->request('GET', 'milestones')['milestones'] ?? [];
            $lostReasons = $this->request('GET', 'lostreasons')['lostReasons'] ?? [];

            foreach ($lostReasons as $lostReason) {
                $lostReasonOptions[] = [
                    'label' => $lostReason['name'],
                    'value' => $lostReason['id'],
                ];
            }

            foreach ($milestones as $milestone) {
                $milestoneOptions[] = [
                    'label' => $milestone['name'],
                    'value' => $milestone['id'],
                ];
            }

            $fields = $this->request('GET', 'opportunities/fields/definitions')['definitions'] ?? [];

            $opportunityFields = array_merge([
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
                    'handle' => 'lostReason',
                    'name' => Craft::t('formie', 'lostReason'),
                    'options' => [
                        'label' => Craft::t('formie', 'Lost Reason'),
                        'options' => $lostReasonOptions,
                    ],
                ]),
                new IntegrationField([
                    'handle' => 'milestone',
                    'name' => Craft::t('formie', 'Milestone'),
                    'required' => true,
                    'options' => [
                        'label' => Craft::t('formie', 'Milestone'),
                        'options' => $milestoneOptions,
                    ],
                ]),
                new IntegrationField([
                    'handle' => 'value',
                    'name' => Craft::t('formie', 'Value'),
                ]),
                new IntegrationField([
                    'handle' => 'expectedCloseOn',
                    'name' => Craft::t('formie', 'Expected Close On'),
                ]),
                new IntegrationField([
                    'handle' => 'probability',
                    'name' => Craft::t('formie', 'Probability'),
                ]),
                new IntegrationField([
                    'handle' => 'durationBasis',
                    'name' => Craft::t('formie', 'Duration Basis'),
                    'options' => [
                        'label' => Craft::t('formie', 'Status'),
                        'options' => [
                            [
                                'label' => Craft::t('formie', 'Fixed'),
                                'value' => 'FIXED',
                            ],
                            [
                                'label' => Craft::t('formie', 'Hour'),
                                'value' => 'HOUR',
                            ],
                            [
                                'label' => Craft::t('formie', 'Day'),
                                'value' => 'DAY',
                            ],
                            [
                                'label' => Craft::t('formie', 'Week'),
                                'value' => 'WEEK',
                            ],
                            [
                                'label' => Craft::t('formie', 'Month'),
                                'value' => 'MONTH',
                            ],
                            [
                                'label' => Craft::t('formie', 'Quarter'),
                                'value' => 'QUARTER',
                            ],
                            [
                                'label' => Craft::t('formie', 'Year'),
                                'value' => 'YEAR',
                            ],
                        ],
                    ],
                ]),
                new IntegrationField([
                    'handle' => 'duration',
                    'name' => Craft::t('formie', 'Duration'),
                ]),
                new IntegrationField([
                    'handle' => 'closedOn',
                    'name' => Craft::t('formie', 'Closed On'),
                ]),
            ], $this->_getCustomFields($fields));

            // Get Task fields
            $categoryOptions = [];

            $categories = $this->request('GET', 'categories')['categories'] ?? [];

            foreach ($categories as $category) {
                $categoryOptions[] = [
                    'label' => $category['name'],
                    'value' => $category['id'],
                ];
            }

            $taskFields = [
                new IntegrationField([
                    'handle' => 'description',
                    'name' => Craft::t('formie', 'Description'),
                    'required' => true,
                ]),
                new IntegrationField([
                    'handle' => 'detail',
                    'name' => Craft::t('formie', 'Detail'),
                ]),
                new IntegrationField([
                    'handle' => 'categories',
                    'name' => Craft::t('formie', 'Categories'),
                    'options' => [
                        'label' => Craft::t('formie', 'Categories'),
                        'options' => $categoryOptions,
                    ],
                ]),
                new IntegrationField([
                    'handle' => 'dueOn',
                    'name' => Craft::t('formie', 'Due On'),
                    'required' => true,
                    'type' => IntegrationField::TYPE_DATE,
                ]),
                new IntegrationField([
                    'handle' => 'dueTime',
                    'name' => Craft::t('formie', 'Due Time'),
                ]),
                new IntegrationField([
                    'handle' => 'status',
                    'name' => Craft::t('formie', 'Status'),
                    'options' => [
                        'label' => Craft::t('formie', 'Status'),
                        'options' => [
                            [
                                'label' => Craft::t('formie', 'Open'),
                                'value' => 'OPEN',
                            ],
                            [
                                'label' => Craft::t('formie', 'Completed'),
                                'value' => 'COMPLETED',
                            ],
                            [
                                'label' => Craft::t('formie', 'Pending'),
                                'value' => 'PENDING',
                            ],
                        ],
                    ],
                ]),
            ];

            $settings = [
                'people' => $peopleFields,
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
            $opportunityValues = $this->getFieldMappingValues($submission, $this->opportunityFieldMapping, 'opportunity');
            $taskValues = $this->getFieldMappingValues($submission, $this->taskFieldMapping, 'task');

            // Directly modify the field values first
            $peopleFields = $this->_prepCustomFields($peopleValues);
            $opportunityFields = $this->_prepCustomFields($opportunityValues);
            $taskFields = $this->_prepCustomFields($taskValues);

            $peopleId = null;
            $opportunityId = null;
            $taskId = null;

            if ($this->mapToPeople) {
                $peoplePayload = [
                    'party' => array_merge($peopleValues, [
                        'type' => 'person',
                        'fields' => $peopleFields,
                    ]),
                ];

                $response = $this->deliverPayload($submission, 'parties', $peoplePayload);

                if ($response === false) {
                    return true;
                }

                $peopleId = $response['party']['id'] ?? '';

                if (!$peopleId) {
                    Integration::error($this, Craft::t('formie', 'Missing return “peopleId” {response}. Sent payload {payload}', [
                        'response' => Json::encode($response),
                        'payload' => Json::encode($peoplePayload),
                    ]), true);

                    return false;
                }
            }

            if ($this->mapToOpportunity) {
                $opportunityPayload = [
                    'opportunity' => array_merge($opportunityValues, [
                        'fields' => $opportunityFields,
                    ]),
                ];

                if ($peopleId) {
                    $opportunityPayload['opportunity']['party'] = ['id' => $peopleId];
                }

                $response = $this->deliverPayload($submission, 'opportunities', $opportunityPayload);

                if ($response === false) {
                    return true;
                }

                $opportunityId = $response['opportunity']['id'] ?? '';

                if (!$opportunityId) {
                    Integration::error($this, Craft::t('formie', 'Missing return “opportunityId” {response}. Sent payload {payload}', [
                        'response' => Json::encode($response),
                        'payload' => Json::encode($opportunityPayload),
                    ]), true);

                    return false;
                }
            }

            if ($this->mapToTask) {
                $taskPayload = [
                    'task' => array_merge($taskValues, [
                        'fields' => $taskFields,
                    ]),
                ];

                if ($peopleId) {
                    $taskPayload['task']['party'] = ['id' => $peopleId];
                }

                $response = $this->deliverPayload($submission, 'tasks', $taskPayload);

                if ($response === false) {
                    return true;
                }

                $taskId = $response['task']['id'] ?? '';

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
            $response = $this->request('GET', 'users');
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
            'base_uri' => 'https://api.capsulecrm.com/api/v2/',
            'headers' => [
                'Authorization' => 'Bearer ' . App::parseEnv($this->apiKey),
            ],
        ]);
    }


    // Private Methods
    // =========================================================================

    private function _convertFieldType($fieldType)
    {
        $fieldTypes = [
            'number' => IntegrationField::TYPE_NUMBER,
            'boolean' => IntegrationField::TYPE_BOOLEAN,
            'date' => IntegrationField::TYPE_DATE,
            'list' => IntegrationField::TYPE_ARRAY,
        ];

        return $fieldTypes[$fieldType] ?? IntegrationField::TYPE_STRING;
    }

    private function _getCustomFields($fields): array
    {
        $customFields = [];

        foreach ($fields as $key => $field) {
            $customFields[] = new IntegrationField([
                'handle' => 'custom:' . $field['id'],
                'name' => $field['name'],
                'type' => $this->_convertFieldType($field['type']),
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
                    'definition' => ['id' => (int)str_replace('custom:', '', $key)],
                    'value' => $value,
                ];
            }

            if ($key === 'emailAddresses') {
                $fields[$key] = [
                    [
                        'type' => 'Work',
                        'address' => $value,
                    ],
                ];
            }

            if ($key === 'phoneNumbers') {
                $fields[$key] = [
                    [
                        'type' => 'Work',
                        'number' => $value,
                    ],
                ];
            }

            if ($key === 'websites') {
                $fields[$key] = [
                    [
                        'service' => 'URL',
                        'address' => $value,
                    ],
                ];
            }

            if ($key === 'milestone') {
                $fields[$key] = [
                    'id' => (int)$value,
                ];
            }
        }

        return $customFields;
    }
}