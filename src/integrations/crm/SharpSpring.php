<?php
namespace verbb\formie\integrations\crm;

use verbb\formie\Formie;
use verbb\formie\base\Crm;
use verbb\formie\base\Integration;
use verbb\formie\base\SubfieldInterface;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\fields\formfields\Group;
use verbb\formie\models\FakeElementQuery;
use verbb\formie\models\IntegrationField;
use verbb\formie\models\IntegrationFormSettings;

use Craft;
use GuzzleHttp\Client;
use Throwable;

class SharpSpring extends Crm
{
    // Static Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
    public static function displayName(): string
    {
        return Craft::t('formie', 'SharpSpring');
    }
    

    // Properties
    // =========================================================================
    
    public ?string $accountId = null;
    public ?string $secretKey = null;
    public ?string $formUrl = null;
    public bool $mapToContact = false;
    public bool $mapToForm = false;
    public ?array $contactFieldMapping = null;
    public ?string $endpoint = null;


    // Public Methods
    // =========================================================================

    public function getDescription(): string
    {
        return Craft::t('formie', 'Manage your SharpSpring customers by providing important information on their conversion on your site.');
    }

    /**
     * @inheritDoc
     */
    public function defineRules(): array
    {
        $rules = parent::defineRules();

        $rules[] = [['accountId', 'secretKey'], 'required'];

        $contact = $this->getFormSettingValue('contact');

        // Validate the following when saving form settings
        $rules[] = [
            ['contactFieldMapping'], 'validateFieldMapping', 'params' => $contact, 'when' => function($model) {
                return $model->enabled && $model->mapToContact;
            }, 'on' => [Integration::SCENARIO_FORM],
        ];

        return $rules;
    }

