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
use craft\helpers\App;
use craft\helpers\ArrayHelper;
use craft\helpers\Json;
use craft\web\View;

class ActiveCampaign extends Crm
{
    // Properties
    // =========================================================================

    public $apiKey;
    public $apiUrl;
    public $mapToContact = false;
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

        $contact = $this->getFormSettingValue('contact');
        $deal = $this->getFormSettingValue('deal');
        $account = $this->getFormSettingValue('account');

        // Validate the following when saving form settings
        $rules[] = [['contactFieldMapping'], 'validateFieldMapping', 'params' => $contact, 'when' => function($model) {
            return $model->enabled && $model->mapToContact;
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
            $dealGroups = $this->_getPaginated('dealGroups');

            foreach ($dealGroups as $dealGroup) {
                $dealGroupOptions[] = [
                    'label' => $dealGroup['title'],
                    'value' => $dealGroup['id'],
                ];
            }

            $dealStages = $this->_getPaginated('dealStages');

            foreach ($dealStages as $dealStage) {
                $dealStageOptions[] = [
                    'label' => $dealStage['title'],
                    'value' => $dealStage['id'],
                ];
            }

            $lists = $this->_getPaginated('lists');

            foreach ($lists as $list) {
                $listOptions[] = [
                    'label' => $list['name'],
                    'value' => $list['id'],
                ];
            }

            // Get Contacts fields
            $fields = $this->_getPaginated('fields');

            $contactFields = array_merge([
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
            ], $this->_getCustomFields($fields));

            $contactFields[] = new IntegrationField([
                'handle' => 'tags',
                'name' => Craft::t('formie', 'Tags'),
            ]);

            // Get Deals fields
            $fields = $this->_getPaginated('dealCustomFieldMeta');

            $dealFields = array_merge([
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
            ], $this->_getCustomFields($fields));

            // Get Account fields
            $fields = $this->_getPaginated('accountCustomFieldMeta');

            $accountFields = array_merge([
                new IntegrationField([
                    'handle' => 'name',
                    'name' => Craft::t('formie', 'Name'),
                    'required' => true,
                ]),
            ], $this->_getCustomFields($fields));

            $settings = [
                'contact' => $contactFields,
                'deal' => $dealFields,
                'account' => $accountFields,
            ];
        } catch (\Throwable $e) {
            Integration::apiError($this, $e);
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
            $dealValues = $this->getFieldMappingValues($submission, $this->dealFieldMapping, 'deal');
            $accountValues = $this->getFieldMappingValues($submission, $this->accountFieldMapping, 'account');

            $accountId = null;
            $contactId = null;

            if ($this->mapToContact) {
                $email = ArrayHelper::remove($contactValues, 'email');
                $firstName = ArrayHelper::remove($contactValues, 'firstName');
                $lastName = ArrayHelper::remove($contactValues, 'lastName');
                $phone = ArrayHelper::remove($contactValues, 'phone');
                $listId = ArrayHelper::remove($contactValues, 'listId');
                $tags = ArrayHelper::remove($contactValues, 'tags');

                $contactPayload = [
                    'contact' => array_filter([
                        'email' => $email,
                        'firstName' => $firstName,
                        'lastName' => $lastName,
                        'phone' => $phone,
                        'fieldValues' => $this->_prepCustomFields($contactValues),
                    ]),
                ];

                $response = $this->deliverPayload($submission, 'contact/sync', $contactPayload);

                if ($response === false) {
                    return true;
                }

                $contactId = $response['contact']['id'] ?? '';

                if (!$contactId) {
                    Integration::error($this, Craft::t('formie', 'Missing return “contactId” {response}. Sent payload {payload}', [
                        'response' => Json::encode($response),
                        'payload' => Json::encode($contactPayload),
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

                    $response = $this->deliverPayload($submission, 'contactLists', $payload);

                    if ($response === false) {
                        return true;
                    }
                }

                // Process any tags, we need to find or create each one.
                if ($tags) {
                    // Cleanup and handle multiple tags
                    $tags = array_filter(array_map('trim', explode(',', $tags)));

                    if ($tags) {
                        // Find all the tags first
                        $existingTags = $this->_getPaginated('tags');
                        $tagIds = [];

                        // Process each tag
                        foreach ($tags as $tag) {
                            // Find if it's already been created, don't create again
                            foreach ($existingTags as $existingTag) {
                                if ($existingTag['tag'] === $tag) {
                                    $tagIds[] = $existingTag['id'];

                                    continue 2;
                                }
                            }

                            // Create the tag
                            $tagPayload = [
                                'tag' => [
                                    'tag' => $tag,
                                    'tagType' => 'contact',
                                    'description' => '',
                                ],
                            ];

                            $response = $this->deliverPayload($submission, 'tags', $tagPayload);

                            $tagIds[] = $response['tag']['id'] ?? null;
                        }

                        // Assign all tags to the contact
                        foreach ($tagIds as $tagId) {
                            $tagPayload = [
                                'contactTag' => [
                                    'contact' => $contactId,
                                    'tag' => $tagId,
                                ],
                            ];

                            $response = $this->deliverPayload($submission, 'contactTags', $tagPayload);

                            if ($response === false) {
                                return true;
                            }
                        }
                    }
                }
            }

            if ($this->mapToAccount) {
                $accountName = ArrayHelper::remove($accountValues, 'name');

                $accountPayload = [
                    'account' => array_filter([
                        'name' => $accountName,
                        'fields' => $this->_prepAltCustomFields($accountValues),
                    ]),
                ];

                // Try to find the account first
                $response = $this->request('GET', 'accounts', [
                    'query' => [
                        'limit' => 100,
                    ],
                ]);

                $accounts = $response['accounts'] ?? [];
                $accountId = '';

                foreach ($accounts as $account) {
                    if (strtolower($account['name']) === strtolower($accountName)) {
                        $accountId = $account['id'];
                    }
                }

                // If not found already, create it
                if (!$accountId) {
                    $response = $this->deliverPayload($submission, 'accounts', $accountPayload);

                    if ($response === false) {
                        return true;
                    }

                    $accountId = $response['account']['id'] ?? '';
                }

                // Add the contact to the account, if both were okay
                if ($accountId && $contactId) {
                    $payload = [
                        'accountContact' => array_filter([
                            'contact' => $contactId,
                            'account' => $accountId,
                        ]),
                    ];

                    // Don't proceed with an update if already associated
                    $response = $this->request('GET', 'accountContacts', [
                        'query' => [
                            'limit' => 100,
                        ],
                    ]);

                    $accountContacts = $response['accountContacts'][0]['id'] ?? '';

                    if (!$accountContacts) {
                        $response = $this->deliverPayload($submission, 'accountContacts', $payload);

                        if ($response === false) {
                            return true;
                        }
                    }
                }
            }

            if ($this->mapToDeal) {
                $currency = ArrayHelper::remove($dealValues, 'currency');

                $dealPayload = [
                    'deal' => array_filter([
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
                    ]),
                ];

                $response = $this->deliverPayload($submission, 'deals', $dealPayload);

                if ($response === false) {
                    return true;
                }
            }
        } catch (\Throwable $e) {
            Integration::apiError($this, $e);

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
            $response = $this->request('GET', 'contacts');
        } catch (\Throwable $e) {
            Integration::apiError($this, $e);

            return false;
        }

        return true;
    }

    /**
     * @inheritDoc
     */
    public function getClient()
    {
        if ($this->_client) {
            return $this->_client;
        }

        return $this->_client = Craft::createGuzzleClient([
            'base_uri' => trim(App::parseEnv($this->apiUrl), '/') . '/api/3/',
            'headers' => ['Api-Token' => App::parseEnv($this->apiKey)],
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
            'multiselect' => IntegrationField::TYPE_ARRAY,
            'checkbox' => IntegrationField::TYPE_ARRAY,
            'date' => IntegrationField::TYPE_DATETIME,
        ];

        return $fieldTypes[$fieldType] ?? IntegrationField::TYPE_STRING;
    }

    /**
     * @inheritDoc
     */
    private function _getCustomFields($fields, $excludeNames = [])
    {
        $customFields = [];

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

        foreach ($fields as $key => $field) {
            // Some endpoints return different things!
            $fieldName = $field['fieldLabel'] ?? $field['title'] ?? '';
            $fieldType = $field['fieldType'] ?? $field['type'] ?? '';

            // // Only allow supported types
            if (!in_array($fieldType, $supportedFields)) {
                continue;
            }

            // Exclude any names
            if (in_array($fieldName, $excludeNames)) {
                continue;
            }

            $customFields[] = new IntegrationField([
                'handle' => (string)$field['id'],
                'name' => $fieldName,
                'type' => $this->_convertFieldType($fieldType),
            ]);
        }

        return $customFields;
    }

    /**
     * @inheritDoc
     */
    private function _prepCustomFields($fields)
    {
        $customFields = [];

        $fields = array_filter($fields);

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

        $fields = array_filter($fields);

        foreach ($fields as $key => $value) {
            $customFields[] = [
                'customFieldId' => $key,
                'fieldValue' => $value,
            ];
        }

        return $customFields;
    }

    /**
     * @inheritDoc
     */
    private function _getPaginated($endpoint, $limit = 100, $offset = 0, $items = [])
    {
        $response = $this->request('GET', $endpoint, [
            'query' => [
                'limit' => $limit,
                'offset' => $offset,
            ],
        ]);

        $newItems = $response[$endpoint] ?? [];
        $total = $response['meta']['total'] ?? 0;

        $items = array_merge($items, $newItems);

        if (count($items) < $total) {
            $items = array_merge($items, $this->_getPaginated($endpoint, $limit, $offset + $limit, $items));
        }

        return $items;
    }
}