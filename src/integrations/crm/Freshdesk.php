<?php
namespace verbb\formie\integrations\crm;

use verbb\formie\base\Crm;
use verbb\formie\base\Integration;
use verbb\formie\elements\Submission;
use verbb\formie\events\ModifyFieldIntegrationValuesEvent;
use verbb\formie\models\IntegrationField;
use verbb\formie\models\IntegrationFormSettings;

use Craft;
use craft\elements\db\AssetQuery;
use craft\helpers\App;
use craft\helpers\ArrayHelper;
use craft\helpers\Json;
use craft\helpers\StringHelper;

use GuzzleHttp\Psr7\Utils;
use GuzzleHttp\Client;

use Throwable;

class Freshdesk extends Crm
{
    // Static Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
    public static function displayName(): string
    {
        return Craft::t('formie', 'Freshdesk');
    }
    

    // Properties
    // =========================================================================

    public ?string $apiKey = null;
    public ?string $apiDomain = null;
    public bool $mapToContact = false;
    public bool $mapToTicket = false;
    public ?array $contactFieldMapping = null;
    public ?array $ticketFieldMapping = null;
    private ?array $_attachments = null;


    // Public Methods
    // =========================================================================

    public function getDescription(): string
    {
        return Craft::t('formie', 'Manage your Freshdesk customers by providing important information on their conversion on your site.');
    }

    /**
     * @inheritDoc
     */
    public function defineRules(): array
    {
        $rules = parent::defineRules();

        $rules[] = [['apiKey', 'apiDomain'], 'required'];

        $contact = $this->getFormSettingValue('contact');
        $ticket = $this->getFormSettingValue('ticket');

        // Validate the following when saving form settings
        $rules[] = [
            ['contactFieldMapping'], 'validateFieldMapping', 'params' => $contact, 'when' => function($model) {
                return $model->enabled && $model->mapToContact;
            }, 'on' => [Integration::SCENARIO_FORM],
        ];

        $rules[] = [
            ['ticketFieldMapping'], 'validateFieldMapping', 'params' => $ticket, 'when' => function($model) {
                return $model->enabled && $model->mapToTicket;
            }, 'on' => [Integration::SCENARIO_FORM],
        ];

        return $rules;
    }

