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

class Salesmate extends Crm
{
    // Static Methods
    // =========================================================================

    public static function displayName(): string
    {
        return Craft::t('formie', 'Salesmate');
    }
    

    // Properties
    // =========================================================================
    
    public ?string $apiKey = null;
    public ?string $apiDomain = null;
    public bool $mapToContact = false;
    public ?array $contactFieldMapping = null;

    private array $_entityOptions = [];


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
            if ($this->mapToContact) {
                $response = $this->request('GET', 'module/v4/1/fields');
                $fields = $response['Data'] ?? [];

                $settings['contact'] = $this->_getCustomFields($fields);
            }
        } catch (Throwable $e) {
            Integration::apiError($this, $e);
        }

        return new IntegrationFormSettings($settings);
    }

    public function sendPayload(Submission $submission): bool
    {
        try {
            if ($this->mapToContact) {
                $contactValues = $this->getFieldMappingValues($submission, $this->contactFieldMapping, 'contact');

                $payload = $contactValues;

                $response = $this->request('POST', 'contact/v4/search', [
                    'json' => [
                        'displayingFields' => [
                            'contact.id',
                            'contact.email',
                        ],
                        'filterQuery' => [
                            'group' => [
                                'rules' => [
                                    [
                                        'condition' => 'EQUALS',
                                        'moduleName' => 'Contact',
                                        'field' => [
                                            'fieldName' => 'contact.email',
                                        ],
                                        'data' => $payload['email'],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ]);

                $contactId = $response['Data']['data'][0]['id'] ?? null;

                if ($contactId) {
                    $response = $this->deliverPayload($submission, "contact/v4/$contactId" , $payload, 'PUT');
                } else {
                    $response = $this->deliverPayload($submission, 'contact/v4', $payload);

                    $contactId = $response['Data']['id'] ?? '';
                }

                if ($response === false) {
                    return true;
                }

                if (!$contactId) {
                    Integration::error($this, Craft::t('formie', 'Missing return “contactId” {response}. Sent payload {payload}', [
                        'response' => Json::encode($response),
                        'payload' => Json::encode($payload),
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
            $response = $this->request('GET', 'core/v4/users?status=active');
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

        $rules[] = [['apiKey', 'apiDomain'], 'required'];

        $contact = $this->getFormSettingValue('contact');

        // Validate the following when saving form settings
        $rules[] = [
            ['contactFieldMapping'], 'validateFieldMapping', 'params' => $contact, 'when' => function($model) {
                return $model->enabled && $model->mapToContact;
            }, 'on' => [Integration::SCENARIO_FORM],
        ];

        return $rules;
    }

    protected function defineClient(): Client
    {
        $domain = parse_url(App::parseEnv($this->apiDomain))['host'];
        $url = rtrim(App::parseEnv($this->apiDomain), '/');

        return Craft::createGuzzleClient([
            'base_uri' => "$url/apis/",
            'headers' => [
                'accessToken' => App::parseEnv($this->apiKey),
                'x-linkname' => $domain,
            ],
        ]);
    }


    // Private Methods
    // =========================================================================

    private function _convertFieldType(string $fieldType): string
    {
        $fieldTypes = [
            'date' => IntegrationField::TYPE_DATE,
            'datetime' => IntegrationField::TYPE_DATETIME,
            'number' => IntegrationField::TYPE_NUMBER,
        ];

        return $fieldTypes[$fieldType] ?? IntegrationField::TYPE_STRING;
    }

    private function _getCustomFields(array $fields): array
    {
        $customFields = [];

        foreach ($fields as $key => $field) {
            if (($field['isInternal'] ?? false)) {
                continue;
            }
            
            if (($field['groupName'] ?? '') === 'Internal') {
                continue;
            }

            $options = [];

            if ($field['fieldOptions']) {
                $fieldOptions = Json::decode($field['fieldOptions']);

                // Check for static values
                if (isset($fieldOptions['valuesMeta'])) {
                    foreach ($fieldOptions['valuesMeta'] as $opt) {
                        $options[] = [
                            'label' => $opt['label'] ?? '',
                            'value' => (string)($opt['value'] ?? ''),
                        ];
                    }
                } else if (isset($fieldOptions['url'])) {
                    $entityPath = $fieldOptions['url'];

                    if ($entityPath === '/lookups/companies') {
                        $options = $this->_listCompanies();
                    } else if ($entityPath === '/lookups/contacts') {
                        $options = $this->_listContacts();
                    } else if ($entityPath === '/lookups/deals') {
                        $options = $this->_listDeals();
                    } else if ($entityPath === '/users/active') {
                        $options = $this->_listUsers();
                    } else if ($entityPath === '/lookups/active/currency') {
                        $options = $this->_listCurrencies();
                    }
                }
            }

            if ($options) {
                $options = [
                    'label' => $field['displayName'],
                    'options' => $options,
                ];
            }

            $customFields[] = new IntegrationField([
                'handle' => $field['fieldName'],
                'name' => $field['displayName'],
                'type' => $this->_convertFieldType($field['type']),
                'sourceType' => $field['type'],
                'required' => (bool)($field['isRequired'] ?? false),
                'options' => $options,
            ]);
        }

        return $customFields;
    }

    private function _listUsers(): array
    {
        if ($cached = ($this->_entityOptions['users'] ?? [])) {
            return $cached;
        }

        $response = $this->request('GET', 'core/v4/users?status=active');

        $options = [];

        foreach (($response['Data'] ?? []) as $option) {
            $options[] = [
                'label' => $option['name'],
                'value' => (string)$option['id'],
            ];
        }

        return $this->_entityOptions['users'] = $options;
    }

    private function _listCurrencies(): array
    {
        if ($cached = ($this->_entityOptions['currencies'] ?? [])) {
            return $cached;
        }

        $response = $this->request('GET', 'v3/lookups/active/currency');

        $options = [];

        foreach (($response['Data'] ?? []) as $option) {
            $options[] = [
                'label' => $option['displayLabel'],
                'value' => (string)$option['code'],
            ];
        }

        return $this->_entityOptions['currencies'] = $options;
    }

    private function _listPipelines(): array
    {
        return [];
    }

    private function _listStages(): array
    {
        return [];
    }

    private function _listTags(): array
    {
        return [];
    }

    private function _listContacts(): array
    {
        if ($cached = ($this->_entityOptions['contacts'] ?? [])) {
            return $cached;
        }

        $response = $this->request('POST', 'contact/v4/search', [
            'json' => [
                'displayingFields' => [
                    'contact.company.name',
                    'contact.company.id',
                    'contact.company.photo',
                    'contact.designation',
                    'contact.type',
                    'contact.email',
                    'contact.mobile',
                    'contact.billingCity',
                    'contact.billingCountry',
                    'contact.tags',
                    'contact.name',
                    'contact.lastNoteAddedBy.name',
                    'contact.lastNoteAddedBy.photo',
                    'contact.lastNoteAddedBy.id',
                    'contact.lastNoteAddedAt',
                    'contact.lastNote',
                    'contact.lastCommunicationMode',
                    'contact.lastCommunicationBy',
                    'contact.lastCommunicationAt',
                    'contact.lastModifiedBy.name',
                    'contact.lastModifiedBy.photo',
                    'contact.lastModifiedBy.id',
                    'contact.createdBy.name',
                    'contact.createdBy.photo',
                    'contact.createdBy.id',
                    'contact.lastModifiedAt',
                    'contact.openDealCount',
                    'contact.utmSource',
                    'contact.utmCampaign',
                    'contact.utmTerm',
                    'contact.utmMedium',
                    'contact.utmContent',
                    'contact.library',
                    'contact.emailMessageCount',
                    'contact.description',
                    'contact.photo',
                    'contact.emailOptOut',
                    'contact.firstName',
                    'contact.lastName',
                    'contact.id',
                    'contact.createdAt',
                ],
                'filterQuery' => [
                    'group' => [
                        'rules' => [
                            [
                                'condition' => 'IS_AFTER',
                                'moduleName' => 'Contact',
                                'field' => [
                                    'fieldName' => 'contact.createdAt',
                                    'displayName' => 'Created At',
                                    'type' => 'DateTime',
                                ],
                                'data' => 'Jan 01, 1970 05:30 AM',
                                'eventType' => 'DateTime',
                            ],
                        ],
                    ],
                ],
                'sort' => [
                    'fieldName' => 'contact.createdAt',
                    'order' => 'desc',
                ],
            ]
        ]);

        $options = [];

        foreach (($response['Data']['data'] ?? []) as $option) {
            $options[] = [
                'label' => $option['name'],
                'value' => (string)$option['id'],
            ];
        }

        return $this->_entityOptions['contacts'] = $options;
    }

    private function _listDeals(): array
    {
        if ($cached = ($this->_entityOptions['deals'] ?? [])) {
            return $cached;
        }

        $response = $this->request('POST', 'deal/v4/search', [
            'json' => [
                'displayingFields' => [
                    'deal.id',
                    'deal.title',
                    'deal.primaryContact.totalActivities',
                    'deal.primaryContact.id',
                    'deal.primaryContact.photo',
                    'deal.primaryContact.closedActivities',
                    'deal.primaryContact.openActivities',
                    'deal.lastModifiedAt',
                    'deal.pipeline',
                    'deal.stage',
                    'deal.owner.name',
                    'deal.owner.photo',
                    'deal.owner.id',
                    'deal.lastCommunicationBy',
                    'deal.source',
                    'deal.dealValue',
                    'deal.status',
                    'deal.estimatedCloseDate',
                    'deal.lastNote',
                    'deal.lastActivityAt',
                    'deal.primaryCompany.name',
                    'deal.primaryCompany.id',
                    'deal.primaryCompany.photo',
                    'deal.lostReason',
                    'deal.currency',
                    'deal.priority',
                    'deal.tags',
                    'deal.description',
                    'deal.closedDate',
                    'deal.primaryContact.name',
                    'deal.lastCommunicationAt',
                    'deal.primaryContact.firstName',
                    'deal.primaryContact.lastName',
                ],
                'filterQuery' => [
                    'group' => [
                        'operator' => 'AND',
                        'rules' => [
                            [
                                'condition' => 'IS_AFTER',
                                'moduleName' => 'Deal',
                                'field' => [
                                    'fieldName' => 'deal.createdAt',
                                    'displayName' => 'Created At',
                                    'type' => 'DateTime',
                                ],
                                'data' => 'Jan 01, 1970 05:30 AM',
                                'eventType' => 'DateTime',
                            ],
                        ],
                    ],
                ],
                'sort' => [
                    'fieldName' => 'deal.createdAt',
                    'order' => 'desc',
                ],
                'moduleId' => 4,
            ]
        ]);

        $options = [];

        foreach (($response['Data']['data'] ?? []) as $option) {
            $options[] = [
                'label' => $option['name'],
                'value' => (string)$option['id'],
            ];
        }

        return $this->_entityOptions['deals'] = $options;
    }

    private function _listCompanies(): array
    {
        if ($cached = ($this->_entityOptions['companies'] ?? [])) {
            return $cached;
        }

        $response = $this->request('POST', 'company/v4/search', [
            'json' => [
                'displayingFields' => [
                    'company.createdAt',
                    'company.type',
                    'company.phone',
                    'company.billingAddressLine1',
                    'company.textCustomField3',
                    'company.tags',
                    'company.annualRevenue',
                    'company.billingCity',
                    'company.owner.name',
                    'company.owner.photo',
                    'company.owner.id',
                    'company.name',
                    'company.billingState',
                    'company.billingCountry',
                    'company.totalAmountOfOpenDeal',
                    'company.lastCommunicationAt',
                    'company.lastCommunicationMode',
                    'company.openActivities',
                    'company.totalAmountOfWonDeal',
                    'company.textCustomField8',
                    'company.wonDealCount',
                    'company.lostDealCount',
                    'company.openDealCount',
                    'company.photo',
                    'company.id',
                ],
                'filterQuery' => [
                    'group' => [
                        'rules' => [
                            [
                                'condition' => 'IS_AFTER',
                                'moduleName' => 'Company',
                                'field' => [
                                    'fieldName' => 'company.createdAt',
                                    'displayName' => 'Created At',
                                    'type' => 'DateTime',
                                ],
                                'data' => 'Jan 01, 1970 05:30 AM',
                                'eventType' => 'DateTime',
                            ],
                        ],
                    ],
                ],
                'sort' => [
                    'fieldName' => 'company.name',
                    'order' => 'asc',
                ],
            ]
        ]);

        $options = [];

        foreach (($response['Data']['data'] ?? []) as $option) {
            $options[] = [
                'label' => $option['name'],
                'value' => (string)$option['id'],
            ];
        }

        return $this->_entityOptions['companies'] = $options;
    }
}