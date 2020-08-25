<?php
namespace verbb\formie\integrations\crm;

use verbb\formie\base\Crm;
use verbb\formie\base\Integration;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\errors\IntegrationException;
use verbb\formie\events\SendIntegrationPayloadEvent;
use verbb\formie\models\IntegrationField;
use verbb\formie\models\EmailMarketingList;

use Craft;
use craft\helpers\ArrayHelper;
use craft\helpers\Json;
use craft\web\View;

class ActiveCampaign extends Crm
{
    // Properties
    // =========================================================================

    public $apiKey;
    public $apiUrl;
    public $mapToDeal = false;
    public $mapToAccount = false;
    public $contactFieldMapping;
    public $dealFieldMapping;
    public $accountFieldMapping;


    // Public Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
    public static function displayName(): string
    {
        return Craft::t('formie', 'ActiveCampaign');
    }

    /**
     * @inheritDoc
     */
    public function getDescription(): string
    {
        return Craft::t('formie', 'Manage your ActiveCampaign customers by providing important information on their conversion on your site.');
    }

    /**
     * @inheritDoc
     */
    public function defineRules(): array
    {
        $rules = parent::defineRules();

        $rules[] = [['apiKey', 'apiUrl'], 'required'];

        $contact = $this->getFormSettings()['contact'] ?? [];
        $deal = $this->getFormSettings()['deal'] ?? [];
        $account = $this->getFormSettings()['account'] ?? [];

        // Validate the following when saving form settings
        $rules[] = [['contactFieldMapping'], 'validateFieldMapping', 'params' => $contact, 'when' => function($model) {
            return $model->enabled;
        }, 'on' => [Integration::SCENARIO_FORM]];

        $rules[] = [['dealFieldMapping'], 'validateFieldMapping', 'params' => $deal, 'when' => function($model) {
            return $model->enabled && $model->mapToDeal;
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
        $dealGroupOptions = [];
        $dealStageOptions = [];
        $listOptions = [];

        // Populate some options for some values
        try {
            $response = $this->_request('GET', 'dealGroups');
            $dealGroups = $response['dealGroups'] ?? [];
            $dealStages = $response['dealStages'] ?? [];

            foreach ($dealGroups as $dealGroup) {
                $dealGroupOptions[] = [
                    'label' => $dealGroup['title'],
                    'value' => $dealGroup['id'],
                ];
            }

            foreach ($dealStages as $dealStage) {
                $dealStageOptions[] = [
                    'label' => $dealStage['title'],
                    'value' => $dealStage['id'],
                ];
            }

            $response = $this->_request('GET', 'lists');
            $lists = $response['lists'] ?? [];

            foreach ($lists as $list) {
                $listOptions[] = [
                    'label' => $list['name'],
                    'value' => $list['id'],
                ];
            }
        } catch (\Throwable $e) {
            Integration::error($this, Craft::t('formie', 'API error: “{message}” {file}:{line}', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]));
        }

        $settings = [
            'contact' => [
                new IntegrationField([
                    'handle' => 'listId',
                    'name' => Craft::t('formie', 'List'),
                    'options' => [
                        'label' => Craft::t('formie', 'Lists'),
                        'options' => $listOptions,
                    ],
                ]),
                new IntegrationField([
                    'handle' => 'email',
                    'name' => Craft::t('formie', 'Email'),
                    'required' => true,
                ]),
                new IntegrationField([
                    'handle' => 'firstName',
                    'name' => Craft::t('formie', 'First Name'),
                ]),
                new IntegrationField([
                    'handle' => 'lastName',
                    'name' => Craft::t('formie', 'Last Name'),
                ]),
                new IntegrationField([
                    'handle' => 'phone',
                    'name' => Craft::t('formie', 'Phone'),
                ]),
            ],

            'deal' => [
                new IntegrationField([
                    'handle' => 'title',
                    'name' => Craft::t('formie', 'Title'),
                    'required' => true,
                ]),
                new IntegrationField([
                    'handle' => 'description',
                    'name' => Craft::t('formie', 'Description'),
                ]),
                new IntegrationField([
                    'handle' => 'value',
                    'name' => Craft::t('formie', 'Value'),
                    'required' => true,
                ]),
                new IntegrationField([
                    'handle' => 'currency',
                    'name' => Craft::t('formie', 'Currency'),
                    'required' => true,
                ]),
                new IntegrationField([
                    'handle' => 'group',
                    'name' => Craft::t('formie', 'Pipeline (Group)'),
                    'required' => true,
                    'options' => [
                        'label' => Craft::t('formie', 'Pipelines'),
                        'options' => $dealGroupOptions,
                    ],
                ]),
                new IntegrationField([
                    'handle' => 'stage',
                    'name' => Craft::t('formie', 'Stage'),
                    'required' => true,
                    'options' => [
                        'label' => Craft::t('formie', 'Stages'),
                        'options' => $dealStageOptions,
                    ],
                ]),
                new IntegrationField([
                    'handle' => 'owner',
                    'name' => Craft::t('formie', 'Owner'),
                    'required' => true,
                ]),
                new IntegrationField([
                    'handle' => 'percent',
                    'name' => Craft::t('formie', 'Percent'),
                ]),
                new IntegrationField([
                    'handle' => 'status',
                    'name' => Craft::t('formie', 'Status'),
                    'options' => [
                        'label' => Craft::t('formie', 'Status'),
                        'options' => [
                            [
                                'label' => Craft::t('formie', 'Open'),
                                'value' => '0',
                            ],
                            [
                                'label' => Craft::t('formie', 'Won'),
                                'value' => '1',
                            ],
                            [
                                'label' => Craft::t('formie', 'Lost'),
                                'value' => '2',
                            ],
                        ],
                    ],
                ]),
            ],

            'account' => [
                new IntegrationField([
                    'handle' => 'name',
                    'name' => Craft::t('formie', 'Name'),
                    'required' => true,
                ]),
            ],
        ];

        try {
            $response = $this->_request('GET', 'fields');
            $fields = $response['fields'] ?? [];

            // Don't use all fields, at least for the moment...
            $supportedFields = [
                'text',
                'textarea',
                'hidden',
                'dropdown',
                'radio',
                'date',
                // 'checkbox',
                // 'listbox',
            ];

            foreach ($fields as $field) {
                if (in_array($field['type'], $supportedFields)) {
                    $settings['contact'][] = new IntegrationField([
                        'handle' => (string)$field['id'],
                        'name' => $field['title'],
                        'type' => $field['type'],
                    ]);
                }
            }

            // Fetch deal-specific fields
            $response = $this->_request('GET', 'dealCustomFieldMeta');
            $fields = $response['dealCustomFieldMeta'] ?? [];

            foreach ($fields as $field) {
                if (in_array($field['fieldType'], $supportedFields)) {
                    $settings['deal'][] = new IntegrationField([
                        'handle' => (string)$field['id'],
                        'name' => $field['fieldLabel'],
                        'type' => $field['fieldType'],
                    ]);
                }
            }

            // Fetch account-specific fields
            $response = $this->_request('GET', 'accountCustomFieldMeta');
            $fields = $response['accountCustomFieldMeta'] ?? [];

            foreach ($fields as $field) {
                if (in_array($field['fieldType'], $supportedFields)) {
                    $settings['account'][] = new IntegrationField([
                        'handle' => (string)$field['id'],
                        'name' => $field['fieldLabel'],
                        'type' => $field['fieldType'],
                    ]);
                }
            }
        } catch (\Throwable $e) {
            Integration::error($this, Craft::t('formie', 'API error: “{message}” {file}:{line}', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]));
        }

        return $settings;
    }

