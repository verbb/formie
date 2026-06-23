<?php
namespace verbb\formie\integrations\crm;

use verbb\formie\base\Crm;
use verbb\formie\base\FormInterface;
use verbb\formie\base\Integration;
use verbb\formie\elements\Submission;
use verbb\formie\events\ModifyFieldIntegrationValueEvent;
use verbb\formie\fields\values\DateFieldValue;
use verbb\formie\fields\values\FieldValueInterface;
use verbb\formie\helpers\ArrayHelper;
use verbb\formie\helpers\SchemaHelper;
use verbb\formie\helpers\StringHelper;
use verbb\formie\models\IntegrationCollection;
use verbb\formie\models\IntegrationField;
use verbb\formie\models\IntegrationFormSettings;

use Craft;
use craft\helpers\App;
use craft\helpers\DateTimeHelper;
use craft\helpers\Json;

use yii\base\Event;

use GuzzleHttp\Client;

use DateTime;
use Throwable;

class HubSpot extends Crm
{
    // Constants
    // =========================================================================

    private const STANDARD_OBJECT_TYPE_IDS = [
        'CONTACT' => '0-1',
        'COMPANY' => '0-2',
        'DEAL' => '0-3',
        'TICKET' => '0-5',
    ];

    // Static Methods
    // =========================================================================

    public static function displayName(): string
    {
        return 'HubSpot';
    }

    protected static function defineOptionSources(): array
    {
        $shared = [
            'storage' => 'objects',
            'collectionLabel' => Craft::t('formie', 'Object'),
            'collectionInstructions' => Craft::t('formie', 'Choose the CRM object that owns the field.'),
            'collectionPlaceholder' => Craft::t('formie', 'Select an object'),
            'remoteHandleLabel' => Craft::t('formie', 'Option Source'),
            'remoteHandleInstructions' => Craft::t('formie', 'Choose which remote field supplies the options.'),
            'remoteHandlePlaceholder' => Craft::t('formie', 'Select an option source'),
            'collectionRequiredMessage' => Craft::t('formie', 'Select a CRM object.'),
            'remoteHandleRequiredMessage' => Craft::t('formie', 'Select an option source.'),
            'emptyCollectionsWarning' => Craft::t('formie', 'No CRM objects are available. Refresh the integration field mapping first.'),
            'emptySourcesWarning' => Craft::t('formie', 'No option sources are available. Refresh the integration field mapping first.'),
        ];

        return [
            array_merge($shared, [
                'handle' => 'hubspot-forms',
                'label' => Craft::t('formie', 'Form Fields'),
                'storage' => 'collections',
                'collectionKey' => 'forms',
                'collectionLabel' => Craft::t('formie', 'Form'),
                'collectionInstructions' => Craft::t('formie', 'Choose the HubSpot form that owns the field.'),
                'collectionPlaceholder' => Craft::t('formie', 'Select a form'),
                'collectionRequiredMessage' => Craft::t('formie', 'Select a HubSpot form.'),
                'emptyCollectionsWarning' => Craft::t('formie', 'No HubSpot forms are available. Refresh the integration forms first.'),
                'emptySourcesWarning' => Craft::t('formie', 'No HubSpot form option sources are available. Refresh the integration forms first.'),
            ]),
            array_merge($shared, [
                'handle' => 'hubspot-properties',
                'label' => Craft::t('formie', 'CRM Properties'),
                'objectKeys' => ['contact', 'company', 'deal', 'ticket'],
                'objectLabels' => [
                    'contact' => Craft::t('formie', 'Contact'),
                    'company' => Craft::t('formie', 'Company'),
                    'deal' => Craft::t('formie', 'Deal'),
                    'ticket' => Craft::t('formie', 'Ticket'),
                ],
            ]),
        ];
    }

