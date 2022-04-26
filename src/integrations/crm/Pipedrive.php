<?php
namespace verbb\formie\integrations\crm;

use verbb\formie\base\Crm;
use verbb\formie\base\Integration;
use verbb\formie\elements\Submission;
use verbb\formie\events\ModifyFieldIntegrationValueEvent;
use verbb\formie\fields\formfields\Phone;
use verbb\formie\models\IntegrationField;
use verbb\formie\models\IntegrationFormSettings;

use Craft;
use craft\helpers\App;
use craft\helpers\ArrayHelper;
use craft\helpers\Json;

use yii\base\Event;

use GuzzleHttp\Client;

use Throwable;

class Pipedrive extends Crm
{
    // Static Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
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
        });
    }

    /**
     * @inheritDoc
     */
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

    public function getDescription(): string
    {
        return Craft::t('formie', 'Manage your Pipedrive customers by providing important information on their conversion on your site.');
    }

    /**
     * @inheritDoc
     */
    public function defineRules(): array
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

    public function fetchFormSettings(): IntegrationFormSettings
    {
        $settings = [];

        try {
            // Get Person fields
            $response = $this->request('GET', 'personFields');
            $fields = $response['data'] ?? [];
            $personFields = array_merge($this->_getCustomFields($fields), [
                new IntegrationField([
                    'handle' => 'note',
                    'name' => Craft::t('formie', 'Note'),
                ]),
            ]);

            // Get Deal fields
            $response = $this->request('GET', 'dealFields');
            $fields = $response['data'] ?? [];
            $dealFields = array_merge($this->_getCustomFields($fields), [
                new IntegrationField([
                    'handle' => 'note',
                    'name' => Craft::t('formie', 'Note'),
                ]),
            ]);

            // Get Lead fields
            $leadFields = [
                new IntegrationField([
                    'handle' => 'title',
                    'name' => Craft::t('formie', 'Title'),
                    'required' => true,
                ]),
                new IntegrationField([
                    'handle' => 'owner_id',
                    'name' => Craft::t('formie', 'Owner ID'),
                ]),
                new IntegrationField([
                    'handle' => 'note',
                    'name' => Craft::t('formie', 'Note'),
                ]),
                new IntegrationField([
                    'handle' => 'value',
                    'name' => Craft::t('formie', 'Value'),
                ]),
            ];

            // Get Organization fields
            $response = $this->request('GET', 'organizationFields');
            $fields = $response['data'] ?? [];
            $organizationFields = array_merge($this->_getCustomFields($fields), [
                new IntegrationField([
                    'handle' => 'note',
                    'name' => Craft::t('formie', 'Note'),
                ]),
            ]);

            // Get Note fields
            $response = $this->request('GET', 'noteFields');
            $fields = $response['data'] ?? [];
            $noteFields = $this->_getCustomFields($fields);

            $settings = [
                'person' => $personFields,
                'deal' => $dealFields,
                'lead' => $leadFields,
                'organization' => $organizationFields,
                'note' => $noteFields,
            ];
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

                $response = $this->deliverPayload($submission, 'persons', $personPayload);

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

    public function getClient(): Client
    {
        if ($this->_client) {
            return $this->_client;
        }

        return $this->_client = Craft::createGuzzleClient([
            'base_uri' => 'https://api.pipedrive.com/v1/',
            'query' => ['api_token' => App::parseEnv($this->apiKey)],
        ]);
    }


    // Private Methods
    // =========================================================================

    private function _convertFieldType($fieldType)
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

    private function _getCustomFields($fields, $excludeNames = []): array
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
                'required' => $required,
                'options' => $options,
            ]);
        }

        return $customFields;
    }
}
