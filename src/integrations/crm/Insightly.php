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
use craft\helpers\StringHelper;

use GuzzleHttp\Client;

use Throwable;

class Insightly extends Crm
{
    // Static Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
    public static function displayName(): string
    {
        return Craft::t('formie', 'Insightly');
    }
    

    // Properties
    // =========================================================================
    
    public ?string $apiKey = null;
    public bool $mapToContact = false;
    public bool $mapToLead = false;
    public ?array $contactFieldMapping = null;
    public ?array $leadFieldMapping = null;


    // Public Methods
    // =========================================================================

    public function getDescription(): string
    {
        return Craft::t('formie', 'Manage your Insightly customers by providing important information on their conversion on your site.');
    }

    /**
     * @inheritDoc
     */
    public function defineRules(): array
    {
        $rules = parent::defineRules();

        $rules[] = [['apiKey'], 'required'];

        $contact = $this->getFormSettingValue('contact');
        $lead = $this->getFormSettingValue('lead');

        // Validate the following when saving form settings
        $rules[] = [
            ['contactFieldMapping'], 'validateFieldMapping', 'params' => $contact, 'when' => function($model) {
                return $model->enabled && $model->mapToContact;
            }, 'on' => [Integration::SCENARIO_FORM],
        ];

        $rules[] = [
            ['leadFieldMapping'], 'validateFieldMapping', 'params' => $lead, 'when' => function($model) {
                return $model->enabled && $model->mapToLead;
            }, 'on' => [Integration::SCENARIO_FORM],
        ];

        return $rules;
    }

