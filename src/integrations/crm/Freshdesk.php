<?php
namespace verbb\formie\integrations\crm;

use verbb\formie\base\Crm;
use verbb\formie\base\Integration;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\errors\IntegrationException;
use verbb\formie\events\SendIntegrationPayloadEvent;
use verbb\formie\models\IntegrationCollection;
use verbb\formie\models\IntegrationField;
use verbb\formie\models\IntegrationFormSettings;

use Craft;
use craft\helpers\ArrayHelper;
use craft\helpers\Json;
use craft\helpers\StringHelper;
use craft\web\View;

class Freshdesk extends Crm
{
    // Properties
    // =========================================================================

    public $apiKey;
    public $apiDomain;
    public $mapToContact = false;
    public $mapToTicket = false;
    public $contactFieldMapping;
    public $ticketFieldMapping;


    // Public Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
    public static function displayName(): string
    {
        return Craft::t('formie', 'Freshdesk');
    }

    /**
     * @inheritDoc
     */
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
        $rules[] = [['contactFieldMapping'], 'validateFieldMapping', 'params' => $contact, 'when' => function($model) {
            return $model->enabled && $model->mapToContact;
        }, 'on' => [Integration::SCENARIO_FORM]];

        $rules[] = [['ticketFieldMapping'], 'validateFieldMapping', 'params' => $ticket, 'when' => function($model) {
            return $model->enabled && $model->mapToTicket;
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
            // Get Contact fields
            $fields = $this->request('GET', 'contact_fields');

            $contactFields = array_merge([
                new IntegrationField([
                    'handle' => 'name',
                    'name' => Craft::t('formie', 'Name'),
                    'required' => true,
                ]),
                new IntegrationField([
                    'handle' => 'email',
                    'name' => Craft::t('formie', 'Email'),
                    'required' => true,
                ]),
                new IntegrationField([
                    'handle' => 'phone',
                    'name' => Craft::t('formie', 'Phone'),
                ]),
                new IntegrationField([
                    'handle' => 'mobile',
                    'name' => Craft::t('formie', 'Mobile'),
                ]),
                new IntegrationField([
                    'handle' => 'twitter_id',
                    'name' => Craft::t('formie', 'Twitter ID'),
                ]),
                new IntegrationField([
                    'handle' => 'company_id',
                    'name' => Craft::t('formie', 'Company ID'),
                ]),
                new IntegrationField([
                    'handle' => 'description',
                    'name' => Craft::t('formie', 'Description'),
                ]),
                new IntegrationField([
                    'handle' => 'job_title',
                    'name' => Craft::t('formie', 'Job Title'),
                ]),
                new IntegrationField([
                    'handle' => 'time_zone',
                    'name' => Craft::t('formie', 'Timezone'),
                ]),
            ], $this->_getCustomFields($fields));

            // Get Ticket fields
            $fields = $this->request('GET', 'ticket_fields');

            $ticketFields = array_merge([
                new IntegrationField([
                    'handle' => 'name',
                    'name' => Craft::t('formie', 'Name'),
                    'required' => true,
                ]),
                new IntegrationField([
                    'handle' => 'email',
                    'name' => Craft::t('formie', 'Email'),
                    'required' => true,
                ]),
                new IntegrationField([
                    'handle' => 'phone',
                    'name' => Craft::t('formie', 'Phone'),
                ]),
                new IntegrationField([
                    'handle' => 'unique_external_id',
                    'name' => Craft::t('formie', 'Unique External ID'),
                ]),
                new IntegrationField([
                    'handle' => 'subject',
                    'name' => Craft::t('formie', 'Subject'),
                    'required' => true,
                ]),
                new IntegrationField([
                    'handle' => 'type',
                    'name' => Craft::t('formie', 'Type'),
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
                ]),
                new IntegrationField([
                    'handle' => 'description',
                    'name' => Craft::t('formie', 'Description'),
                    'required' => true,
                ]),
                new IntegrationField([
                    'handle' => 'responder_id',
                    'name' => Craft::t('formie', 'Responder ID'),
                ]),
                new IntegrationField([
                    'handle' => 'attachments',
                    'name' => Craft::t('formie', 'Attachments'),
                ]),
                new IntegrationField([
                    'handle' => 'cc_emails',
                    'name' => Craft::t('formie', 'CC Emails'),
                ]),
                new IntegrationField([
                    'handle' => 'due_by',
                    'name' => Craft::t('formie', 'Due By'),
                ]),
                new IntegrationField([
                    'handle' => 'email_config_id',
                    'name' => Craft::t('formie', 'Email Config ID'),
                ]),
                new IntegrationField([
                    'handle' => 'fr_due_by',
                    'name' => Craft::t('formie', 'First Response Due By'),
                ]),
                new IntegrationField([
                    'handle' => 'group_id',
                    'name' => Craft::t('formie', 'Group ID'),
                ]),
                new IntegrationField([
                    'handle' => 'product_id',
                    'name' => Craft::t('formie', 'Product ID'),
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
                ]),
                new IntegrationField([
                    'handle' => 'tags',
                    'name' => Craft::t('formie', 'Tags'),
                ]),
                new IntegrationField([
                    'handle' => 'company_id',
                    'name' => Craft::t('formie', 'Company ID'),
                ]),
            ], $this->_getCustomFields($fields));

            $settings = [
                'contact' => $contactFields,
                'ticket' => $ticketFields,
            ];
        } catch (\Throwable $e) {
            Integration::error($this, Craft::t('formie', 'API error: “{message}” {file}:{line}', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]), true);
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
            $ticketValues = $this->getFieldMappingValues($submission, $this->ticketFieldMapping, 'ticket');

            // Directly modify the field values first
            $contactFields = $this->_prepCustomFields($contactValues);
            $ticketFields = $this->_prepCustomFields($ticketValues);

            // Send Contact payload
            $contactPayload = array_merge($contactValues, [
                'custom_fields' => $contactFields,
            ]);

            try {
                $response = $this->deliverPayload($submission, 'contacts', $contactPayload);

                if ($response === false) {
                    return false;
                }

                $contactId = $response['id'] ?? '';

                if (!$contactId) {
                    Integration::error($this, Craft::t('formie', 'Missing return “contactId” {response}', [
                        'response' => Json::encode($response),
                    ]), true);

                    return false;
                }
            } catch (\Throwable $e) {
                // For now, we don't care about an existing contact
            }

            // Send Ticket payload
            $ticketPayload = array_merge($ticketValues, [
                'custom_fields' => $ticketFields,
            ]);

            // Extra payload prep - some fields are finnicky
            if (isset($ticketPayload['status'])) {
                $ticketPayload['status'] = (int)$ticketPayload['status'];
            }

            if (isset($ticketPayload['priority'])) {
                $ticketPayload['priority'] = (int)$ticketPayload['priority'];
            }

            if (isset($ticketPayload['source'])) {
                $ticketPayload['source'] = (int)$ticketPayload['source'];
            }

            $response = $this->deliverPayload($submission, 'tickets', $ticketPayload);

            if ($response === false) {
                return false;
            }

            $ticketId = $response['id'] ?? '';

            if (!$ticketId) {
                Integration::error($this, Craft::t('formie', 'Missing return “ticketId” {response}', [
                    'response' => Json::encode($response),
                ]), true);

                return false;
            }
        } catch (\Throwable $e) {
            Integration::error($this, Craft::t('formie', 'API error: “{message}” {file}:{line}', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]), true);

