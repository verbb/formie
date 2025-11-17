<?php
namespace verbb\formie\integrations\crm;

use verbb\formie\base\Crm;
use verbb\formie\base\Integration;
use verbb\formie\elements\Submission;
use verbb\formie\events\ModifyFieldIntegrationValueEvent;
use verbb\formie\helpers\ArrayHelper;
use verbb\formie\helpers\StringHelper;
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

    public static function displayName(): string
    {
        return Craft::t('formie', 'HubSpot');
    }

    public static function convertValueForIntegration(mixed $value, IntegrationField $integrationField): mixed
    {
        // If setting checkboxes values to a static value, ensure it's sent as a single value.
        // This won't be picked up in `EVENT_MODIFY_FIELD_MAPPING_VALUE` because it's not mapped to a field.
        if ($integrationField->getType() === IntegrationField::TYPE_ARRAY) {
            return $value;
        }

        // Handle static values being set for dates, which should be a timestamp (submission `dateCreated`)
        if ($integrationField->getType() === IntegrationField::TYPE_DATETIME) {
            // HubSpot needs this as a timestamp value.
            if ($value instanceof DateTime) {
                $date = clone $value;

                return (string)($date->getTimestamp() * 1000);
            }
        }

        return parent::convertValueForIntegration($value, $integrationField);
    }

    // Properties
    // =========================================================================

    public ?string $accessToken = null;
    public bool $mapToContact = false;
    public bool $mapToDeal = false;
    public bool $mapToCompany = false;
    public bool $mapToTicket = false;
    public bool $mapToForm = false;
    public ?array $contactFieldMapping = null;
    public ?array $dealFieldMapping = null;
    public ?array $companyFieldMapping = null;
    public ?array $ticketFieldMapping = null;
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

            // Special handling for dates for HubSpot
            if ($event->integrationField->getType() === IntegrationField::TYPE_DATETIME) {
                // HubSpot needs this as a timestamp value.
                if ($event->rawValue instanceof DateTime) {
                    $date = clone $event->rawValue;

                    $event->value = (string)($date->getTimestamp() * 1000);
                } else {
                    // Always return the raw value for all other instances. We might be passing in the timestamp
                    $event->value = $event->rawValue;
                }
            }

            if ($event->integrationField->sourceType === 'file' && $event->integration->mapToForm) {
                // For HubSpot File fields, we need to handle content differently
                if (is_string($event->value)) {
                    $event->value = array_map('trim', explode(',', $event->value));
                }

                // Let our form-field processing handling know about it needs to be treated differently
                // Prevent changing multiple times, as this event is called
                if (is_array($event->value) && !isset($event->value['FILE_UPLOAD_DATA'])) {
                    $event->value = ['FILE_UPLOAD_DATA' => $event->value];
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
                // Get Contacts fields
                if ($this->mapToContact) {
                    $response = $this->request('GET', 'crm/v3/properties/contacts');
                    $fields = $response['results'] ?? [];

                    $settings['contact'] = array_merge([
                        new IntegrationField([
                            'handle' => 'email',
                            'name' => Craft::t('formie', 'Email'),
                            'required' => true,
                        ]),
                    ], $this->_getCustomFields($fields, ['email']));
                }

                // Get Companies fields
                if ($this->mapToCompany) {
                    $response = $this->request('GET', 'crm/v3/properties/companies');
                    $fields = $response['results'] ?? [];

                    $settings['company'] = array_merge([
                        new IntegrationField([
                            'handle' => 'name',
                            'name' => Craft::t('formie', 'Name'),
                            'required' => true,
                        ]),
                    ], $this->_getCustomFields($fields, ['name']));
                }

                // Get Tickets fields
                if ($this->mapToTicket) {
                    $response = $this->request('GET', 'crm/v3/properties/tickets');
                    $fields = $response['results'] ?? [];

                    $ticketPipelineOptions = [];
                    $ticketStageOptions = [];

                    $response = $this->request('GET', 'crm/v3/pipelines/tickets');
                    $pipelines = $response['results'] ?? [];

                    foreach ($pipelines as $pipeline) {
                        $ticketPipelineOptions[] = [
                            'label' => $pipeline['label'],
                            'value' => $pipeline['id'],
                        ];

                        foreach ($pipeline['stages'] ?? [] as $stage) {
                            $ticketStageOptions[] = [
                                'label' => $pipeline['label'] . ': ' . $stage['label'],
                                'value' => $stage['id'],
                            ];
                        }
                    }

                    $settings['ticket'] = array_merge([
                        new IntegrationField([
                            'handle' => 'subject',
                            'name' => Craft::t('formie', 'Ticket Subject'),
                            'required' => true,
                        ]),
                        new IntegrationField([
                            'handle' => 'hs_pipeline',
                            'name' => Craft::t('formie', 'Pipeline'),
                            'required' => true,
                            'options' => [
                                'label' => Craft::t('formie', 'Pipelines'),
                                'options' => $ticketPipelineOptions,
                            ],
                        ]),
                        new IntegrationField([
                            'handle' => 'hs_pipeline_stage',
                            'name' => Craft::t('formie', 'Pipeline Stage'),
                            'required' => true,
                            'options' => [
                                'label' => Craft::t('formie', 'Stages'),
                                'options' => $ticketStageOptions,
                            ],
                        ]),
                    ], $this->_getCustomFields($fields, ['subject', 'hs_pipeline', 'hs_pipeline_stage']));
                }

                // Get Deals fields
                if ($this->mapToDeal) {
                    $dealPipelinesOptions = [];
                    $dealStageOptions = [];
                    
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

                    $response = $this->request('GET', 'crm/v3/properties/deals');
                    $fields = $response['results'] ?? [];

                    $settings['deal'] = array_merge([
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
                }
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
            $ticketValues = $this->getFieldMappingValues($submission, $this->ticketFieldMapping, 'ticket');
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

            if ($this->mapToTicket) {
                $ticketPayload = [
                    'properties' => $ticketValues,
                ];

                $ticketSubject = $ticketValues['subject'] ?? null;

                // Ticket Name is required to match against
                if (!$ticketSubject) {
                    Integration::error($this, Craft::t('formie', 'Invalid subject'), true);

                    return false;
                }

                // Find existing ticket
                $response = $this->request('POST', 'crm/v3/objects/tickets/search', [
                    'json' => [
                        'filterGroups' => [
                            [
                                'filters' => [
                                    [
                                        'operator' => 'EQ',
                                        'propertyName' => 'subject',
                                        'value' => $ticketSubject,
                                    ],
                                ],
                            ],
                        ],
                    ],
                ]);

                $existingTicketId = $response['results'][0]['id'] ?? '';

                // Update or create
                if ($existingTicketId) {
                    $response = $this->deliverPayload($submission, "crm/v3/objects/tickets/{$existingTicketId}", $ticketPayload, 'PATCH');
                } else {
                    $response = $this->deliverPayload($submission, 'crm/v3/objects/tickets', $ticketPayload);
                }

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

            if ($this->mapToForm) {
                // Prepare the payload for HubSpot, required for v1 API
                $formPayload = [];

                // Handle GDPR fields
                $legalConsentOptionsMarketing = ArrayHelper::remove($formValues, 'legalConsentOptionsMarketing');
                $legalConsentOptionsProcessing = ArrayHelper::remove($formValues, 'legalConsentOptionsProcessing');

                // Don't forget to cast as boolean, as in `EVENT_MODIFY_FIELD_MAPPING_VALUE` we cast boolean as string.
                // Tested separately to the above when not mapped at all.
                $legalConsentOptionsMarketing = StringHelper::toBoolean((string)$legalConsentOptionsMarketing);
                $legalConsentOptionsProcessing = StringHelper::toBoolean((string)$legalConsentOptionsProcessing);

                if ($legalConsentOptionsProcessing || $legalConsentOptionsMarketing) {
                    // Don't forget to cast as boolean, as in `EVENT_MODIFY_FIELD_MAPPING_VALUE` we cast boolean as string.
                    // Tested separately to the above when not mapped at all.
                    $legalConsentOptionsMarketing = StringHelper::toBoolean($legalConsentOptionsMarketing);
                    $legalConsentOptionsProcessing = StringHelper::toBoolean($legalConsentOptionsProcessing);

                    if ($legalConsentOptionsProcessing || $legalConsentOptionsMarketing) {
                        $legalConsentOptionsMarketingField = $this->_getField('forms', $this->formId, 'legalConsentOptionsMarketing');
                        $legalConsentOptionsProcessingField = $this->_getField('forms', $this->formId, 'legalConsentOptionsProcessing');

                        $formPayload['legalConsentOptions'] = [
                            'consent' => [
                                'consentToProcess' => true,
                                'text' => $legalConsentOptionsProcessingField['data']['text'] ?? '',
                            ],
                        ];

                        if ($legalConsentOptionsMarketing) {
                            $formPayload['legalConsentOptions']['consent']['communications'] = [
                                [
                                    'value' => true,
                                    'subscriptionTypeId' => $legalConsentOptionsMarketingField['data']['typeId'] ?? '',
                                    'text' => $legalConsentOptionsMarketingField['data']['text'] ?? '',
                                ]
                            ];
                        }
                    }
                }

                // Extract some values that shouldn't be part of the form payload
                $formPayload['context']['pageUri'] = ArrayHelper::remove($formValues, 'pageUri') ?? $this->context['referrer'] ?? null;
                $formPayload['context']['pageName'] = ArrayHelper::remove($formValues, 'pageName');

                foreach ($formValues as $key => $value) {
                    // Don't include the tracking ID, it's invalid to HubSpot
                    if ($key === 'trackingID') {
                        continue;
                    }

                    // Get the object type, use `CONTACT` or `COMPANY` for legacy, and default to `CONTACT`
                    if (!str_contains($key, '.')) {
                        $key = "CONTACT.$key";
                    }

                    $handleParts = explode('.', $key);
                    $objectTypeId = str_replace(['CONTACT', 'COMPANY'], ['0-1', '0-2'], ($handleParts[0] ?? 'CONTACT'));

                    // Special-handling for some fields.
                    if (is_array($value) && isset($value['FILE_UPLOAD_DATA'])) {
                        foreach ($value['FILE_UPLOAD_DATA'] as $subValue) {
                            $formPayload['fields'][] = [
                                'objectTypeId' => $objectTypeId,
                                'name' => $handleParts[1] ?? '',
                                'value' => strtok($subValue, '?'),
                            ];
                        }
                    } else {
                        $formPayload['fields'][] = [
                            'objectTypeId' => $objectTypeId,
                            'name' => $handleParts[1] ?? '',
                            'value' => $value,
                        ];
                    }
                }

                // Setup Hubspot's context, if we're mapping it, or if it's automatically saved in context
                $hutk = $formValues['trackingID'] ?? $this->context['hubspotutk'] ?? '';

                if ($hutk) {
                    $formPayload['context']['hutk'] = $hutk;
                }

                $formPayload['context']['ipAddress'] = $this->context['ipAddress'] ?? null;

                [$portalId, $formGuid] = explode('__', $this->formId);

                // Bloody HubSpot have old APIs, so they require a separate endpoint
                $endpoint = "submissions/v3/integration/submit/{$portalId}/{$formGuid}";
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

    public function getFormsClient(): Client
    {
        if ($this->_formsClient) {
            return $this->_formsClient;
        }

        return $this->_formsClient = Craft::createGuzzleClient([
            'base_uri' => 'https://api.hsforms.com/',
        ]);
    }

    public function populateContext(): void
    {
        parent::populateContext();

        // Allow us to save the tracking cookie at the time of submission, so grab later
        $this->context['hubspotutk'] = $_COOKIE['hubspotutk'] ?? null;
    }

    public function getFieldMappingValues(Submission $submission, ?array $fieldMapping, mixed $fieldSettings = [])
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


    // Protected Methods
    // =========================================================================

    protected function defineRules(): array
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

    protected function defineClient(): Client
    {
        $accessToken = App::parseEnv($this->accessToken);

        return Craft::createGuzzleClient([
            'base_uri' => 'https://api.hubapi.com/',
            'headers' => [
                'Authorization' => 'Bearer ' . $accessToken,
                'Content-Type' => 'application/json',
            ],
        ]);
    }


    // Private Methods
    // =========================================================================

    private function _convertFieldType(string $fieldType): string
    {
        $fieldTypes = [
            'checkbox' => IntegrationField::TYPE_ARRAY,
            'booleancheckbox' => IntegrationField::TYPE_BOOLEAN,
            'date' => IntegrationField::TYPE_DATE,
            'number' => IntegrationField::TYPE_NUMBER,
        ];

        return $fieldTypes[$fieldType] ?? IntegrationField::TYPE_STRING;
    }

    private function _getCustomFields(array $fields, array $excludeNames = []): array
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
            $options = array_map(function($fieldOption) {
                return [
                    'label' => $fieldOption['label'],
                    'value' => $fieldOption['value'],
                ];
            }, $field['options'] ?? []);

            if ($options) {
                $options = [
                    'label' => $field['label'],
                    'options' => $options,
                ];
            }

            $customFields[] = new IntegrationField([
                'handle' => $field['name'],
                'name' => $field['label'] ?: $field['name'],
                'type' => $this->_convertFieldType($field['fieldType']),
                'sourceType' => $field['fieldType'],
                'options' => $options,
            ]);
        }

        return $customFields;
    }

    private function _getField(string $dataHandle, string $dataId, string $fieldHandle): array
    {
        $objects = $this->cache['settings'][$dataHandle] ?? [];

        foreach ($objects as $object) {
            if ($object['id'] === $dataId) {
                $fields = $object['fields'] ?? [];

                foreach ($fields as $field) {
                    if ($field['handle'] === $fieldHandle) {
                        return $field;
                    }
                }
            }
        }

        return [];
    }

    private function _getFormFields($form): array
    {
        $fields = [];

        $extraFields = [
            new IntegrationField([
                'handle' => 'trackingID',
                'name' => Craft::t('formie', 'Tracking ID'),
            ]),
            new IntegrationField([
                'handle' => 'pageUri',
                'name' => Craft::t('formie', 'Page URI'),
            ]),
            new IntegrationField([
                'handle' => 'pageName',
                'name' => Craft::t('formie', 'Page Name'),
            ]),
        ];

        $formFieldGroups = $form['formFieldGroups'] ?? [];

        foreach ($formFieldGroups as $formFieldGroup) {
            $formFields = $formFieldGroup['fields'] ?? [];

            foreach ($formFields as $formField) {
                // Include the group name in the label for clarity to match HubSpot UI.
                $formField['label'] = Craft::t('formie', '{label} ({group} property)', [
                    'label' => $formField['label'],
                    'group' => StringHelper::toTitleCase($formField['propertyObjectType']),
                ]);
                
                // Ensure that we prefix items with their correct object group
                // While we don't need this conditional technically, removing it means all form mappings would be gone
                // due to HubSpot treating every field as a CONTACT field by default, but we haven't included that in mapping.
                // TODO: run a migration for all form mappings to update the `CONTACT.name` prefix.
                if ($formField['propertyObjectType'] !== 'CONTACT') {
                    $formField['name'] = $formField['propertyObjectType'] . '.' . $formField['name'];
                }

                $fields[] = $formField;

                // Check for "dependentField" (conditional fields) to include
                $dependentFieldFilters = $formField['dependentFieldFilters'] ?? [];

                foreach ($dependentFieldFilters as $dependentFieldFilter) {
                    $dependentFormField = $dependentFieldFilter['dependentFormField'] ?? null;

                    if ($dependentFormField) {
                        $fields[] = $dependentFormField;
                    }
                }
            }
        }

        // Extra handling for GDPR fields
        $metaData = $form['metaData'] ?? [];

        foreach ($metaData as $data) {
            if ($data['name'] === 'legalConsentOptions') {
                $consentData = Json::decode($data['value']);

                $processingConsentType = $consentData['processingConsentType'] ?? 'REQUIRED_CHECKBOX';

                $extraFields[] = new IntegrationField([
                    'handle' => 'legalConsentOptionsMarketing',
                    'name' => Craft::t('formie', 'Legal Consent (Marketing)'),
                    'type' => IntegrationField::TYPE_BOOLEAN,
                    'options' => [
                        'label' => Craft::t('formie', 'Consent'),
                        'options' => [
                            ['label' => Craft::t('formie', 'True'), 'value' => 'true'],
                            ['label' => Craft::t('formie', 'False'), 'value' => 'false']
                        ]
                    ],
                    'data' => [
                        'text' => strip_tags($consentData['communicationConsentCheckboxes'][0]['label'] ?? ''),
                        'typeId' => $consentData['communicationConsentCheckboxes'][0]['communicationTypeId'] ?? '',
                    ],
                ]);

                if ($processingConsentType === 'REQUIRED_CHECKBOX') {
                    $extraFields[] = new IntegrationField([
                        'handle' => 'legalConsentOptionsProcessing',
                        'name' => Craft::t('formie', 'Legal Consent (Processing)'),
                        'type' => IntegrationField::TYPE_BOOLEAN,
                        'options' => [
                            'label' => Craft::t('formie', 'Consent'),
                            'options' => [
                                ['label' => Craft::t('formie', 'True'), 'value' => 'true'],
                                ['label' => Craft::t('formie', 'False'), 'value' => 'false']
                            ]
                        ],
                        'data' => [
                            'text' => strip_tags($consentData['processingConsentCheckboxLabel'] ?? ''),
                        ],
                    ]);
                }
            }
        }

        return array_merge($extraFields, $this->_getCustomFields($fields));
    }
}
