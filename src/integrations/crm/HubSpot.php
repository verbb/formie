<?php
namespace verbb\formie\integrations\crm;

use verbb\formie\base\Crm;
use verbb\formie\base\Integration;
use verbb\formie\elements\Submission;
use verbb\formie\events\ModifyFieldIntegrationValueEvent;
use verbb\formie\helpers\ArrayHelper;
use verbb\formie\models\IntegrationCollection;
use verbb\formie\models\IntegrationField;
use verbb\formie\models\IntegrationFormSettings;

use Craft;
use craft\helpers\App;
use craft\helpers\Json;

use yii\base\Event;

use GuzzleHttp\Client;

use DateTime;
use Throwable;

class HubSpot extends Crm
{
    // Static Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
    public static function displayName(): string
    {
        return Craft::t('formie', 'HubSpot');
    }

    public static function convertValueForIntegration($value, $integrationField): mixed
    {
        // If setting checkboxes values to a static value, ensure it's sent as a single value.
        // This won't be picked up in `EVENT_MODIFY_FIELD_MAPPING_VALUE` because it's not mapped to a field.
        if ($integrationField->getType() === IntegrationField::TYPE_ARRAY) {
            return $value;
        }

        return parent::convertValueForIntegration($value, $integrationField);
    }

    // Properties
    // =========================================================================

    public ?string $accessToken = null;
    public bool $mapToContact = false;
    public bool $mapToDeal = false;
    public bool $mapToCompany = false;
    public bool $mapToForm = false;
    public ?array $contactFieldMapping = null;
    public ?array $dealFieldMapping = null;
    public ?array $companyFieldMapping = null;
    public ?array $formFieldMapping = null;
    public ?string $formId = null;

    private ?Client $_formsClient = null;


    // Public Methods
    // =========================================================================

    public function __construct($config = [])
    {
        // Config normalization - before the migration runs
        if (array_key_exists('apiKey', $config)) {
            unset($config['apiKey']);
        }

        parent::__construct($config);
    }

    /**
     * @inheritDoc
     */
    public function init(): void
    {
        parent::init();

        Event::on(self::class, self::EVENT_MODIFY_FIELD_MAPPING_VALUE, function(ModifyFieldIntegrationValueEvent $event) {
            // Special handling for single checkbox boolean fields for HubSpot
            if ($event->integrationField->getType() === IntegrationField::TYPE_BOOLEAN) {
                // HubSpot needs this as a string value (also check if already cast).
                $event->value = ($event->value === true || $event->value === 'true') ? 'true' : 'false';
            }

            // Special handling for arrays for checkboxes
            if ($event->integrationField->getType() === IntegrationField::TYPE_ARRAY) {
                if (is_array($event->value)) {
                    $event->value = array_filter($event->value);
                    $event->value = ArrayHelper::recursiveImplode($event->value, ';');
                    $event->value = str_replace('&nbsp;', ' ', $event->value);
                }
            }

            // Special handling for dates for HubSpot
            if ($event->integrationField->getType() === IntegrationField::TYPE_DATE) {
                // HubSpot needs this as a timestamp value.
                if ($event->rawValue instanceof DateTime) {
                    $date = clone $event->rawValue;
                    $date->setTime(0, 0, 0);

                    $event->value = (string)($date->getTimestamp() * 1000);
                } else {
                    // Always return the raw value for all other instances. We might be passing in the timestamp
                    $event->value = $event->rawValue;
                }
            }
        });
    }

    public function getDescription(): string
    {
        return Craft::t('formie', 'Manage your HubSpot customers by providing important information on their conversion on your site.');
    }

    /**
     * @inheritDoc
     */
    public function defineRules(): array
    {
        $rules = parent::defineRules();

        $rules[] = [['accessToken'], 'required'];

        $contact = $this->getFormSettingValue('contact');
        $deal = $this->getFormSettingValue('deal');

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

        return $rules;
    }