    /**
     * Normalize a value to DateTime for HubSpot date/datetime fields.
     * HubSpot uses millisecond timestamps; values in that range are converted from ms to seconds before parsing.
     * All other normalization (FieldValueInterface, objects, strings) is delegated to DateFieldValue::toDateTime().
     */
    private static function _valueToDateTime(mixed $value): ?DateTime
    {
        if ($value instanceof DateTime) {
            return clone $value;
        }
        // HubSpot-specific: numeric ms timestamps (e.g. from JS or API)
        if (is_numeric($value)) {
            $num = (float)$value;
            if ($num >= 1e12 && $num < 1e15) {
                $seconds = (int)round($num / 1000);
                $date = DateTimeHelper::toDateTime('@' . $seconds);
                return $date ?: null;
            }
            if ($num < 0 || $num >= 1e15) {
                return null;
            }
        }
        return DateFieldValue::toDateTime($value);
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
    private ?Client $_uploadClient = null;


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
            // HubSpot-specific shaping: single place for all mapping value modifications.

            if ($event->integrationField->getType() === IntegrationField::TYPE_BOOLEAN) {
                $event->value = ($event->value === true || $event->value === 'true') ? 'true' : 'false';
            }

            if ($event->integrationField->getType() === IntegrationField::TYPE_ARRAY) {
                if (is_array($event->value)) {
                    $event->value = array_filter($event->value);

                    $event->value = array_map(function ($v): string {
                        if (is_scalar($v)) {
                            return (string)$v;
                        }

                        if (is_array($v)) {
                            return implode(';', array_map('strval', $v));
                        }

                        if ($v instanceof FieldValueInterface) {
                            return implode(';', array_map('strval', $v->toValueArray()));
                        }

                        if (is_object($v)) {
                            if (method_exists($v, '__toString')) {
                                return $v->__toString();
                            }

                            return Json::encode($v);
                        }

                        return (string)$v;
                    }, $event->value);
                    $event->value = ArrayHelper::recursiveImplode($event->value, ';');
                    $event->value = str_replace('&nbsp;', ' ', $event->value);
                }
            }

            if ($event->integrationField->getType() === IntegrationField::TYPE_DATE) {
                if ($event->rawValue instanceof DateTime) {
                    $date = clone $event->rawValue;
                    $date->setTime(0, 0, 0);
                    $event->value = (string)($date->getTimestamp() * 1000);
                } else {
                    $date = self::_valueToDateTime($event->rawValue ?? $event->value);
                    $event->value = $date ? (string)($date->getTimestamp() * 1000) : $event->rawValue;
                }
            }

            if ($event->integrationField->getType() === IntegrationField::TYPE_DATETIME) {
                $date = null;

                if ($event->rawValue instanceof DateTime) {
                    $date = clone $event->rawValue;
                } elseif ($event->value instanceof DateTime) {
                    $date = clone $event->value;
                } else {
                    $date = self::_valueToDateTime($event->value);
                }

                if ($date) {
                    $event->value = (string)($date->getTimestamp() * 1000);
                }
            }

            if ($event->integrationField->sourceType === 'file' && $event->integration->mapToForm) {
                $fallbackValues = [];
                $values = [];

                if (is_array($event->value) && isset($event->value['FILE_UPLOAD_DATA'])) {
                    $fallbackValues = array_filter($event->value['FILE_UPLOAD_DATA']);
                } else if (is_array($event->value)) {
                    $fallbackValues = array_filter($event->value);
                } else if (is_string($event->value)) {
                    $fallbackValues = array_filter(array_map('trim', explode(',', $event->value)));
                }

                if ($event->rawValue && method_exists($event->rawValue, 'all')) {
                    foreach ($event->rawValue->all() as $asset) {
                        if (!$asset instanceof Asset) {
                            continue;
                        }

                        $value = $this->_getHubSpotFileValue($asset);

                        if ($value) {
                            $values[] = $value;
                        }
                    }
                }

                if (!$values) {
                    $values = $fallbackValues;
                }

                // Let our form-field processing handling know about it needs to be treated differently
                // Prevent changing multiple times, as this event is called
                if ($values && !isset($values['FILE_UPLOAD_DATA'])) {
                    $event->value = ['FILE_UPLOAD_DATA' => array_values(array_filter($values))];
                }
            }
        });
    }

    public function getDescription(): string
    {
        return Craft::t('formie', 'Manage your {name} customers by providing important information on their conversion on your site.', ['name' => static::displayName()]);
    }

    public function getFormSettingsRefreshParams(): array
    {
        return [
            'refreshForms' => true,
        ];
    }

    public function getOptionSourceRefreshParams(string $provider): array
    {
        if ($provider === 'hubspot-forms') {
            return [
                'refreshForms' => true,
            ];
        }

        return [];
    }

    public function fetchFormSettings(): IntegrationFormSettings
    {
        $settings = [];

        try {
            // Just fetch the forms and their fields
            if (Craft::$app->getRequest()->getParam('refreshForms')) {
                // Reset the forms
                $settings['forms'] = [];

                try {
                    $response = $this->request('GET', 'crm-object-schemas/v3/schemas');
                    $settings['customObjectSchemas'] = $response['results'] ?? [];
                } catch (Throwable $e) {
                    Integration::info($this, Craft::t('formie', 'Unable to fetch HubSpot custom object schemas. Custom object form fields may not resolve correctly. Error: {error}', [
                        'error' => Integration::getExceptionLogMessage($e),
                    ]));

                    $settings['customObjectSchemas'] = $this->getFormSettingValue('customObjectSchemas') ?? [];
                }

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
                if ($this->mapToContact && $this->settingsContext->dataKey === 'contact') {
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
                if ($this->mapToCompany && $this->settingsContext->dataKey === 'company') {
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
                if ($this->mapToDeal && $this->settingsContext->dataKey === 'deal') {
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
                $response = $this->deliverPayload($submission, 'contacts/v1/contact/createOrUpdate/email/' . rawurlencode((string)$email), $contactPayload);

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

                    if (!str_contains($key, '.')) {
                        $key = self::STANDARD_OBJECT_TYPE_IDS['CONTACT'] . ".$key";
                    }

                    $handleParts = explode('.', $key, 2);
                    $objectTypeId = $this->_resolveHubSpotObjectTypeId($handleParts[0] ?? self::STANDARD_OBJECT_TYPE_IDS['CONTACT']);
                    $fieldName = $handleParts[1] ?? '';

                    // Special-handling for some fields.
                    if (is_array($value) && isset($value['FILE_UPLOAD_DATA'])) {
                        foreach ($value['FILE_UPLOAD_DATA'] as $subValue) {
                            $formPayload['fields'][] = [
                                'objectTypeId' => $objectTypeId,
                                'name' => $fieldName,
                                'value' => $subValue,
                            ];
                        }
                    } else {
                        $formPayload['fields'][] = [
                            'objectTypeId' => $objectTypeId,
                            'name' => $fieldName,
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

    public function getUploadClient(): Client
    {
        if ($this->_uploadClient) {
            return $this->_uploadClient;
        }

        $accessToken = App::parseEnv($this->accessToken);

        return $this->_uploadClient = Craft::createGuzzleClient([
            'base_uri' => 'https://api.hubapi.com/',
            'headers' => [
                'Authorization' => 'Bearer ' . $accessToken,
            ],
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
            }, 'on' => [Integration::SCENARIO_FORM], 'skipOnEmpty' => false,
        ];

        $rules[] = [
            ['dealFieldMapping'], 'validateFieldMapping', 'params' => $deal, 'when' => function($model) {
                return $model->enabled && $model->mapToDeal;
            }, 'on' => [Integration::SCENARIO_FORM], 'skipOnEmpty' => false,
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

    protected function defineFormSettingsSchema(FormInterface $form): array
    {
        $schema = parent::defineFormSettingsSchema($form);
        $schema[] = SchemaHelper::lightswitchField([
            'name' => 'mapToContact',
            'label' => Craft::t('formie', 'Map to {name}', ['name' => 'Contact']),
            'instructions' => Craft::t('formie', 'Whether to map form data to {name} {label}.', ['name' => $this->displayName(), 'label' => 'Contacts']),
        ]);
        $schema[] = $this->getIntegrationFieldMappingField([
            'name' => 'contactFieldMapping',
            'if' => 'mapToContact',
            'dataLabel' => 'Contact',
            'dataKey' => 'contact',
        ]);
        $schema[] = SchemaHelper::lightswitchField([
            'name' => 'mapToDeal',
            'label' => Craft::t('formie', 'Map to {name}', ['name' => 'Deal']),
            'instructions' => Craft::t('formie', 'Whether to map form data to {name} {label}.', ['name' => $this->displayName(), 'label' => 'Deals']),
        ]);
        $schema[] = $this->getIntegrationFieldMappingField([
            'name' => 'dealFieldMapping',
            'if' => 'mapToDeal',
            'dataLabel' => 'Deal',
            'dataKey' => 'deal',
        ]);
        $schema[] = SchemaHelper::lightswitchField([
            'name' => 'mapToCompany',
            'label' => Craft::t('formie', 'Map to {name}', ['name' => 'Company']),
            'instructions' => Craft::t('formie', 'Whether to map form data to {name} {label}.', ['name' => $this->displayName(), 'label' => 'Companies']),
        ]);
        $schema[] = $this->getIntegrationFieldMappingField([
            'name' => 'companyFieldMapping',
            'if' => 'mapToCompany',
            'dataLabel' => 'Company',
            'dataKey' => 'company',
        ]);
        $schema[] = SchemaHelper::lightswitchField([
            'name' => 'mapToForm',
            'label' => Craft::t('formie', 'Map to Form'),
            'instructions' => Craft::t('formie', 'Whether to map form data to {name} {label}.', ['name' => $this->displayName(), 'label' => 'Forms']),
        ]);
        $schema[] = SchemaHelper::integrationRefreshComboboxField([
            'name' => 'formId',
            'label' => Craft::t('formie', 'Form'),
            'instructions' => Craft::t('formie', 'Select your {name} form to create submissions on.', ['name' => $this->displayName()]),
            'if' => 'mapToForm',
            'required' => true,
            'placeholder' => Craft::t('formie', 'Select an option'),
            'options' => $this->getCollectionOptions('forms'),
            'refreshParams' => [
                'refreshForms' => true,
            ],
        ]);

        $mappingSchema = $this->defineFieldMappingSchema('forms', 'formId');
        if ($mappingSchema) {
            $schema[] = $this->getIntegrationFieldMappingField([
                'name' => 'formFieldMapping',
                'if' => 'mapToForm',
                'dataLabel' => 'Form',
                'dataKey' => 'forms',
                'integrationFields' => $mappingSchema,
            ]);
        }

        return $schema;
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

    private function _getHubSpotFileValue(Asset $asset): ?string
    {
        $fs = $asset->getVolume()->getFs();
        $url = null;

        if ($fs->hasUrls) {
            $url = $asset->getUrl();
        }

        if ($url) {
            return $url;
        }

        return $this->_uploadAssetToHubSpot($asset);
    }

    private function _uploadAssetToHubSpot(Asset $asset): ?string
    {
        $path = AssetsHelper::getFullAssetFilePath($asset);
        $fs = $asset->getVolume()->getFs();
        $cleanupPath = !($fs instanceof LocalFsInterface);
        $handle = null;

        if (!is_file($path) || !is_readable($path)) {
            return null;
        }

        try {
            $handle = fopen($path, 'r');

            if ($handle === false) {
                return null;
            }

            $response = $this->getUploadClient()->request('POST', 'files/v3/files', [
                'multipart' => [
                    [
                        'name' => 'file',
                        'contents' => $handle,
                        'filename' => $asset->getFilename(),
                    ],
                    [
                        'name' => 'fileName',
                        'contents' => $asset->getFilename(),
                    ],
                    [
                        'name' => 'folderPath',
                        'contents' => '/formie',
                    ],
                    [
                        'name' => 'options',
                        'contents' => Json::encode([
                            'access' => 'PUBLIC_NOT_INDEXABLE',
                        ]),
                    ],
                ],
            ]);

            $response = Json::decode((string)$response->getBody());

            return $response['url'] ?? $response['defaultHostingUrl'] ?? null;
        } catch (Throwable $e) {
            self::apiError($this, $e, false);

            return null;
        } finally {
            if (is_resource($handle)) {
                fclose($handle);
            }

            if ($cleanupPath) {
                @unlink($path);
            }
        }
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
                'data' => $field['data'] ?? [],
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
                $formField = $this->_prepareHubSpotFormField($formField);

                // Include the group name in the label for clarity to match HubSpot UI.
                $formField['label'] = Craft::t('formie', '{label} ({group} property)', [
                    'label' => $formField['label'],
                    'group' => StringHelper::toTitleCase($formField['propertyObjectType']),
                ]);

                $fields[] = $formField;

                // Check for "dependentField" (conditional fields) to include
                $dependentFieldFilters = $formField['dependentFieldFilters'] ?? [];

                foreach ($dependentFieldFilters as $dependentFieldFilter) {
                    $dependentFormField = $dependentFieldFilter['dependentFormField'] ?? null;

                    if ($dependentFormField) {
                        $fields[] = $this->_prepareHubSpotFormField($dependentFormField);
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

    private function _getCustomObjectSchemas(): array
    {
        $schemas = $this->getFormSettingValue('customObjectSchemas');

        return is_array($schemas) ? $schemas : [];
    }

    private function _resolveHubSpotObjectTypeId(string $propertyObjectType): string
    {
        $propertyObjectType = trim($propertyObjectType);

        if ($propertyObjectType === '') {
            return self::STANDARD_OBJECT_TYPE_IDS['CONTACT'];
        }

        if (isset(self::STANDARD_OBJECT_TYPE_IDS[$propertyObjectType])) {
            return self::STANDARD_OBJECT_TYPE_IDS[$propertyObjectType];
        }

        if (preg_match('/^\d+-\d+$/', $propertyObjectType)) {
            return $propertyObjectType;
        }

        foreach ($this->_getCustomObjectSchemas() as $schema) {
            if (!is_array($schema)) {
                continue;
            }

            $objectTypeId = (string)($schema['objectTypeId'] ?? '');

            if ($objectTypeId === '') {
                continue;
            }

            $candidates = array_filter([
                $schema['name'] ?? null,
                $schema['fullyQualifiedName'] ?? null,
                $schema['labels']['singular'] ?? null,
                $schema['labels']['plural'] ?? null,
            ]);

            foreach ($candidates as $candidate) {
                if (strcasecmp((string)$candidate, $propertyObjectType) === 0) {
                    return $objectTypeId;
                }
            }
        }

        Integration::info($this, Craft::t('formie', 'Unable to resolve HubSpot object type “{type}”. Defaulting to contact.', [
            'type' => $propertyObjectType,
        ]));

        return self::STANDARD_OBJECT_TYPE_IDS['CONTACT'];
    }

    private function _prepareHubSpotFormField(array $formField): array
    {
        $propertyObjectType = (string)($formField['propertyObjectType'] ?? 'CONTACT');
        $propertyName = (string)($formField['name'] ?? '');
        $objectTypeId = $this->_resolveHubSpotObjectTypeId($propertyObjectType);

        // Contact fields remain unprefixed for backward compatibility with existing mappings.
        if ($propertyObjectType !== 'CONTACT') {
            $formField['name'] = $objectTypeId . '.' . $propertyName;
        }

        $formField['data'] = array_merge($formField['data'] ?? [], [
            'objectTypeId' => $objectTypeId,
            'propertyObjectType' => $propertyObjectType,
            'propertyName' => $propertyName,
        ]);

        return $formField;
    }
}
