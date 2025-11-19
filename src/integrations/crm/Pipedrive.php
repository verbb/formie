<?php
namespace verbb\formie\integrations\crm;

use verbb\formie\base\Crm;
use verbb\formie\base\Integration;
use verbb\formie\elements\Submission;
use verbb\formie\events\ModifyFieldIntegrationValueEvent;
use verbb\formie\fields\Phone;
use verbb\formie\helpers\ArrayHelper;
use verbb\formie\models\IntegrationField;
use verbb\formie\models\IntegrationFormSettings;

use Craft;
use craft\helpers\App;
use craft\helpers\Json;

use yii\base\Event;

use GuzzleHttp\Client;

use Throwable;

class Pipedrive extends Crm
{
    // Static Methods
    // =========================================================================

    public static function displayName(): string
    {
        return Craft::t('formie', 'Pipedrive');
    }
    

    // Properties
    // =========================================================================
    
    public ?string $apiKey = null;
    public bool $mapToPerson = false;
    public bool $mapToDeal = false;
    public bool $mapToLead = false;
    public bool $mapToOrganization = false;
    public bool $mapToNote = false;
    public ?array $personFieldMapping = null;
    public ?array $dealFieldMapping = null;
    public ?array $leadFieldMapping = null;
    public ?array $organizationFieldMapping = null;
    public ?array $noteFieldMapping = null;


    // Public Methods
    // =========================================================================

    public function init(): void
    {
        parent::init();

        Event::on(self::class, self::EVENT_MODIFY_FIELD_MAPPING_VALUE, function(ModifyFieldIntegrationValueEvent $event) {
            // Special handling for phone fields which need to be supplied as an array, but for country dropdown enabled
            // fields, this will produce an array, but with extra info. Just simplify the value.
            if ($event->integrationField->getType() === IntegrationField::TYPE_ARRAY && $event->field instanceof Phone) {
                // Skip when the field is a plain phone number field, or mapping the "number" directly
                if (is_array($event->value) && isset($event->value['number'])) {
                    $event->value = [$event->value['number']];
                }
            }

            // Special handling for "Multiple Options" (set) Pipedrive fields, which expect an ID of the option as a numeric value
            if ($event->integrationField->sourceType === 'set') {
                if (is_array($event->value)) {
                    foreach ($event->value as $key => $value) {
                        $event->value[$key] = (int)$value;
                    }
                }
            }
        });
    }

    public function getDescription(): string
    {
        return Craft::t('formie', 'Manage your {name} customers by providing important information on their conversion on your site.', ['name' => static::displayName()]);
    }

    public function fetchFormSettings(): IntegrationFormSettings
    {
        $settings = [];

        try {
            // Get Person fields
            if ($this->mapToPerson) {
                $response = $this->request('GET', 'personFields');
                $fields = $response['data'] ?? [];

                $settings['person'] = array_merge($this->_getCustomFields($fields), [
                    new IntegrationField([
                        'handle' => 'note',
                        'name' => Craft::t('formie', 'Note'),
                    ]),
                ]);
            }

            // Deals and Leads use the same custom fields
            if ($this->mapToDeal || $this->mapToLead) {
                $response = $this->request('GET', 'dealFields');
                $dealLeadFields = $response['data'] ?? [];
            }

            // Get Deal fields
            if ($this->mapToDeal) {
                $settings['deal'] = array_merge($this->_getCustomFields($dealLeadFields), [
                    new IntegrationField([
                        'handle' => 'note',
                        'name' => Craft::t('formie', 'Note'),
                    ]),
                ]);
            }

            // Get Lead fields - uses the same custom fields as deals
            if ($this->mapToLead) {
                $leadFields = $this->_getCustomFields($dealLeadFields, ['currency', 'probability', 'stage_id', 'label', 'status']);

                $response = $this->request('GET', 'leadLabels');
                $leadLabels = $response['data'] ?? [];

                $response = $this->request('GET', 'users');
                $users = $response['data'] ?? [];

                $response = $this->request('GET', 'currencies');
                $currencies = $response['data'] ?? [];

                $settings['lead'] = array_merge($leadFields, [
                    new IntegrationField([
                        'handle' => 'currency',
                        'name' => Craft::t('formie', 'Currency'),
                        'options' => [
                            'label' => Craft::t('formie', 'Currency'),
                            'options' => array_map(function($currency) {
                                return [
                                    'label' => $currency['name'],
                                    'value' => (string)$currency['code'],
                                ];
                            }, $currencies),
                        ],
                    ]),
                    new IntegrationField([
                        'handle' => 'owner_id',
                        'name' => Craft::t('formie', 'Owner'),
                        'type' => IntegrationField::TYPE_NUMBER,
                        'options' => [
                            'label' => Craft::t('formie', 'Owner'),
                            'options' => array_map(function($user) {
                                return [
                                    'label' => $user['name'],
                                    'value' => (string)$user['id'],
                                ];
                            }, $users),
                        ],
                    ]),
                    new IntegrationField([
                        'handle' => 'label_ids',
                        'name' => Craft::t('formie', 'Labels'),
                        'type' => IntegrationField::TYPE_ARRAY,
                        'options' => [
                            'label' => Craft::t('formie', 'Labels'),
                            'options' => array_map(function($leadLabel) {
                                return [
                                    'label' => $leadLabel['name'],
                                    'value' => (string)$leadLabel['id'],
                                ];
                            }, $leadLabels),
                        ],
                    ]),
                    new IntegrationField([
                        'handle' => 'note',
                        'name' => Craft::t('formie', 'Note'),
                    ]),
                ]);
            }

            // Get Organization fields
            if ($this->mapToOrganization) {
                $response = $this->request('GET', 'organizationFields');
                $fields = $response['data'] ?? [];

                $settings['organization'] = array_merge($this->_getCustomFields($fields), [
                    new IntegrationField([
                        'handle' => 'note',
                        'name' => Craft::t('formie', 'Note'),
                    ]),
                ]);
            }

            // Get Note fields
            if ($this->mapToNote) {
                $response = $this->request('GET', 'noteFields');
                $fields = $response['data'] ?? [];
                $settings['note'] = $this->_getCustomFields($fields);
            }
        } catch (Throwable $e) {
            Integration::apiError($this, $e);
        }

        return new IntegrationFormSettings($settings);
    }