    public function fetchFormSettings(): IntegrationFormSettings
    {
        $settings = [];
        $dealPipelinesOptions = [];
        $dealStageOptions = [];

        try {
            // Just fetch the forms and their fields
            if (Craft::$app->getRequest()->getParam('refreshForms')) {
                // Reset the forms
                $settings['forms'] = [];

                $forms = $this->request('GET', 'forms/v2/forms');

                foreach ($forms as $form) {
                    $settings['forms'][] = new IntegrationCollection([
                        'id' => $form['portalId'] . '__' . $form['guid'],
                        'name' => $form['name'],
                        'fields' => $this->_getFormFields($form),
                    ]);
                }

                // Sort forms by name
                usort($settings['forms'], function($a, $b) {
                    return strcmp($a['name'], $b['name']);
                });
            } else {
                $response = $this->request('GET', 'crm/v3/pipelines/deals');
                $pipelines = $response['results'] ?? [];

                foreach ($pipelines as $pipeline) {
                    $dealPipelinesOptions[] = [
                        'label' => $pipeline['label'],
                        'value' => $pipeline['id'],
                    ];

                    $stages = $pipeline['stages'] ?? [];

                    foreach ($stages as $stage) {
                        $dealStageOptions[] = [
                            'label' => $pipeline['label'] . ': ' . $stage['label'],
                            'value' => $stage['id'],
                        ];
                    }
                }

                // Get Contacts fields
                $response = $this->request('GET', 'crm/v3/properties/contacts');
                $fields = $response['results'] ?? [];

                $contactFields = array_merge([
                    new IntegrationField([
                        'handle' => 'email',
                        'name' => Craft::t('formie', 'Email'),
                        'required' => true,
                    ]),
                ], $this->_getCustomFields($fields, ['email']));

                // Get Companies fields
                $response = $this->request('GET', 'crm/v3/properties/companies');
                $fields = $response['results'] ?? [];

                $companyFields = array_merge([
                    new IntegrationField([
                        'handle' => 'name',
                        'name' => Craft::t('formie', 'Name'),
                        'required' => true,
                    ]),
                ], $this->_getCustomFields($fields, ['name']));

                // Get Deals fields
                $response = $this->request('GET', 'crm/v3/properties/deals');
                $fields = $response['results'] ?? [];

                $dealFields = array_merge([
                    new IntegrationField([
                        'handle' => 'dealname',
                        'name' => Craft::t('formie', 'Deal Name'),
                        'required' => true,
                    ]),
                    new IntegrationField([
                        'handle' => 'pipeline',
                        'name' => Craft::t('formie', 'Deal Pipeline'),
                        'required' => true,
                        'options' => [
                            'label' => Craft::t('formie', 'Pipelines'),
                            'options' => $dealPipelinesOptions,
                        ],
                    ]),
                    new IntegrationField([
                        'handle' => 'dealstage',
                        'name' => Craft::t('formie', 'Deal Stage'),
                        'required' => true,
                        'options' => [
                            'label' => Craft::t('formie', 'Stages'),
                            'options' => $dealStageOptions,
                        ],
                    ]),
                ], $this->_getCustomFields($fields, ['dealname', 'pipeline', 'dealstage']));

                $settings = [
                    'contact' => $contactFields,
                    'deal' => $dealFields,
                    'company' => $companyFields,
                ];
            }
        } catch (Throwable $e) {
            Integration::apiError($this, $e);
        }

        // Because we have split settings for partial settings fetches, enssure we populate settings from cache
        // So we need to unserialize the cached form settings, and combine with any new settings and return
        $cachedSettings = $this->cache['settings'] ?? [];

        if ($cachedSettings) {
            $formSettings = new IntegrationFormSettings();
            $formSettings->unserialize($cachedSettings);
            $settings = array_merge($formSettings->collections, $settings);
        }

        return new IntegrationFormSettings($settings);
    }