            return false;
        }

        return true;
    }

    /**
     * @inheritDoc
     */
    public function fetchConnection(): bool
    {
        try {
            $response = $this->request('GET', 'tickets');
        } catch (\Throwable $e) {
            Integration::error($this, Craft::t('formie', 'API error: “{message}” {file}:{line}', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]), true);

            return false;
        }

        return true;
    }


    // Protected Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
    protected function getClient()
    {
        if ($this->_client) {
            return $this->_client;
        }

        $url = rtrim(Craft::parseEnv($this->apiDomain), '/');

        return $this->_client = Craft::createGuzzleClient([
            'base_uri' => "$url/api/v2/",
            'auth' => [Craft::parseEnv($this->apiKey), 'password'],
        ]);
    }


    // Private Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
    private function _convertFieldType($fieldType)
    {
        $fieldTypes = [
            'custom_date' => IntegrationField::TYPE_DATETIME,
            'custom_checkbox' => IntegrationField::TYPE_BOOLEAN,
            'custom_decimal' => IntegrationField::TYPE_NUMBER,
            'custom_number' => IntegrationField::TYPE_NUMBER,
        ];

        return $fieldTypes[$fieldType] ?? IntegrationField::TYPE_STRING;
    }

    /**
     * @inheritDoc
     */
    private function _getCustomFields($fields, $excludeNames = [])
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

    /**
     * @inheritDoc
     */
    private function _prepCustomFields(&$fields)
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