    public function fetchFormSettings(): IntegrationFormSettings
    {
        $settings = [];

        try {
            // Get Contact fields
            $fields = $this->request('GET', 'contact_fields');

            $contactFields = array_merge([
                new IntegrationField([
                    'handle' => 'name',
                    'name' => Craft::t('formie', 'Name'),
                    'required' => true,
                    'type' => IntegrationField::TYPE_STRING,
                ]),
                new IntegrationField([
                    'handle' => 'email',
                    'name' => Craft::t('formie', 'Email'),
                    'required' => true,
                    'type' => IntegrationField::TYPE_STRING,
                ]),
                new IntegrationField([
                    'handle' => 'phone',
                    'name' => Craft::t('formie', 'Phone'),
                    'type' => IntegrationField::TYPE_STRING,
                ]),
                new IntegrationField([
                    'handle' => 'mobile',
                    'name' => Craft::t('formie', 'Mobile'),
                    'type' => IntegrationField::TYPE_STRING,
                ]),
                new IntegrationField([
                    'handle' => 'twitter_id',
                    'name' => Craft::t('formie', 'Twitter ID'),
                    'type' => IntegrationField::TYPE_STRING,
                ]),
                new IntegrationField([
                    'handle' => 'company_id',
                    'name' => Craft::t('formie', 'Company ID'),
                    'type' => IntegrationField::TYPE_NUMBER,
                ]),
                new IntegrationField([
                    'handle' => 'description',
                    'name' => Craft::t('formie', 'Description'),
                    'type' => IntegrationField::TYPE_STRING,
                ]),
                new IntegrationField([
                    'handle' => 'job_title',
                    'name' => Craft::t('formie', 'Job Title'),
                    'type' => IntegrationField::TYPE_STRING,
                ]),
                new IntegrationField([
                    'handle' => 'time_zone',
                    'name' => Craft::t('formie', 'Timezone'),
                    'type' => IntegrationField::TYPE_STRING,
                ]),
            ], $this->_getCustomFields($fields));

            // Get Ticket fields
            $fields = $this->request('GET', 'ticket_fields');

            $ticketFields = array_merge([
                new IntegrationField([
                    'handle' => 'name',
                    'name' => Craft::t('formie', 'Name'),
                    'required' => true,
                    'type' => IntegrationField::TYPE_STRING,
                ]),
                new IntegrationField([
                    'handle' => 'email',
                    'name' => Craft::t('formie', 'Email'),
                    'required' => true,
                    'type' => IntegrationField::TYPE_STRING,
                ]),
                new IntegrationField([
                    'handle' => 'phone',
                    'name' => Craft::t('formie', 'Phone'),
                    'type' => IntegrationField::TYPE_STRING,
                ]),
                new IntegrationField([
                    'handle' => 'unique_external_id',
                    'name' => Craft::t('formie', 'Unique External ID'),
                    'type' => IntegrationField::TYPE_STRING,
                ]),
                new IntegrationField([
                    'handle' => 'subject',
                    'name' => Craft::t('formie', 'Subject'),
                    'required' => true,
                    'type' => IntegrationField::TYPE_STRING,
                ]),
                new IntegrationField([
                    'handle' => 'type',
                    'name' => Craft::t('formie', 'Type'),
                    'type' => IntegrationField::TYPE_STRING,
                ]),
                new IntegrationField([
                    'handle' => 'status',
                    'name' => Craft::t('formie', 'Status'),
                    'required' => true,
                    'options' => [
                        'label' => Craft::t('formie', 'Source'),
                        'options' => [
                            [
                                'label' => Craft::t('formie', 'Open'),
                                'value' => 2,
                            ],
                            [
                                'label' => Craft::t('formie', 'Pending'),
                                'value' => 3,
                            ],
                            [
                                'label' => Craft::t('formie', 'Resolved'),
                                'value' => 4,
                            ],
                            [
                                'label' => Craft::t('formie', 'Closed'),
                                'value' => 5,
                            ],
                        ],
                    ],
                    'type' => IntegrationField::TYPE_NUMBER,
                ]),
                new IntegrationField([
                    'handle' => 'priority',
                    'name' => Craft::t('formie', 'Priority'),
                    'required' => true,
                    'options' => [
                        'label' => Craft::t('formie', 'Source'),
                        'options' => [
                            [
                                'label' => Craft::t('formie', 'Low'),
                                'value' => 1,
                            ],
                            [
                                'label' => Craft::t('formie', 'Medium'),
                                'value' => 2,
                            ],
                            [
                                'label' => Craft::t('formie', 'High'),
                                'value' => 3,
                            ],
                            [
                                'label' => Craft::t('formie', 'Urgent'),
                                'value' => 4,
                            ],
                        ],
                    ],
                    'type' => IntegrationField::TYPE_NUMBER,
                ]),
                new IntegrationField([
                    'handle' => 'description',
                    'name' => Craft::t('formie', 'Description'),
                    'required' => true,
                    'type' => IntegrationField::TYPE_STRING,
                ]),
                new IntegrationField([
                    'handle' => 'responder_id',
                    'name' => Craft::t('formie', 'Responder ID'),
                    'type' => IntegrationField::TYPE_NUMBER,
                ]),
                new IntegrationField([
                    'handle' => 'attachments',
                    'name' => Craft::t('formie', 'Attachments'),
                    'type' => IntegrationField::TYPE_ARRAY,
                ]),
                new IntegrationField([
                    'handle' => 'cc_emails',
                    'name' => Craft::t('formie', 'CC Emails'),
                    'type' => IntegrationField::TYPE_ARRAY,
                ]),
                new IntegrationField([
                    'handle' => 'due_by',
                    'name' => Craft::t('formie', 'Due By'),
                    'type' => IntegrationField::TYPE_DATETIME,
                ]),
                new IntegrationField([
                    'handle' => 'email_config_id',
                    'name' => Craft::t('formie', 'Email Config ID'),
                    'type' => IntegrationField::TYPE_NUMBER,
                ]),
                new IntegrationField([
                    'handle' => 'fr_due_by',
                    'name' => Craft::t('formie', 'First Response Due By'),
                    'type' => IntegrationField::TYPE_DATETIME,
                ]),
                new IntegrationField([
                    'handle' => 'group_id',
                    'name' => Craft::t('formie', 'Group ID'),
                    'type' => IntegrationField::TYPE_NUMBER,
                ]),
                new IntegrationField([
                    'handle' => 'product_id',
                    'name' => Craft::t('formie', 'Product ID'),
                    'type' => IntegrationField::TYPE_NUMBER,
                ]),
                new IntegrationField([
                    'handle' => 'source',
                    'name' => Craft::t('formie', 'Source'),
                    'required' => true,
                    'options' => [
                        'label' => Craft::t('formie', 'Source'),
                        'options' => [
                            [
                                'label' => Craft::t('formie', 'Email'),
                                'value' => 1,
                            ],
                            [
                                'label' => Craft::t('formie', 'Portal'),
                                'value' => 2,
                            ],
                            [
                                'label' => Craft::t('formie', 'Phone'),
                                'value' => 3,
                            ],
                            [
                                'label' => Craft::t('formie', 'Chat'),
                                'value' => 7,
                            ],
                            [
                                'label' => Craft::t('formie', 'Feedback Widget'),
                                'value' => 9,
                            ],
                            [
                                'label' => Craft::t('formie', 'Outbound Email'),
                                'value' => 10,
                            ],
                        ],
                    ],
                    'type' => IntegrationField::TYPE_NUMBER,
                ]),
                new IntegrationField([
                    'handle' => 'tags',
                    'name' => Craft::t('formie', 'Tags'),
                    'type' => IntegrationField::TYPE_ARRAY,
                ]),
                new IntegrationField([
                    'handle' => 'company_id',
                    'name' => Craft::t('formie', 'Company ID'),
                    'type' => IntegrationField::TYPE_NUMBER,
                ]),
            ], $this->_getCustomFields($fields));

            $settings = [
                'contact' => $contactFields,
                'ticket' => $ticketFields,
            ];
        } catch (Throwable $e) {
            Integration::apiError($this, $e);
        }

        return new IntegrationFormSettings($settings);
    }