    public function sendPayload(Submission $submission): bool
    {
        try {
            $contactValues = $this->getFieldMappingValues($submission, $this->contactFieldMapping, 'contact');
            $dealValues = $this->getFieldMappingValues($submission, $this->dealFieldMapping, 'deal');
            $companyValues = $this->getFieldMappingValues($submission, $this->companyFieldMapping, 'company');
            $formValues = $this->getFieldMappingValues($submission, $this->formFieldMapping, 'forms');

            $contactId = null;

            if ($this->mapToContact) {
                $email = ArrayHelper::getValue($contactValues, 'email');

                // Prepare the payload for HubSpot, required for v1 API
                $contactPayload = [];

                foreach ($contactValues as $key => $value) {
                    $contactPayload['properties'][] = [
                        'property' => $key,
                        'value' => $value,
                    ];
                }

                // Create or update the contact
                $response = $this->deliverPayload($submission, "contacts/v1/contact/createOrUpdate/email/{$email}", $contactPayload);

                if ($response === false) {
                    return true;
                }

                $contactId = $response['vid'] ?? '';

                if (!$contactId) {
                    Integration::error($this, Craft::t('formie', 'Missing return “contactId” {response}. Sent payload {payload}', [
                        'response' => Json::encode($response),
                        'payload' => Json::encode($contactPayload),
                    ]), true);

                    return false;
                }
            }

            if ($this->mapToDeal) {
                $dealPayload = [];

                if ($contactId) {
                    $dealPayload = [
                        'associations' => [
                            'associatedVids' => [$contactId],
                        ],
                    ];
                }

                foreach ($dealValues as $key => $value) {
                    $dealPayload['properties'][] = [
                        'name' => $key,
                        'value' => $value,
                    ];
                }

                $response = $this->deliverPayload($submission, 'deals/v1/deal', $dealPayload);

                if ($response === false) {
                    return true;
                }

                $dealId = $response['dealId'] ?? '';

                if (!$dealId) {
                    Integration::error($this, Craft::t('formie', 'Missing return “dealId” {response}. Sent payload {payload}', [
                        'response' => Json::encode($response),
                        'payload' => Json::encode($dealPayload),
                    ]), true);

                    return false;
                }
            }

            if ($this->mapToCompany) {
                $companyPayload = [
                    'properties' => $companyValues,
                ];

                $companyName = $companyValues['name'] ?? null;

                // Company Name is required to match against
                if (!$companyName) {
                    Integration::error($this, Craft::t('formie', 'Invalid companyName'), true);

                    return false;
                }

                // Find existing company
                $response = $this->request('POST', 'crm/v3/objects/companies/search', [
                    'json' => [
                        'filterGroups' => [
                            [
                                'filters' => [
                                    [
                                        'operator' => 'EQ',
                                        'propertyName' => 'name',
                                        'value' => $companyName,
                                    ],
                                ],
                            ],
                        ],
                    ],
                ]);

                $existingCompanyId = $response['results'][0]['id'] ?? '';

                // Update or create
                if ($existingCompanyId) {
                    $response = $this->deliverPayload($submission, "crm/v3/objects/companies/{$existingCompanyId}", $companyPayload, 'PATCH');
                } else {
                    $response = $this->deliverPayload($submission, 'crm/v3/objects/companies', $companyPayload);
                }

                if ($response === false) {
                    return true;
                }

                $companyId = $response['id'] ?? '';

                if (!$companyId) {
                    Integration::error($this, Craft::t('formie', 'Missing return “companyId” {response}. Sent payload {payload}', [
                        'response' => Json::encode($response),
                        'payload' => Json::encode($companyPayload),
                    ]), true);

                    return false;
                }
            }

            if ($this->mapToForm) {
                // Prepare the payload for HubSpot, required for v1 API
                $formPayload = [];

                // Handle GDPR fields
                $legalConsentOptions = ArrayHelper::remove($formValues, 'legalConsentOptions');

                if ($legalConsentOptions) {
                    $formPayload['legalConsentOptions'] = [
                        'consent' => [
                            'consentToProcess' => true,
                            'text' => 'I consent',
                        ],
                    ];
                }

                foreach ($formValues as $key => $value) {
                    // Don't include the tracking ID, it's invalid to HubSpot
                    if ($key === 'trackingID') {
                        continue;
                    }

                    $formPayload['fields'][] = [
                        'name' => $key,
                        'value' => $value,
                    ];
                }

                // Setup Hubspot's context
                // TODO: change this when we refactor integrations to allow arbitrary storing of extra data at submission time
                $hutk = $formValues['trackingID'] ?? $_COOKIE['hubspotutk'] ?? '';

                if ($hutk) {
                    $formPayload['context']['hutk'] = $hutk;
                }

                $formPayload['context']['ipAddress'] = $this->ipAddress;
                $formPayload['context']['pageUri'] = $this->referrer;

                [$portalId, $formGuid] = explode('__', $this->formId);

                // Bloody HubSpot have old APIs, so they require a separate endpoint
                $endpoint = "submissions/v3/integration/submit/${portalId}/${formGuid}";
                $payload = $formPayload;
                $method = 'POST';

                // Allow events to cancel sending
                if (!$this->beforeSendPayload($submission, $endpoint, $payload, $method)) {
                    return true;
                }

                $response = $this->getFormsClient()->request($method, ltrim($endpoint, '/'), [
                    'json' => $payload,
                ]);

                $response = Json::decode((string)$response->getBody());

                // Allow events to say the response is invalid
                if (!$this->afterSendPayload($submission, $endpoint, $payload, $method, $response)) {
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
            $response = $this->request('GET', 'crm/v3/properties/contacts');
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

        $accessToken = App::parseEnv($this->accessToken);

        return $this->_client = Craft::createGuzzleClient([
            'base_uri' => 'https://api.hubapi.com/',
            'headers' => [
                'Authorization' => 'Bearer ' . $accessToken,
                'Content-Type' => 'application/json',
            ],
        ]);
    }

    public function getFormsClient(): Client
    {
        if ($this->_formsClient) {
            return $this->_formsClient;
        }

        return $this->_formsClient = Craft::createGuzzleClient([
            'base_uri' => 'https://api.hsforms.com/',
        ]);
    }

    public function getFieldMappingValues(Submission $submission, $fieldMapping, $fieldSettings = [])
    {
        // When mapping to forms, the field settings will be an array of `IntegrationCollection` objects.
        // So we need to select the form's settings that we're mapping to and return just the field.
        if ($fieldSettings === 'forms') {
            $collections = $this->getFormSettingValue($fieldSettings);

            foreach ($collections as $collection) {
                if ($collection->id === $this->formId) {
                    $fieldSettings =  $collection->fields;
                }
            }
        }

        return parent::getFieldMappingValues($submission, $fieldMapping, $fieldSettings);
    }


    // Private Methods
    // =========================================================================

    private function _convertFieldType($fieldType)
    {
        $fieldTypes = [
            'checkbox' => IntegrationField::TYPE_ARRAY,
            'booleancheckbox' => IntegrationField::TYPE_BOOLEAN,
            'date' => IntegrationField::TYPE_DATE,
            'number' => IntegrationField::TYPE_NUMBER,
        ];

        return $fieldTypes[$fieldType] ?? IntegrationField::TYPE_STRING;
    }

    private function _getCustomFields($fields, $excludeNames = []): array
    {
        $customFields = [];

        $supportedFields = [
            'string',
            'enumeration',
            'datetime',
            'date',
            'phone_number',
            'bool',
            'number',
        ];

        foreach ($fields as $key => $field) {
            $readOnlyValue = $field['modificationMetadata']['readOnlyValue'] ?? false;
            $calculated = $field['calculated'] ?? false;

            if ($readOnlyValue || $calculated) {
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

            // Add in any options for some fields
            $options = [];
            $fieldOptions = $field['options'] ?? [];

            foreach ($fieldOptions as $fieldOption) {
                $options[] = [
                    'label' => $fieldOption['label'],
                    'value' => $fieldOption['value'],
                ];
            }

            if ($options) {
                $options = [
                    'label' => $field['label'],
                    'options' => $options,
                ];
            }

            $customFields[] = new IntegrationField([
                'handle' => $field['name'],
                'name' => $field['label'],
                'type' => $this->_convertFieldType($field['fieldType']),
                'sourceType' => $field['fieldType'],
                'options' => $options,
            ]);
        }

        return $customFields;
    }

    private function _getFormFields($form): array
    {
        $fields = [];

        $extraFields = [
            new IntegrationField([
                'handle' => 'trackingID',
                'name' => Craft::t('formie', 'Tracking ID'),
            ]),
        ];

        $formFieldGroups = $form['formFieldGroups'] ?? [];

        foreach ($formFieldGroups as $formFieldGroup) {
            $formFields = $formFieldGroup['fields'] ?? [];

            foreach ($formFields as $formField) {
                $fields[] = $formField;
            }
        }

        // Extra handling for GDPR fields
        $metaData = $form['metaData'] ?? [];

        foreach ($metaData as $data) {
            if ($data['name'] === 'legalConsentOptions') {
                $extraFields[] = new IntegrationField([
                    'handle' => 'legalConsentOptions',
                    'name' => Craft::t('formie', 'Legal Consent Options'),
                ]);
            }
        }

        return array_merge($extraFields, $this->_getCustomFields($fields));
    }
}