    public function fetchFormSettings(): IntegrationFormSettings
    {
        $settings = [];

        try {
            // Get Contacts fields
            $fields = $this->request('GET', 'CustomFields/Contacts');

            $contactFields = array_merge([
                new IntegrationField([
                    'handle' => 'CONTACT_ID',
                    'name' => Craft::t('formie', 'Contact ID'),
                ]),
                new IntegrationField([
                    'handle' => 'SALUTATION',
                    'name' => Craft::t('formie', 'Salutation'),
                ]),
                new IntegrationField([
                    'handle' => 'FIRST_NAME',
                    'name' => Craft::t('formie', 'First Name'),
                    'required' => true,
                ]),
                new IntegrationField([
                    'handle' => 'LAST_NAME',
                    'name' => Craft::t('formie', 'Last Name'),
                ]),
                new IntegrationField([
                    'handle' => 'OWNER_USER_ID',
                    'name' => Craft::t('formie', 'Owner User ID'),
                ]),
                new IntegrationField([
                    'handle' => 'SOCIAL_LINKEDIN',
                    'name' => Craft::t('formie', 'Social (Linked.in)'),
                ]),
                new IntegrationField([
                    'handle' => 'SOCIAL_FACEBOOK',
                    'name' => Craft::t('formie', 'Social (Facebook)'),
                ]),
                new IntegrationField([
                    'handle' => 'SOCIAL_TWITTER',
                    'name' => Craft::t('formie', 'Social (Twitter)'),
                ]),
                new IntegrationField([
                    'handle' => 'DATE_OF_BIRTH',
                    'name' => Craft::t('formie', 'Date of Birth'),
                ]),
                new IntegrationField([
                    'handle' => 'PHONE',
                    'name' => Craft::t('formie', 'Phone'),
                ]),
                new IntegrationField([
                    'handle' => 'PHONE_HOME',
                    'name' => Craft::t('formie', 'Phone (Home)'),
                ]),
                new IntegrationField([
                    'handle' => 'PHONE_MOBILE',
                    'name' => Craft::t('formie', 'Phone (Mobile)'),
                ]),
                new IntegrationField([
                    'handle' => 'PHONE_OTHER',
                    'name' => Craft::t('formie', 'Phone (Other)'),
                ]),
                new IntegrationField([
                    'handle' => 'EMAIL_ADDRESS',
                    'name' => Craft::t('formie', 'Email'),
                ]),
                new IntegrationField([
                    'handle' => 'ADDRESS_MAIL_STREET',
                    'name' => Craft::t('formie', 'Address Street'),
                ]),
                new IntegrationField([
                    'handle' => 'ADDRESS_MAIL_CITY',
                    'name' => Craft::t('formie', 'Address City'),
                ]),
                new IntegrationField([
                    'handle' => 'ADDRESS_MAIL_STATE',
                    'name' => Craft::t('formie', 'Address State'),
                ]),
                new IntegrationField([
                    'handle' => 'ADDRESS_MAIL_POSTCODE',
                    'name' => Craft::t('formie', 'Address Postcode'),
                ]),
                new IntegrationField([
                    'handle' => 'ADDRESS_MAIL_COUNTRY',
                    'name' => Craft::t('formie', 'Address Country'),
                ]),
                new IntegrationField([
                    'handle' => 'ORGANISATION_ID',
                    'name' => Craft::t('formie', 'Organisation ID'),
                ]),
            ], $this->_getCustomFields($fields));

            // Get Leads fields
            $fields = $this->request('GET', 'CustomFields/Leads');

            $leadFields = array_merge([
                new IntegrationField([
                    'handle' => 'LEAD_ID',
                    'name' => Craft::t('formie', 'Lead ID'),
                ]),
                new IntegrationField([
                    'handle' => 'SALUTATION',
                    'name' => Craft::t('formie', 'Salutation'),
                ]),
                new IntegrationField([
                    'handle' => 'FIRST_NAME',
                    'name' => Craft::t('formie', 'First Name'),
                ]),
                new IntegrationField([
                    'handle' => 'LAST_NAME',
                    'name' => Craft::t('formie', 'Last Name'),
                    'required' => true,
                ]),
                new IntegrationField([
                    'handle' => 'TITLE',
                    'name' => Craft::t('formie', 'Title'),
                ]),
                new IntegrationField([
                    'handle' => 'LEAD_STATUS_ID',
                    'name' => Craft::t('formie', 'Lead Status ID'),
                ]),
                new IntegrationField([
                    'handle' => 'PHONE',
                    'name' => Craft::t('formie', 'Phone'),
                ]),
                new IntegrationField([
                    'handle' => 'EMAIL',
                    'name' => Craft::t('formie', 'Email'),
                ]),
                new IntegrationField([
                    'handle' => 'MOBILE',
                    'name' => Craft::t('formie', 'Mobile'),
                ]),
                new IntegrationField([
                    'handle' => 'FAX',
                    'name' => Craft::t('formie', 'Fax'),
                ]),
                new IntegrationField([
                    'handle' => 'WEBSITE',
                    'name' => Craft::t('formie', 'Website'),
                ]),
                new IntegrationField([
                    'handle' => 'ORGANISATION_NAME',
                    'name' => Craft::t('formie', 'Organisation Name'),
                ]),
                new IntegrationField([
                    'handle' => 'INDUSTRY',
                    'name' => Craft::t('formie', 'Industry'),
                ]),
                new IntegrationField([
                    'handle' => 'EMPLOYEE_COUNT',
                    'name' => Craft::t('formie', 'Employee Count'),
                ]),
                new IntegrationField([
                    'handle' => 'IMAGE_URL',
                    'name' => Craft::t('formie', 'Image URL'),
                ]),
                new IntegrationField([
                    'handle' => 'ADDRESS_STREET',
                    'name' => Craft::t('formie', 'Address Street'),
                ]),
            ], $this->_getCustomFields($fields));

            $settings = [
                'contact' => $contactFields,
                'lead' => $leadFields,
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
            $leadValues = $this->getFieldMappingValues($submission, $this->leadFieldMapping, 'lead');

            $customFields = $this->_prepCustomFields($contactValues);

            $contactPayload = array_merge($contactValues, [
                'CUSTOMFIELDS' => $customFields,
            ]);

            $response = $this->deliverPayload($submission, 'Contacts', $contactPayload);

            if ($response === false) {
                return true;
            }

            $contactId = $response['CONTACT_ID'] ?? '';

            if (!$contactId) {
                Integration::error($this, Craft::t('formie', 'Missing return “contactId” {response}. Sent payload {payload}', [
                    'response' => Json::encode($response),
                    'payload' => Json::encode($contactPayload),
                ]), true);

                return false;
            }

            $customFields = $this->_prepCustomFields($leadValues);

            $leadPayload = array_merge($leadValues, [
                'CUSTOMFIELDS' => $customFields,
            ]);

            $response = $this->deliverPayload($submission, 'Leads', $leadPayload);

            if ($response === false) {
                return true;
            }

            $leadId = $response['LEAD_ID'] ?? '';

            if (!$leadId) {
                Integration::error($this, Craft::t('formie', 'Missing return “leadId” {response}. Sent payload {payload}', [
                    'response' => Json::encode($response),
                    'payload' => Json::encode($leadPayload),
                ]), true);

                return false;
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
            $response = $this->request('GET', 'Instance');
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
            'base_uri' => 'https://api.insightly.com/v3.1/',
            'auth' => [App::parseEnv($this->apiKey), ''],
        ]);
    }


    // Private Methods
    // =========================================================================

    private function _convertFieldType($fieldType)
    {
        $fieldTypes = [
            'DATE' => IntegrationField::TYPE_DATETIME,
            'BIT' => IntegrationField::TYPE_BOOLEAN,
            'NUMERIC' => IntegrationField::TYPE_NUMBER,
        ];

        return $fieldTypes[$fieldType] ?? IntegrationField::TYPE_STRING;
    }

    private function _getCustomFields($fields, $excludeNames = []): array
    {
        $customFields = [];

        $supportedFields = [
            'TEXT',
            'DROPDOWN',
            'URL',
            'MULTILINETEXT',
            'DATE',
            'BIT',
            'NUMERIC',
        ];

        foreach ($fields as $key => $field) {
            if (!$field['EDITABLE']) {
                continue;
            }

            // Only allow supported types
            if (!in_array($field['FIELD_TYPE'], $supportedFields)) {
                continue;
            }

            // Exclude any names
            if (in_array($field['FIELD_NAME'], $excludeNames)) {
                continue;
            }

            $customFields[] = new IntegrationField([
                'handle' => 'custom:' . $field['FIELD_NAME'],
                'name' => $field['FIELD_LABEL'],
                'type' => $this->_convertFieldType($field['FIELD_TYPE']),
            ]);
        }

        return $customFields;
    }

    private function _prepCustomFields(&$fields): array
    {
        $customFields = [];

        foreach ($fields as $key => $value) {
            if (StringHelper::startsWith($key, 'custom:')) {
                $field = ArrayHelper::remove($fields, $key);

                $customFields[] = [
                    'FIELD_NAME' => str_replace('custom:', '', $key),
                    'FIELD_VALUE' => $value,
                ];
            }
        }

        return $customFields;
    }
}