    public function sendPayload(Submission $submission): bool
    {
        try {
            // Send Contact payload
            if ($this->mapToContact) {
                $contactValues = $this->getFieldMappingValues($submission, $this->contactFieldMapping, 'contact');

                // Directly modify the field values first
                $contactFields = $this->_prepCustomFields($contactValues);

                // Only add custom fields if array not empty to prevent validation error
                if ($contactFields) {
                    $contactPayload = array_merge($contactValues, [
                        'custom_fields' => $contactFields,
                    ]);
                } else {
                    $contactPayload = $contactValues;
                }

                try {
                    $response = $this->deliverPayload($submission, 'contacts', $contactPayload);

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
                } catch (Throwable $e) {
                    $body = Json::decode((string)$e->getResponse()->getBody());

                    // Check number of errors; if more than one, we can't update anyway
                    if (count($body->errors) === 1) {
                        $err = $body->errors[0];

                        // Now check that the sole error is actually due to existing contact
                        if ($err->field === 'email' && $err->code === 'duplicate_value') {
                            try {
                                $updateResponse = $this->deliverPayload(
                                    $submission,
                                    "contacts/{$err->additional_info->user_id}",
                                    $contactPayload,
                                    'PUT'
                                );

                                if ($updateResponse === false) {
                                    return true;
                                }
                            } catch (Throwable $e) {
                                // If fails to update, most likely an agent and can safely ignore exception
                            }
                        }
                    }
                } catch (Throwable $e) {
                    // For now, we don't care about an existing contact
                }
            }

            // Send Ticket payload
            if ($this->mapToTicket) {
                $requiresMultipart = $this->_requiresMultipart($this->ticketFieldMapping, $submission);

                $ticketValues = $this->getFieldMappingValues($submission, $this->ticketFieldMapping, 'ticket', $requiresMultipart);

                if ($requiresMultipart) {
                    $ticketPayload = $ticketValues;
                    $contentType = 'multipart';
                } else {
                    // Directly modify the field values first
                    $ticketFields = $this->_prepCustomFields($ticketValues);

                    // Only add custom fields if array not empty to prevent validation error
                    if ($ticketFields) {
                        $ticketPayload = array_merge($ticketValues, [
                            'custom_fields' => $ticketFields,
                        ]);
                    } else {
                        $ticketPayload = $ticketValues;
                    }

                    // Extra payload prep - some fields are finicky
                    if (isset($ticketPayload['status'])) {
                        $ticketPayload['status'] = (int)$ticketPayload['status'];
                    }

                    if (isset($ticketPayload['priority'])) {
                        $ticketPayload['priority'] = (int)$ticketPayload['priority'];
                    }

                    if (isset($ticketPayload['source'])) {
                        $ticketPayload['source'] = (int)$ticketPayload['source'];
                    }

                    // Unset any attachments field, empty or otherwise, since
                    // _requiresMultipart() has already determined we don't need it
                    unset($ticketPayload['attachments']);

                    $contentType = 'json';
                }

                $response = $this->deliverPayload($submission, 'tickets', $ticketPayload, 'POST', $contentType);

                if ($response === false) {
                    return true;
                }

                $ticketId = $response['id'] ?? '';

                if (!$ticketId) {
                    Integration::error($this, Craft::t('formie', 'Missing return “ticketId” {response}. Sent payload {payload}', [
                        'response' => Json::encode($response),
                        'payload' => Json::encode($ticketPayload),
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
            $response = $this->request('GET', 'tickets');
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
            'base_uri' => "$url/api/v2/",
            'auth' => [App::parseEnv($this->apiKey), 'password'],
        ]);
    }

    public function getFieldMappingValues(Submission $submission, $fieldMapping, $fieldSettings = [], bool $multipart = false)
    {
        // If multipart isn't required, just use verbb\formie\base\Crm::getFieldMappingValues
        if (!$multipart) {
            return parent::getFieldMappingValues($submission, $fieldMapping, $fieldSettings);
        }

        // Manually get field settings since we're not using parent method
        $fieldSettings = $this->getFormSettingValue($fieldSettings);
        $fieldValues = [];

        if (!is_array($fieldMapping)) {
            $fieldMapping = [];
        }

        foreach ($fieldMapping as $tag => $fieldKey) {
            // Don't let in un-mapped fields
            if ($fieldKey === '') {
                continue;
            }

            // Prep custom field names for multipart
            if (StringHelper::startsWith($tag, 'custom:')) {
                $name = 'custom_fields[' . str_replace('custom:', '', $tag) . ']';
            } else {
                $name = $tag;
            }

            if (str_contains($fieldKey, '{')) {
                // Handle attachments differently to get file contents
                if ($tag === 'attachments') {
                    $name .= '[]';

                    foreach ($this->_attachments as $attachment) {
                        $fieldValues[] = [
                            'name' => $name,
                            'contents' => Utils::tryFopen($attachment->getImageTransformSourcePath(), 'r'),
                            'filename' => $attachment->filename,
                        ];
                    }
                } else {
                    // Get the type of field we are mapping to (for the integration)
                    $integrationField = ArrayHelper::firstWhere($fieldSettings, 'handle', $tag) ?? new IntegrationField();
                    $value = $this->getMappedFieldValue($fieldKey, $submission, $integrationField);

                    // Loop through the value if it's an array
                    if (is_array($value)) {
                        foreach ($value as $key => $contents) {
                            $fieldValues[] = [
                                'name' => is_string($key) ? "{$name}[{$key}]" : "{$name}[]",
                                'contents' => $contents,
                            ];
                        }
                    } else if ($value !== '' && $value !== null) {
                        $fieldValues[] = [
                            'name' => $name,
                            'contents' => $value,
                        ];
                    }
                }
            } else {
                // Otherwise, might have passed in a direct, static value
                $fieldValues[] = [
                    'name' => $name,
                    'contents' => $fieldKey,
                ];
            }
        }

        $event = new ModifyFieldIntegrationValuesEvent([
            'fieldValues' => $fieldValues,
            'submission' => $submission,
            'fieldMapping' => $fieldMapping,
            'fieldSettings' => $fieldSettings,
            'integration' => $this,
        ]);

        $this->trigger(Integration::EVENT_MODIFY_FIELD_MAPPING_VALUES, $event);

        return $event->fieldValues;
    }

    // Private Methods
    // =========================================================================

    private function _convertFieldType($fieldType)
    {
        $fieldTypes = [
            'custom_date' => IntegrationField::TYPE_DATETIME,
            'custom_checkbox' => IntegrationField::TYPE_BOOLEAN,
            'custom_decimal' => IntegrationField::TYPE_FLOAT,
            'custom_number' => IntegrationField::TYPE_NUMBER,
        ];

        return $fieldTypes[$fieldType] ?? IntegrationField::TYPE_STRING;
    }

    private function _getCustomFields($fields, $excludeNames = []): array
    {
        $customFields = [];

        $supportedFields = [
            'custom_text',
            'custom_dropdown',
            'custom_paragraph',
            'custom_date',
            'custom_checkbox',
            'custom_decimal',
            'custom_number',
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

            $customFields[] = new IntegrationField([
                'handle' => 'custom:' . $field['name'],
                'name' => $field['label'],
                'type' => $this->_convertFieldType($field['type']),
                'required' => $field['required_for_customers'],
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

    private function _requiresMultipart(array $mapping, Submission $submission): bool
    {
        // If assets integration field isn't mapped, multipart is not needed
        if (!isset($mapping['attachments'])) {
            return false;
        }

        // Replace how we store the value (as `{field_handle}` or `{submission:id}`)
        $fieldKey = str_replace(['{', '}'], ['', ''], $mapping['attachments']);

        // If this is a submission attribute, it's not anything that can be attached
        if (StringHelper::startsWith($fieldKey, 'submission:')) {
            return false;
        }

        // Check for nested fields (as `group[name[prefix]]`) - convert to dot-notation
        if (str_contains($fieldKey, '[')) {
            $fieldKey = str_replace(['[', ']'], ['.', ''], $fieldKey);

            // Change the field handle to reflect the top-level field, not the full path to the value
            // but still keep the subfield path (if any) for some fields to use
            $fieldKey = explode('.', $fieldKey);
            $fieldHandle = array_shift($fieldKey);
            $fieldKey = implode('.', $fieldKey);
        } else {
            $fieldHandle = $fieldKey;
            $fieldKey = '';
        }

        // Fetch all custom fields here for efficiency
        $formFields = ArrayHelper::index($submission->getFieldLayout()->getCustomFields(), 'handle');

        // Try and get the form field we're pulling data from
        $field = $formFields[$fieldHandle] ?? null;

        // If the field exists, check if any value exists
        if ($field && $value = $submission->getFieldValue($fieldHandle)) {
            // If the value is an AssetQuery, get the results
            if ($value instanceof AssetQuery) {
                $assets = $value->all();

                if (!empty($assets)) {
                    // Cache attachments for future use
                    $this->_attachments = $assets;
                    return true;
                }
            }
        }

        return false;
    }
}