    /**
     * @inheritDoc
     */
    public function sendPayload(Submission $submission): bool
    {
        try {
            $contactValues = $this->getFieldMappingValues($submission, $this->contactFieldMapping);
            $dealValues = $this->getFieldMappingValues($submission, $this->dealFieldMapping);
            $accountValues = $this->getFieldMappingValues($submission, $this->accountFieldMapping);

            $accountId = null;
            $contactId = null;

            $email = ArrayHelper::remove($contactValues, 'email');
            $firstName = ArrayHelper::remove($contactValues, 'firstName');
            $lastName = ArrayHelper::remove($contactValues, 'lastName');
            $phone = ArrayHelper::remove($contactValues, 'phone');
            $listId = ArrayHelper::remove($contactValues, 'listId');

            $contactPayload = [
                'contact' => [
                    'email' => $email,
                    'firstName' => $firstName,
                    'lastName' => $lastName,
                    'phone' => $phone,
                    'fieldValues' => $this->_prepCustomFields($contactValues),
                ],
            ];

            $response = $this->_sendPayload($submission, 'contact/sync', $contactPayload);

            if ($response === false) {
                return false;
            }

            $contactId = $response['contact']['id'] ?? '';

            if (!$contactId) {
                Integration::error($this, Craft::t('formie', 'Missing return “contactId” {response}', [
                    'response' => Json::encode($response),
                ]), true);

                return false;
            }

            // If we're wanting to add them to a mailing list as well...
            if ($listId) {
                $payload = [
                    'contactList' => [
                        'list' => $listId,
                        'contact' => $contactId,
                        'status' => 1,
                    ],
                ];

                $response = $this->_sendPayload($submission, 'contactLists', $payload);

                if ($response === false) {
                    return false;
                }
            }

            if ($this->mapToAccount) {
                $accountName = ArrayHelper::remove($accountValues, 'name');

                $accountPayload = [
                    'account' => [
                        'name' => $accountName,
                        'fields' => $this->_prepAltCustomFields($accountValues),
                    ],
                ];

                // Try to find the account first
                $response = $this->_request('GET', 'accounts');

                $accounts = $response['accounts'] ?? [];
                $accountId = '';

                foreach ($accounts as $account) {
                    if (strtolower($account['name']) === strtolower($accountName)) {
                        $accountId = $account['id'];
                    }
                }

                // If not found already, create it
                if (!$accountId) {
                    $response = $this->_sendPayload($submission, 'accounts', $accountPayload);

                    if ($response === false) {
                        return false;
                    }

                    $accountId = $response['account']['id'] ?? '';
                }

                // Add the contact to the account, if both were okay
                if ($accountId && $contactId) {
                    $payload = [
                        'accountContact' => [
                            'contact' => $contactId,
                            'account' => $accountId,
                        ],
                    ];

                    // Don't proceed with an update if already associated
                    $response = $this->_request('GET', 'accountContacts');
                    $accountContacts = $response['accountContacts'][0]['id'] ?? '';

                    if (!$accountContacts) {
                        $response = $this->_sendPayload($submission, 'accountContacts', $payload);

                        if ($response === false) {
                            return false;
                        }
                    }
                }
            }

            if ($this->mapToDeal) {
                $currency = ArrayHelper::remove($dealValues, 'currency');

                $dealPayload = [
                    'deal' => [
                        'title' => ArrayHelper::remove($dealValues, 'title'),
                        'description' => ArrayHelper::remove($dealValues, 'description'),
                        'account' => $accountId ?? '',
                        'contact' => $contactId ?? '',
                        'value' => ArrayHelper::remove($dealValues, 'value'),
                        'currency' => strtolower($currency),
                        'group' => ArrayHelper::remove($dealValues, 'group'),
                        'stage' => ArrayHelper::remove($dealValues, 'stage'),
                        'owner' => ArrayHelper::remove($dealValues, 'owner'),
                        'percent' => ArrayHelper::remove($dealValues, 'percent'),
                        'status' => ArrayHelper::remove($dealValues, 'status'),
                        'fields' => $this->_prepAltCustomFields($dealValues),
                    ],
                ];

                $response = $this->_sendPayload($submission, 'deals', $dealPayload);

                if ($response === false) {
                    return false;
                }
            }
        } catch (\Throwable $e) {
            Integration::error($this, Craft::t('formie', 'API error: “{message}” {file}:{line}', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]));

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
            $response = $this->_request('GET', '');
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


    // Private Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
    private function _getClient()
    {
        if ($this->_client) {
            return $this->_client;
        }

        return $this->_client = Craft::createGuzzleClient([
            'base_uri' => trim($this->apiUrl, '/') . '/api/3/',
            'headers' => ['Api-Token' => $this->apiKey],
        ]);
    }

    /**
     * @inheritDoc
     */
    private function _request(string $method, string $uri, array $options = [])
    {
        $response = $this->_getClient()->request($method, trim($uri, '/'), $options);

        return Json::decode((string)$response->getBody());
    }

    /**
     * @inheritDoc
     */
    private function _sendPayload($submission, $endpoint, $payload)
    {
        // Allow events to cancel sending
        if (!$this->beforeSendPayload($submission, $payload)) {
            return false;
        }

        $response = $this->_request('POST', $endpoint, [
            'json' => $payload,
        ]);

        // Allow events to say the response is invalid
        if (!$this->afterSendPayload($submission, $payload, $response)) {
            return false;
        }

        return $response;
    }

    /**
     * @inheritDoc
     */
    private function _prepCustomFields($fields)
    {
        $customFields = [];

        foreach ($fields as $key => $value) {
            $customFields[] = [
                'field' => $key,
                'value' => $value,
            ];
        }

        return $customFields;
    }

    /**
     * @inheritDoc
     */
    private function _prepAltCustomFields($fields)
    {
        $customFields = [];

        foreach ($fields as $key => $value) {
            $customFields[] = [
                'customFieldId' => $key,
                'fieldValue' => $value,
            ];
        }

        return $customFields;
    }
}