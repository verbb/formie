<?php
namespace verbb\formie\integrations\crm;

use verbb\formie\base\Crm;
use verbb\formie\base\FormInterface;
use verbb\formie\base\Integration;
use verbb\formie\elements\Submission;
use verbb\formie\events\DotdigitalAddressBooksEvent;
use verbb\formie\helpers\ArrayHelper;
use verbb\formie\helpers\SchemaHelper;
use verbb\formie\models\IntegrationField;
use verbb\formie\models\IntegrationFormSettings;

use Craft;
use craft\helpers\App;
use craft\helpers\DateTimeHelper;
use craft\helpers\Json;

use DateTimeZone;
use Throwable;

use GuzzleHttp\Client;

class Dotdigital extends Crm
{
    // Constants
    // =========================================================================

    public const EVENT_MODIFY_ADDRESS_BOOKS = 'modifyAddressBooks';


    // Static Methods
    // =========================================================================

    public static function displayName(): string
    {
        return 'Dotdigital';
    }
    

    // Properties
    // =========================================================================
    
    public ?string $username = null;
    public ?string $password = null;
    public ?string $apiDomain = null;
    public bool $mapToContact = false;
    public bool $sendEmailCampaign = false;
    public ?array $contactFieldMapping = null;
    public ?array $emailSendMapping = null;


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
            if ($this->settingsContext->dataKey === 'emailCampaign') {
                $settings['emailCampaign'] = [
                    new IntegrationField([
                        'handle' => 'emailCampaignId',
                    'name' => Craft::t('formie', 'Email Campaign'),
                    'required' => true,
                    'options' => [
                        'label' => Craft::t('formie', 'Email Campaign'),
                        'options' => array_map(function($emailCampaign) {
                            return [
                                'label' => $emailCampaign['name'],
                                'value' => (string)$emailCampaign['id'],
                            ];
                        }, $this->request('GET', 'campaigns')),
                    ],
                ]),
                new IntegrationField([
                    'handle' => 'emailCampaignSendDate',
                    'name' => Craft::t('formie', 'Email Campaign Send Date'),
                    'options' => [
                        'label' => Craft::t('formie', 'Email Campaign Send Date'),
                        'options' => [
                            [
                                'label' => Craft::t('formie', '+{num, plural, =1{# hour} other{# hours}}', ['num' => 1]),
                                'value' => '+1 hour',
                            ],
                            [
                                'label' => Craft::t('formie', '+{num, plural, =1{# hour} other{# hours}}', ['num' => 2]),
                                'value' => '+2 hours',
                            ],
                            [
                                'label' => Craft::t('formie', '+{num, plural, =1{# hour} other{# hours}}', ['num' => 4]),
                                'value' => '+4 hours',
                            ],
                            [
                                'label' => Craft::t('formie', '+{num, plural, =1{# hour} other{# hours}}', ['num' => 6]),
                                'value' => '+6 hours',
                            ],
                            [
                                'label' => Craft::t('formie', '+{num, plural, =1{# hour} other{# hours}}', ['num' => 12]),
                                'value' => '+12 hours',
                            ],
                            [
                                'label' => Craft::t('formie', '+{num, plural, =1{# day} other{# days}}', ['num' => 1]),
                                'value' => '+1 day',
                            ],
                        ],
                    ],
                    ])
                ];
            }

