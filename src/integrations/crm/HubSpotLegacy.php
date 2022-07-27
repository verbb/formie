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

use Throwable;

class HubSpotLegacy extends Crm
{
    // Static Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
    public static function displayName(): string
    {
        return Craft::t('formie', 'HubSpot (Legacy)');
    }
    

    // Properties
    // =========================================================================

    public ?string $apiKey = null;
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

    /**
     * @inheritDoc
     */
    public function init(): void
    {
        parent::init();

        Event::on(self::class, self::EVENT_MODIFY_FIELD_MAPPING_VALUE, function(ModifyFieldIntegrationValueEvent $event) {
            // Special handling for single checkbox boolean fields for HubSpot
            if ($event->integrationField->getType() === IntegrationField::TYPE_BOOLEAN) {
                // HubSpot needs this as a string value.
                $event->value = ($event->value === true) ? 'true' : 'false';
            }

            // Special handling for arrays for checkboxes
            if ($event->integrationField->getType() === IntegrationField::TYPE_ARRAY) {
                if (is_array($event->value)) {
                    $event->value = array_filter($event->value);
                    $event->value = ArrayHelper::recursiveImplode(';', $event->value);
                    $event->value = str_replace('&nbsp;', ' ', $event->value);
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

        $rules[] = [['apiKey'], 'required'];

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

        return $this->_client = Craft::createGuzzleClient([
            'base_uri' => 'https://api.hubapi.com/',
            'query' => ['hapikey' => App::parseEnv($this->apiKey)],
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
            $hidden = $field['hidden'] ?? false;
            $calculated = $field['calculated'] ?? false;

            if ($readOnlyValue || $hidden || $calculated) {
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
