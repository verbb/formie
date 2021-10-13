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
use craft\web\View;

class Pipedrive extends Crm
{
    // Properties
    // =========================================================================

    public $apiKey;
    public $mapToPerson = false;
    public $mapToDeal = false;
    public $mapToLead = false;
    public $mapToOrganization = false;
    public $mapToNote = false;
    public $personFieldMapping;
    public $dealFieldMapping;
    public $leadFieldMapping;
    public $organizationFieldMapping;
    public $noteFieldMapping;


    // Public Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
    public static function displayName(): string
    {
        return Craft::t('formie', 'Pipedrive');
    }

    /**
     * @inheritDoc
     */
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
        $rules[] = [['personFieldMapping'], 'validateFieldMapping', 'params' => $person, 'when' => function($model) {
            return $model->enabled && $model->mapToPerson;
        }, 'on' => [Integration::SCENARIO_FORM]];

        $rules[] = [['dealFieldMapping'], 'validateFieldMapping', 'params' => $deal, 'when' => function($model) {
            return $model->enabled && $model->mapToDeal;
        }, 'on' => [Integration::SCENARIO_FORM]];

        $rules[] = [['leadFieldMapping'], 'validateFieldMapping', 'params' => $lead, 'when' => function($model) {
            return $model->enabled && $model->mapToLead;
        }, 'on' => [Integration::SCENARIO_FORM]];

        $rules[] = [['organizationFieldMapping'], 'validateFieldMapping', 'params' => $organization, 'when' => function($model) {
            return $model->enabled && $model->mapToOrganization;
        }, 'on' => [Integration::SCENARIO_FORM]];

        $rules[] = [['noteFieldMapping'], 'validateFieldMapping', 'params' => $note, 'when' => function($model) {
            return $model->enabled && $model->mapToNote;
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
            // Get Person fields
            $response = $this->request('GET', 'personFields');
            $fields = $response['data'] ?? [];
            $personFields = $this->_getCustomFields($fields);

            // Get Deal fields
            $response = $this->request('GET', 'dealFields');
            $fields = $response['data'] ?? [];
            $dealFields = $this->_getCustomFields($fields);

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
            $organizationFields = $this->_getCustomFields($fields);
            
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
            $personValues = $this->getFieldMappingValues($submission, $this->personFieldMapping, 'person');
            $dealValues = $this->getFieldMappingValues($submission, $this->dealFieldMapping, 'deal');
            $leadValues = $this->getFieldMappingValues($submission, $this->leadFieldMapping, 'lead');
            $organizationValues = $this->getFieldMappingValues($submission, $this->organizationFieldMapping, 'organization');
            $noteValues = $this->getFieldMappingValues($submission, $this->noteFieldMapping, 'note');

            $organizationId = null;

            if ($this->mapToOrganization) {
                $organizationPayload = $organizationValues;

                $response = $this->deliverPayload($submission, 'organizations', $organizationPayload);

                if ($response === false) {
                    return true;
                }

                $organizationId = $response['data']['id'] ?? '';

                if (!$organizationId) {
                    Integration::error($this, Craft::t('formie', 'Missing return “organizationId” {response}. Sent payload {payload}', [
                        'response' => Json::encode($response),
                        'payload' => Json::encode($contactPayload),
                    ]), true);

                    return false;
                }
            }

            $personId = null;

            if ($this->mapToPerson) {
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
            }

            $dealId = null;

            if ($this->mapToDeal) {
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
            }

            $leadId = null;

            if ($this->mapToLead) {
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
            $response = $this->request('GET', 'deals');
            $success = $response['success'] ?? false;

            if (!$success) {
                Integration::error($this, Craft::t('formie', 'Missing return “success” {response}', [
                    'response' => Json::encode($response),
                ]), true);

                return false;
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
    public function getClient()
    {
        if ($this->_client) {
            return $this->_client;
        }

        return $this->_client = Craft::createGuzzleClient([
            'base_uri' => 'https://api.pipedrive.com/v1/',
            'query' => ['api_token' => Craft::parseEnv($this->apiKey)],
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

    /**
     * @inheritDoc
     */
    private function _getCustomFields($fields, $excludeNames = [])
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

            foreach ($fieldOptions as $key => $fieldOption) {
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
                    foreach ($stages as $key => $stage) {
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
                    foreach ($currencies as $key => $currency) {
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