            if ($this->mapToContact && $this->settingsContext->dataKey === 'contact') {
                $fields = $this->request('GET', 'data-fields');
                $customFields = $this->_getCustomFields($fields, ['FIRSTNAME', 'FULLNAME', 'LASTNAME', 'GENDER', 'LASTSUBSCRIBED', 'POSTCODE']);

                $addressBooks = $this->request('GET', 'address-books');

                $addressBooksEvent = new DotdigitalAddressBooksEvent([
                    'addressBooks' => $addressBooks,
                ]);

                $this->trigger(self::EVENT_MODIFY_ADDRESS_BOOKS, $addressBooksEvent);

                $addressBooks = $addressBooksEvent->addressBooks;

                $settings['contact'] = array_merge([
                    new IntegrationField([
                        'handle' => 'addressBook',
                        'name' => Craft::t('formie', 'Address Book (List)'),
                        'options' => [
                            'label' => Craft::t('formie', 'Address Book'),
                            'options' => array_map(function($addressBook) {
                                return [
                                    'label' => $addressBook['name'],
                                    'value' => (string)$addressBook['id'],
                                ];
                            }, $addressBooks),
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
                                    'label' => Craft::t('formie', 'HTML'),
                                    'value' => 'Html',
                                ],
                            ],
                        ],
                    ]),
                ], $customFields);
            }
        } catch (Throwable $e) {
            Integration::apiError($this, $e);
        }

        return new IntegrationFormSettings($settings);
    }

    public function sendPayload(Submission $submission): bool
    {
        try {
            $contactValues = $this->getFieldMappingValues($submission, $this->contactFieldMapping, 'contact');
            $contactId = null;

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
                        'dataFields' => $dataFields,
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

                    $contactId = $response['id'] ?? '';

                    if (!$contactId) {
                        Integration::error($this, Craft::t('formie', 'Missing return “contactId” {response}. Sent payload {payload}', [
                            'response' => Json::encode($response),
                            'payload' => Json::encode($addressBookPayload),
                        ]), true);

                        return false;
                    }
                }
            }

            $emailCampaignValues = $this->getFieldMappingValues($submission, $this->emailSendMapping, 'emailCampaign');

            if ($this->sendEmailCampaign && $contactId) {
                $emailCampaign = ArrayHelper::remove($emailCampaignValues, 'emailCampaignId');
                $emailCampaignSendDate = ArrayHelper::remove($emailCampaignValues, 'emailCampaignSendDate');

                $sendDate = null;

                if ($emailCampaignSendDate) {
                    $dateCreated = $submission->dateCreated->setTimezone(new DateTimeZone('UTC'));

                    if (str_starts_with($emailCampaignSendDate, '+')) {
                        // Preset date modify value
                        $sendDate = $dateCreated->modify($emailCampaignSendDate);
                    } else if ($date = DateTimeHelper::toDateTime($emailCampaignSendDate, false, false)) {
                        // DateTime object/string
                        $sendDate = $date->format('c');
                    }
                }

                $emailCampaignPayload = [
                    'campaignID' => $emailCampaign,
                    'contactIDs' => [
                        $contactId,
                    ],
                    'sendDate' => $sendDate,
                ];

                $response = $this->deliverPayload($submission, 'campaigns/send', $emailCampaignPayload);

                if ($response === false) {
                    return true;
                }

                $emailCampaignSendId = $response['id'] ?? '';

                if (!$emailCampaignSendId) {
                    Integration::error($this, Craft::t('formie', 'Missing return “emailSendCampaignId” {response}. Sent payload {payload}', [
                        'response' => Json::encode($response),
                        'payload' => Json::encode($emailCampaignPayload),
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
            $response = $this->request('GET', 'account-info');
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

        $rules[] = [['username', 'password', 'apiDomain'], 'required'];

        $contact = $this->getFormSettingValue('contact');
        $emailCampaign = $this->getFormSettingValue('emailCampaign');

        // Validate the following when saving form settings
        $rules[] = [
            ['contactFieldMapping'], 'validateFieldMapping', 'params' => $contact, 'when' => function($model) {
                return $model->enabled && $model->mapToContact;
            }, 'on' => [Integration::SCENARIO_FORM], 'skipOnEmpty' => false,
        ];

        $rules[] = [
            ['emailSendMapping'], 'validateFieldMapping', 'params' => $emailCampaign, 'when' => function($model) {
                return $model->enabled && $model->sendEmailCampaign;
            }, 'on' => [Integration::SCENARIO_FORM], 'skipOnEmpty' => false,
        ];

        return $rules;
    }

    protected function defineClient(): Client
    {
        $url = rtrim(App::parseEnv($this->apiDomain), '/');

        return Craft::createGuzzleClient([
            'base_uri' => $url . '/v2/',
            'auth' => [
                App::parseEnv($this->username), App::parseEnv($this->password),
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
            'name' => 'sendEmailCampaign',
            'label' => Craft::t('formie', 'Send Email Campaign'),
            'instructions' => Craft::t('formie', 'Whether to send an email campaign to the created contact.'),
        ]);

        $emailCampaign = $this->getFormSettingValue('emailCampaign');
        $emailMappingSchema = is_array($emailCampaign) ? $this->convertIntegrationFieldsToSchema($emailCampaign) : [];
        if ($emailMappingSchema) {
            $schema[] = $this->getIntegrationFieldMappingField([
                'name' => 'emailSendMapping',
                'if' => 'sendEmailCampaign',
                'dataLabel' => 'Email Campaign',
                'dataKey' => 'emailCampaign',
                'integrationFields' => $emailMappingSchema,
            ]);
        }

        return $schema;
    }


    // Private Methods
    // =========================================================================

    private function _convertFieldType(string $fieldType): string
    {
        $fieldTypes = [
            'Date' => IntegrationField::TYPE_DATETIME,
            'Boolean' => IntegrationField::TYPE_BOOLEAN,
            'Numeric' => IntegrationField::TYPE_NUMBER,
        ];

        return $fieldTypes[$fieldType] ?? IntegrationField::TYPE_STRING;
    }

    private function _getCustomFields(array $fields, array $excludeNames = []): array
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
                'sourceType' => $field['type'],
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
