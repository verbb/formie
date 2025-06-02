<?php
namespace verbb\formie\integrations\crm;

use verbb\formie\base\Crm;
use verbb\formie\base\Integration;
use verbb\formie\elements\Submission;
use verbb\formie\helpers\ArrayHelper;
use verbb\formie\models\IntegrationField;
use verbb\formie\models\IntegrationFormSettings;

use Craft;
use craft\helpers\App;
use craft\helpers\Json;

use GuzzleHttp\Client;

use Throwable;

class ActiveCampaign extends Crm
{
    // Static Methods
    // =========================================================================

    public static function displayName(): string
    {
        return Craft::t('formie', 'ActiveCampaign');
    }


    // Properties
    // =========================================================================

    public ?string $apiKey = null;
    public ?string $apiUrl = null;
    public bool $mapToContact = false;
    public bool $mapToDeal = false;
    public bool $mapToAccount = false;
    public ?array $contactFieldMapping = null;
    public ?array $dealFieldMapping = null;
    public ?array $accountFieldMapping = null;


    // Public Methods
    // =========================================================================

    public function getDescription(): string
    {
        return Craft::t('formie', 'Manage your {name} customers by providing important information on their conversion on your site.', ['name' => static::displayName()]);
    }

    public function fetchFormSettings(): IntegrationFormSettings
    {
        $settings = [];

        try {
            // Get Contacts fields
            if ($this->mapToContact) {
                $fields = $this->_getPaginated('fields');

                $settings['contact'] = array_merge([
                    new IntegrationField([
                        'handle' => 'listId',
                        'name' => Craft::t('formie', 'List'),
                        'options' => [
                            'label' => Craft::t('formie', 'Lists'),
                            'options' => array_map(function($list) {
                                return [
                                    'label' => $list['name'],
                                    'value' => $list['id'],
                                ];
                            }, $this->_getPaginated('lists')),
                        ],
                    ]),
                    new IntegrationField([
                        'handle' => 'subscribed',
                        'name' => Craft::t('formie', 'Subscribe Status'),
                        'type' => IntegrationField::TYPE_NUMBER,
                        'options' => [
                            'label' => Craft::t('formie', 'Subscribe Status'),
                            'options' => [
                                ['label' => Craft::t('formie', 'Subscribe'), 'value' => 1],
                                ['label' => Craft::t('formie', 'Unsubscribe'), 'value' => 2],
                            ],
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

                $settings['contact'][] = new IntegrationField([
                    'handle' => 'tags',
                    'name' => Craft::t('formie', 'Tags'),
                ]);
            }

            // Get Deals fields
            if ($this->mapToDeal) {
                $fields = $this->_getPaginated('dealCustomFieldMeta');

                $settings['deal'] = array_merge([
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
                            'options' => array_map(function($dealGroup) {
                                return [
                                    'label' => $dealGroup['title'],
                                    'value' => $dealGroup['id'],
                                ];
                            }, $this->_getPaginated('dealGroups')),
                        ],
                    ]),
                    new IntegrationField([
                        'handle' => 'stage',
                        'name' => Craft::t('formie', 'Stage'),
                        'required' => true,
                        'options' => [
                            'label' => Craft::t('formie', 'Stages'),
                            'options' => array_map(function($dealStage) {
                                return [
                                    'label' => $dealStage['title'],
                                    'value' => $dealStage['id'],
                                ];
                            }, $this->_getPaginated('dealStages')),
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
            }

            // Get Account fields
            if ($this->mapToAccount) {
                $fields = $this->_getPaginated('accountCustomFieldMeta');

                $settings['account'] = array_merge([
                    new IntegrationField([
                        'handle' => 'name',
                        'name' => Craft::t('formie', 'Name'),
                        'required' => true,
                    ]),
                ], $this->_getCustomFields($fields));
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
                $subscribed = ArrayHelper::remove($fieldValues, 'subscribed', 1);

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
                            'status' => $subscribed,
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

    
    // Protected Methods
    // =========================================================================

    protected function defineClient(): Client
    {
        return Craft::createGuzzleClient([
            'base_uri' => trim(App::parseEnv($this->apiUrl), '/') . '/api/3/',
            'headers' => ['Api-Token' => App::parseEnv($this->apiKey)],
        ]);
    }


    // Protected Methods
    // =========================================================================

    protected function defineRules(): array
    {
        $rules = parent::defineRules();

        $rules[] = [['apiKey', 'apiUrl'], 'required'];

        $contact = $this->getFormSettingValue('contact');
        $deal = $this->getFormSettingValue('deal');
        $account = $this->getFormSettingValue('account');

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
            ['accountFieldMapping'], 'validateFieldMapping', 'params' => $account, 'when' => function($model) {
                return $model->enabled && $model->mapToAccount;
            }, 'on' => [Integration::SCENARIO_FORM],
        ];

        return $rules;
    }


    // Private Methods
    // =========================================================================

    private function _convertFieldType(string $fieldType): string
    {
        $fieldTypes = [
            'multiselect' => IntegrationField::TYPE_ARRAY,
            'checkbox' => IntegrationField::TYPE_ARRAY,
            'date' => IntegrationField::TYPE_DATETIME,
            'datetime' => IntegrationField::TYPE_DATETIME,
        ];

        return $fieldTypes[$fieldType] ?? IntegrationField::TYPE_STRING;
    }

    private function _getCustomFields(array $fields, array $excludeNames = []): array
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
            'datetime',
            'checkbox',
            'currency',
            'number',
            // 'listbox',
        ];

        foreach ($fields as $field) {
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
                'sourceType' => $fieldType,
            ]);
        }

        return $customFields;
    }

    private function _prepCustomFields(array $fields): array
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

    private function _prepAltCustomFields(array $fields): array
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

    private function _getPaginated(string $endpoint, int $limit = 100, int $offset = 0, array $items = []): array
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
            $items = $this->_getPaginated($endpoint, $limit, $offset + $limit, $items);
        }

        return $items;
    }
}