    public function sendPayload(Submission $submission): bool
    {
        try {
            $personValues = $this->getFieldMappingValues($submission, $this->personFieldMapping, 'person');
            $dealValues = $this->getFieldMappingValues($submission, $this->dealFieldMapping, 'deal');
            $leadValues = $this->getFieldMappingValues($submission, $this->leadFieldMapping, 'lead');
            $organizationValues = $this->getFieldMappingValues($submission, $this->organizationFieldMapping, 'organization');
            $noteValues = $this->getFieldMappingValues($submission, $this->noteFieldMapping, 'note');

            $organizationId = null;

            if ($this->mapToOrganization) {
                // Extract notes, which need to be separate
                $note = ArrayHelper::remove($organizationValues, 'note');

                $organizationPayload = $organizationValues;

                $response = $this->deliverPayload($submission, 'organizations', $organizationPayload);

                if ($response === false) {
                    return true;
                }

                $organizationId = $response['data']['id'] ?? '';

                if (!$organizationId) {
                    Integration::error($this, Craft::t('formie', 'Missing return “organizationId” {response}. Sent payload {payload}', [
                        'response' => Json::encode($response),
                        'payload' => Json::encode($organizationPayload),
                    ]), true);

                    return false;
                }

                // Add the note separately
                if ($note) {
                    $payload = [
                        'content' => $note,
                        'org_id' => $organizationId,
                        'pinned_to_organization_flag' => '1',
                    ];

                    $response = $this->deliverPayload($submission, 'notes', $payload);
                }
            }

            $personId = null;

            if ($this->mapToPerson) {
                // Extract notes, which need to be separate
                $note = ArrayHelper::remove($personValues, 'note');

                $personPayload = $personValues;

                if ($organizationId) {
                    $personPayload['org_id'] = $organizationId;
                }

                $existingPersonEmail = $personPayload['email'] ?? '';

                // Try and find the person first, doesn't handle adding existing ones well
                $response = $this->request('GET', 'persons/search', [
                    'query' => [
                        'api_token' => App::parseEnv($this->apiKey),
                        'term' => $existingPersonEmail,
                        'exact_match' => true,
                        'limit' => 1,
                        'fields' => 'email',
                    ],
                ]);

                $existingPersonId = $response['data']['items'][0]['item']['id'] ?? null;

                // Update or create
                if ($existingPersonId) {
                    $response = $this->deliverPayload($submission, "persons/{$existingPersonId}", $personPayload, 'PUT');
                } else {
                    $response = $this->deliverPayload($submission, 'persons', $personPayload);
                }

                if ($response === false) {
                    return true;
                }

                $personId = $response['data']['id'] ?? '';

                if (!$personId) {
                    Integration::error($this, Craft::t('formie', 'Missing return “personId” {response}. Sent payload {payload}', [
                        'response' => Json::encode($response),
                        'payload' => Json::encode($personPayload),
                    ]), true);

                    return false;
                }

                // Add the note separately
                if ($note) {
                    $payload = [
                        'content' => $note,
                        'person_id' => $personId,
                        'pinned_to_person_flag' => '1',
                    ];

                    $response = $this->deliverPayload($submission, 'notes', $payload);
                }
            }

            $dealId = null;

            if ($this->mapToDeal) {
                // Extract notes, which need to be separate
                $note = ArrayHelper::remove($dealValues, 'note');

                $dealPayload = $dealValues;

                if ($organizationId) {
                    $dealPayload['org_id'] = $organizationId;
                }

                if ($personId) {
                    $dealPayload['person_id'] = $personId;
                }

                $response = $this->deliverPayload($submission, 'deals', $dealPayload);

                if ($response === false) {
                    return true;
                }

                $dealId = $response['data']['id'] ?? '';

                if (!$dealId) {
                    Integration::error($this, Craft::t('formie', 'Missing return “dealId” {response}. Sent payload {payload}', [
                        'response' => Json::encode($response),
                        'payload' => Json::encode($dealPayload),
                    ]), true);

                    return false;
                }

                // Add the note separately
                if ($note) {
                    $payload = [
                        'content' => $note,
                        'deal_id' => $dealId,
                        'pinned_to_deal_flag' => '1',
                    ];

                    $response = $this->deliverPayload($submission, 'notes', $payload);
                }
            }

            $leadId = null;

            if ($this->mapToLead) {
                // Extract notes, which need to be separate
                $note = ArrayHelper::remove($leadValues, 'note');

                $leadPayload = $leadValues;

                if ($organizationId) {
                    $leadPayload['organization_id'] = $organizationId;
                }

                if ($personId) {
                    $leadPayload['person_id'] = $personId;
                }

                // Extract value and currency to build
                $value = ArrayHelper::remove($leadPayload, 'value');
                $currency = ArrayHelper::remove($leadPayload, 'currency') ?? 'USD';

                // Value needs to be formatted correctly
                if ($value) {
                    $leadPayload['value'] = ['amount' => (float)$value, 'currency' => $currency];
                }

                $response = $this->deliverPayload($submission, 'leads', $leadPayload);

                if ($response === false) {
                    return true;
                }

                $leadId = $response['data']['id'] ?? '';

                if (!$leadId) {
                    Integration::error($this, Craft::t('formie', 'Missing return “leadId” {response}. Sent payload {payload}', [
                        'response' => Json::encode($response),
                        'payload' => Json::encode($leadPayload),
                    ]), true);

                    return false;
                }

                // Add the note separately
                if ($note) {
                    $payload = [
                        'content' => $note,
                        'lead_id' => $leadId,
                        'pinned_to_lead_flag' => '1',
                    ];

                    $response = $this->deliverPayload($submission, 'notes', $payload);
                }
            }

            if ($this->mapToNote) {
                $notePayload = $noteValues;

                if ($organizationId) {
                    $notePayload['org_id'] = $organizationId;
                    $notePayload['pinned_to_organization_flag'] = '1';
                }

                if ($personId) {
                    $notePayload['person_id'] = $personId;
                    $notePayload['pinned_to_person_flag'] = '1';
                }

                if ($dealId) {
                    $notePayload['deal_id'] = $dealId;
                    $notePayload['pinned_to_deal_flag'] = '1';
                }

                if ($leadId) {
                    $notePayload['lead_id'] = $leadId;
                    $notePayload['pinned_to_lead_flag'] = '1';
                }

                $response = $this->deliverPayload($submission, 'notes', $notePayload);

                if ($response === false) {
                    return true;
                }

                $noteId = $response['data']['id'] ?? '';

                if (!$noteId) {
                    Integration::error($this, Craft::t('formie', 'Missing return “noteId” {response}. Sent payload {payload}', [
                        'response' => Json::encode($response),
                        'payload' => Json::encode($notePayload),
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
            $response = $this->request('GET', 'deals');
            $success = $response['success'] ?? false;

            if (!$success) {
                Integration::error($this, Craft::t('formie', 'Missing return “success” {response}', [
                    'response' => Json::encode($response),
                ]), true);

                return false;
            }
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
            'base_uri' => 'https://api.pipedrive.com/v1/',
            'query' => ['api_token' => App::parseEnv($this->apiKey)],
        ]);
    }


    // Protected Methods
    // =========================================================================

    protected function defineRules(): array
    {
        $rules = parent::defineRules();

        $rules[] = [['apiKey'], 'required'];

        $person = $this->getFormSettingValue('person');
        $deal = $this->getFormSettingValue('deal');
        $lead = $this->getFormSettingValue('lead');
        $organization = $this->getFormSettingValue('organization');
        $note = $this->getFormSettingValue('note');

        // Validate the following when saving form settings
        $rules[] = [
            ['personFieldMapping'], 'validateFieldMapping', 'params' => $person, 'when' => function($model) {
                return $model->enabled && $model->mapToPerson;
            }, 'on' => [Integration::SCENARIO_FORM],
        ];

        $rules[] = [
            ['dealFieldMapping'], 'validateFieldMapping', 'params' => $deal, 'when' => function($model) {
                return $model->enabled && $model->mapToDeal;
            }, 'on' => [Integration::SCENARIO_FORM],
        ];

        $rules[] = [
            ['leadFieldMapping'], 'validateFieldMapping', 'params' => $lead, 'when' => function($model) {
                return $model->enabled && $model->mapToLead;
            }, 'on' => [Integration::SCENARIO_FORM],
        ];

        $rules[] = [
            ['organizationFieldMapping'], 'validateFieldMapping', 'params' => $organization, 'when' => function($model) {
                return $model->enabled && $model->mapToOrganization;
            }, 'on' => [Integration::SCENARIO_FORM],
        ];

        $rules[] = [
            ['noteFieldMapping'], 'validateFieldMapping', 'params' => $note, 'when' => function($model) {
                return $model->enabled && $model->mapToNote;
            }, 'on' => [Integration::SCENARIO_FORM],
        ];

        return $rules;
    }


    // Private Methods
    // =========================================================================

    private function _convertFieldType(string $fieldType): string
    {
        $fieldTypes = [
            'set' => IntegrationField::TYPE_ARRAY,
            'phone' => IntegrationField::TYPE_ARRAY,
            'int' => IntegrationField::TYPE_NUMBER,
            'double' => IntegrationField::TYPE_FLOAT,
            'monetary' => IntegrationField::TYPE_NUMBER,
            'user' => IntegrationField::TYPE_NUMBER,
            'org' => IntegrationField::TYPE_NUMBER,
            'people' => IntegrationField::TYPE_NUMBER,
        ];

        return $fieldTypes[$fieldType] ?? IntegrationField::TYPE_STRING;
    }

    private function _getCustomFields(array $fields, array $excludeNames = []): array
    {
        $customFields = [];

        $supportedFields = [
            'name',
            'first_name',
            'last_name',
            'label',
            'phone',
            'email',
            'title',
            'value',
            'currency',
            'stage_id',
            'status',
            'probability',
            'content',
        ];

        $requredFields = [
            'name',
            'title',
            'content',
        ];

        foreach ($fields as $key => $field) {
            // Try to fetch just the custom fields - not all of them
            if (!preg_match('/[a-z0-9]{40}/i', $field['key']) && !in_array($field['key'], $supportedFields)) {
                continue;
            }

            // Exclude any names
            if (in_array($field['key'], $excludeNames)) {
                continue;
            }

            $required = $field['mandatory_flag'] ?? false;

            if (in_array($field['key'], $requredFields)) {
                $required = true;
            }

            $options = [];
            $fieldOptions = $field['options'] ?? [];

            foreach ($fieldOptions as $fieldOption) {
                $options[] = [
                    'label' => $fieldOption['label'],
                    'value' => $fieldOption['id'],
                ];
            }

            // Populate some fields
            if ($field['key'] === 'stage_id') {
                $response = $this->request('GET', 'stages');
                $stages = $response['data'] ?? [];

                if ($stages) {
                    foreach ($stages as $stage) {
                        $options[] = [
                            'label' => $stage['name'],
                            'value' => $stage['id'],
                        ];
                    }
                }
            }

            if ($field['key'] === 'currency') {
                $response = $this->request('GET', 'currencies');
                $currencies = $response['data'] ?? [];

                if ($currencies) {
                    foreach ($currencies as $currency) {
                        $options[] = [
                            'label' => $currency['name'],
                            'value' => $currency['code'],
                        ];
                    }
                }
            }

            if ($options) {
                $options = [
                    'label' => $field['name'],
                    'options' => $options,
                ];
            }

            $customFields[] = new IntegrationField([
                'handle' => $field['key'],
                'name' => $field['name'],
                'type' => $this->_convertFieldType($field['field_type']),
                'sourceType' => $field['field_type'],
                'required' => $required,
                'options' => $options,
            ]);
        }

        return $customFields;
    }
}
