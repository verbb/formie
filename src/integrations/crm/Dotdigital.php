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

use GuzzleHttp\Client;

use Throwable;

class Dotdigital extends Crm
{
    // Static Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
    public static function displayName(): string
    {
        return Craft::t('formie', 'Dotdigital');
    }
    

    // Properties
    // =========================================================================
    
    public ?string $username = null;
    public ?string $password = null;
    public ?string $apiDomain = null;
    public bool $mapToContact = false;
    public ?array $contactFieldMapping = null;


    // Public Methods
    // =========================================================================

    public function getDescription(): string
    {
        return Craft::t('formie', 'Manage your Dotdigital customers by providing important information on their conversion on your site.');
    }

    /**
     * @inheritDoc
     */
    public function defineRules(): array
    {
        $rules = parent::defineRules();

        $rules[] = [['username', 'password', 'apiDomain'], 'required'];

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
            $fields = $this->request('GET', 'data-fields');

            $addressBookOptions = [];
            $addressBooks = $this->request('GET', 'address-books');

            foreach ($addressBooks as $addressBook) {
                $addressBookOptions[] = [
                    'label' => $addressBook['name'],
                    'value' => (string)$addressBook['id'],
                ];
            }

            $contactFields = array_merge([
                new IntegrationField([
                    'handle' => 'addressBook',
                    'name' => Craft::t('formie', 'Address Book'),
                    'options' => [
                        'label' => Craft::t('formie', 'Address Book'),
                        'options' => $addressBookOptions,
                    ],
                ]),
                new IntegrationField([
                    'handle' => 'FIRSTNAME',
                    'name' => Craft::t('formie', 'First Name'),
                ]),
                new IntegrationField([
                    'handle' => 'LASTNAME',
                    'name' => Craft::t('formie', 'Last Name'),
                ]),
                new IntegrationField([
                    'handle' => 'email',
                    'name' => Craft::t('formie', 'Email'),
                    'required' => true,
                ]),
                new IntegrationField([
                    'handle' => 'GENDER',
                    'name' => Craft::t('formie', 'Gender'),
                ]),
                new IntegrationField([
                    'handle' => 'POSTCODE',
                    'name' => Craft::t('formie', 'Postcode'),
                ]),
                new IntegrationField([
                    'handle' => 'optInType',
                    'name' => Craft::t('formie', 'Opt-in Type'),
                    'options' => [
                        'label' => Craft::t('formie', 'Opt-in Type'),
                        'options' => [
                            [
                                'label' => Craft::t('formie', 'Unknown'),
                                'value' => 'Unknown',
                            ],
                            [
                                'label' => Craft::t('formie', 'Single'),
                                'value' => 'Single',
                            ],
                            [
                                'label' => Craft::t('formie', 'Double'),
                                'value' => 'Double',
                            ],
                            [
                                'label' => Craft::t('formie', 'VerifiedDouble'),
                                'value' => 'VerifiedDouble',
                            ],
                        ],
                    ],
                ]),
                new IntegrationField([
                    'handle' => 'emailType',
                    'name' => Craft::t('formie', 'Email Type'),
                    'options' => [
                        'label' => Craft::t('formie', 'Email Type'),
                        'options' => [
                            [
                                'label' => Craft::t('formie', 'PlainText'),
                                'value' => 'PlainText',
                            ],
                            [
                                'label' => Craft::t('formie', 'Html'),
                                'value' => 'Html',
                            ],
                        ],
                    ],
                ]),
            ], $this->_getCustomFields($fields, ['FIRSTNAME', 'FULLNAME', 'LASTNAME', 'GENDER', 'LASTSUBSCRIBED', 'POSTCODE']));

            $settings = [
                'contact' => $contactFields,
            ];
        } catch (Throwable $e) {
            Integration::apiError($this, $e);
        }

        return new IntegrationFormSettings($settings);
    }

    public function sendPayload(Submission $submission): bool
    {
        try {
            $contactValues = $this->getFieldMappingValues($submission, $this->contactFieldMapping, 'contact');

            if ($this->mapToContact) {
                $email = ArrayHelper::remove($contactValues, 'email');
                $addressBook = ArrayHelper::remove($contactValues, 'addressBook');
                $emailType = ArrayHelper::remove($contactValues, 'emailType');
                $optInType = ArrayHelper::remove($contactValues, 'optInType');
                $dataFields = $this->_prepCustomFields($contactValues);

                $contactPayload = [
                    'contact' => [
                        'email' => $email,
                        'emailType' => $emailType,
                        'optInType' => $optInType,
                        'dataFields' => $tdataFields,
                    ],
                ];

                $response = $this->deliverPayload($submission, 'contacts/with-consent-and-preferences', $contactPayload);

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

                if ($addressBook) {
                    $addressBookPayload = [
                        'email' => $email,
                    ];

                    $response = $this->deliverPayload($submission, "address-books/{$addressBook}/contacts", $addressBookPayload);

                    if ($response === false) {
                        return true;
                    }

                    $contactId = $response['contact']['id'] ?? '';

                    if (!$contactId) {
                        Integration::error($this, Craft::t('formie', 'Missing return “contactId” {response}. Sent payload {payload}', [
                            'response' => Json::encode($response),
                            'payload' => Json::encode($addressBookPayload),
                        ]), true);

                        return false;
                    }
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
            $response = $this->request('GET', 'account-info');
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

        $url = rtrim(App::parseEnv($this->apiDomain), '/');

        return $this->_client = Craft::createGuzzleClient([
            'base_uri' => $url . '/v2/',
            'auth' => [
                App::parseEnv($this->username), App::parseEnv($this->password),
            ],
        ]);
    }


    // Private Methods
    // =========================================================================

    private function _convertFieldType($fieldType)
    {
        $fieldTypes = [
            'Date' => IntegrationField::TYPE_DATETIME,
            'Boolean' => IntegrationField::TYPE_BOOLEAN,
            'Numeric' => IntegrationField::TYPE_NUMBER,
        ];

        return $fieldTypes[$fieldType] ?? IntegrationField::TYPE_STRING;
    }

    private function _getCustomFields($fields, $excludeNames = []): array
    {
        $customFields = [];

        foreach ($fields as $key => $field) {
            // Exclude any names
            if (in_array($field['name'], $excludeNames)) {
                continue;
            }

            $customFields[] = new IntegrationField([
                'handle' => $field['name'],
                'name' => $field['name'],
                'type' => $this->_convertFieldType($field['type']),
            ]);
        }

        return $customFields;
    }

    private function _prepCustomFields(&$fields): array
    {
        $customFields = [];

        foreach ($fields as $key => $value) {
            $customFields[] = ['key' => $key, 'value' => $value];
        }

        return $customFields;
    }
}