    public function fetchFormSettings(): IntegrationFormSettings
    {
        $settings = [];

        try {
            // Just fetch the forms and their fields
            if (Craft::$app->getRequest()->getParam('sendFormPayload')) {
                $settings['syncFormSuccess'] = '';
                $settings['syncFormError'] = '';

                $endpoint = Craft::$app->getRequest()->getParam('endpoint');

                if ($endpoint) {
                    // Get the current form instance for the integration
                    $formId = Craft::$app->getRequest()->getParam('formId');
                    $form = Form::find()->id($formId)->one();

                    // Create a fake submission to send to SharpSpring
                    $submission = new Submission();
                    $submission->setForm($form);
                    Formie::$plugin->getSubmissions()->populateFakeSubmission($submission);

                    $response = $this->_sendFormSubmission($endpoint, $submission);

                    // HTML/JS is returned from the response, so handle that.
                    if (str_contains($response, '__ss_noform.success = true')) {
                        $settings['syncFormSuccess'] = Craft::t('formie', 'Successfully synced with SharpSpring');
                    } else {
                        $settings['syncFormError'] = $response;
                    }
                } else {
                    $settings['syncFormError'] = Craft::t('formie', 'Endpoint required');
                }
            } else {
                $response = $this->request('POST', '', [
                    'json' => [
                        'method' => 'getFields',
                        'params' => ['where' => [], 'limit' => 500, 'offset' => 0],
                        'id' => 'formie',
                    ],
                ]);

                $fields = $response['result']['field'] ?? [];

                $contactFields = array_merge([
                    new IntegrationField([
                        'handle' => 'emailAddress',
                        'name' => Craft::t('formie', 'Email'),
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
                        'handle' => 'website',
                        'name' => Craft::t('formie', 'Website'),
                    ]),
                    new IntegrationField([
                        'handle' => 'phoneNumber',
                        'name' => Craft::t('formie', 'Phone Number'),
                    ]),
                    new IntegrationField([
                        'handle' => 'phoneNumberExtension',
                        'name' => Craft::t('formie', 'Phone Number Extension'),
                    ]),
                    new IntegrationField([
                        'handle' => 'faxNumber',
                        'name' => Craft::t('formie', 'Fax'),
                    ]),
                    new IntegrationField([
                        'handle' => 'mobilePhoneNumber',
                        'name' => Craft::t('formie', 'Mobile Phone Number'),
                    ]),
                    new IntegrationField([
                        'handle' => 'street',
                        'name' => Craft::t('formie', 'Address Street'),
                    ]),
                    new IntegrationField([
                        'handle' => 'city',
                        'name' => Craft::t('formie', 'Address City'),
                    ]),
                    new IntegrationField([
                        'handle' => 'state',
                        'name' => Craft::t('formie', 'Address State'),
                    ]),
                    new IntegrationField([
                        'handle' => 'zipcode',
                        'name' => Craft::t('formie', 'Address Zipcode'),
                    ]),
                    new IntegrationField([
                        'handle' => 'companyName',
                        'name' => Craft::t('formie', 'Company Name'),
                    ]),
                    new IntegrationField([
                        'handle' => 'industry',
                        'name' => Craft::t('formie', 'Industry'),
                    ]),
                    new IntegrationField([
                        'handle' => 'description',
                        'name' => Craft::t('formie', 'Description'),
                    ]),
                    new IntegrationField([
                        'handle' => 'title',
                        'name' => Craft::t('formie', 'Title'),
                    ]),
                    new IntegrationField([
                        'handle' => 'trackingID',
                        'name' => Craft::t('formie', 'Tracking ID'),
                    ]),
                    new IntegrationField([
                        'handle' => 'campaignID',
                        'name' => Craft::t('formie', 'Campaign IDs'),
                    ]),
                    new IntegrationField([
                        'handle' => 'accountID',
                        'name' => Craft::t('formie', 'Account IDs'),
                    ]),
                ], $this->_getCustomFields($fields));

                $settings = [
                    'contact' => $contactFields,
                ];
            }
        } catch (Throwable $e) {
            Integration::apiError($this, $e);
        }

        // Because we have split settings for partial settings fetches, ensure we populate settings from cache
        // So we need to un-serialize the cached form settings, and combine with any new settings and return
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

            if ($this->mapToContact) {
                // Handle Tracking ID in case it's not set, try a cookie
                if (!isset($contactValues['trackingID'])) {
                    $contactValues['trackingID'] = $_COOKIE['__ss_tk'] ?? '';
                }

                $contactPayload = [
                    'method' => 'createLeads',
                    'params' => ['objects' => [$contactValues]],
                    'id' => 'formie',
                ];

                $response = $this->deliverPayload($submission, '', $contactPayload);

                if ($response === false) {
                    return true;
                }
            }

            if ($this->mapToForm) {
                $response = $this->_sendFormSubmission($this->endpoint, $submission);
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
            $response = $this->request('GET', '');
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
            'base_uri' => 'https://api.sharpspring.com/pubapi/v1.2/',
            'query' => [
                'accountID' => Craft::parseEnv($this->accountId),
                'secretKey' => Craft::parseEnv($this->secretKey),
            ],
        ]);
    }


    // Private Methods
    // =========================================================================

    private function _convertFieldType($fieldType)
    {
        $fieldTypes = [
            'int' => IntegrationField::TYPE_NUMBER,
            'boolean' => IntegrationField::TYPE_BOOLEAN,
        ];

        return $fieldTypes[$fieldType] ?? IntegrationField::TYPE_STRING;
    }

    private function _getCustomFields($fields, $excludeNames = []): array
    {
        $customFields = [];

        foreach ($fields as $key => $field) {
            if (!$field['isCustom']) {
                continue;
            }

            // For the moment, just mapping contacts, but handle this better.
            if (!$field['isAvailableInContactManager']) {
                continue;
            }

            $customFields[] = new IntegrationField([
                'handle' => $field['systemName'],
                'name' => $field['label'],
                'type' => $this->_convertFieldType($field['dataType']),
                'required' => (bool)$field['isRequired'],
            ]);
        }

        return $customFields;
    }

    private function _sendFormSubmission($endpoint, $submission): string
    {
        $formUrl = Craft::parseEnv($this->formUrl);

        $serializedValues = [];

        // Convert the subscription fields into a format SharpSpring can handle
        // This is unfortunately very specialised...
        foreach ($submission->getFieldLayout()->getCustomFields() as $field) {
            $value = $submission->getFieldValue($field->handle);

            if (method_exists($field, 'serializeValueForIntegration')) {
                $value = $field->serializeValueForIntegration($value, $submission);
            } else {
                $value = $field->serializeValue($value, $submission);
            }

            // Handle when generating a fake submission to set up mapping, this doesn't mess
            // up group fields (repeaters technically work, but aren't supported in SharpSpring)
            if (($value instanceof FakeElementQuery) && $row = $value->one()) {
                $value = $row->getSerializedFieldValues();
            }

            // Handle subfields and group fields
            if ($field instanceof SubfieldInterface || $field instanceof Group) {
                if (is_array($value)) {
                    foreach ($value as $k => $v) {
                        $serializedValues[$field->handle . '.' . $k] = $v;
                    }
                } else {
                    $serializedValues[$field->handle] = $value;
                }
            } else if (is_array($value)) {
                $serializedValues[$field->handle] = implode(',', $value);
            } else {
                $serializedValues[$field->handle] = $value;
            }
        }

        // Send the payload to SharpSpring to tell them what fields are available
        // Create a new client because this isn't the same API as the rest of the integration.
        $request = Craft::createGuzzleClient()->request('GET', "$formUrl/$endpoint/jsonp", [
            'query' => $serializedValues,
        ]);

        return (string)$request->getBody();
    }
}