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

class SharpSpring extends Crm
{
    // Properties
    // =========================================================================

    public $accountId;
    public $secretKey;
    public $mapToContact = false;
    public $contactFieldMapping;


    // Public Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
    public static function displayName(): string
    {
        return Craft::t('formie', 'SharpSpring');
    }

    /**
     * @inheritDoc
     */
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
        $rules[] = [['contactFieldMapping'], 'validateFieldMapping', 'params' => $contact, 'when' => function($model) {
            return $model->enabled && $model->mapToContact;
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
        } catch (\Throwable $e) {
            Integration::error($this, Craft::t('formie', 'API error: “{message}” {file}:{line}', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]), true);
        }

        return new IntegrationFormSettings($settings);
    }

    /**
     * @inheritDoc
     */
    public function sendPayload(Submission $submission): bool
    {
        try {
            $contactValues = $this->getFieldMappingValues($submission, $this->contactFieldMapping, 'contact');

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
        } catch (\Throwable $e) {
            Integration::error($this, Craft::t('formie', 'API error: “{message}” {file}:{line}', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]), true);

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
            $response = $this->request('GET', '');
        } catch (\Throwable $e) {
            Integration::error($this, Craft::t('formie', 'API error: “{message}” {file}:{line}', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]), true);

            return false;
        }

        return true;
    }


    // Protected Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
    protected function getClient()
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

    /**
     * @inheritDoc
     */
    private function _convertFieldType($fieldType)
    {
        $fieldTypes = [
            'int' => IntegrationField::TYPE_NUMBER,
            'boolean' => IntegrationField::TYPE_BOOLEAN,
        ];

        return $fieldTypes[$fieldType] ?? IntegrationField::TYPE_STRING;
    }

    /**
     * @inheritDoc
     */
    private function _getCustomFields($fields, $excludeNames = [])
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
}