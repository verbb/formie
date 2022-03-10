<?php
namespace verbb\formie\integrations\emailmarketing;

use verbb\formie\base\Integration;
use verbb\formie\base\EmailMarketing;
use verbb\formie\elements\Submission;
use verbb\formie\models\IntegrationCollection;
use verbb\formie\models\IntegrationField;
use verbb\formie\models\IntegrationFormSettings;

use Craft;
use craft\helpers\App;
use craft\helpers\ArrayHelper;
use craft\helpers\Json;

use GuzzleHttp\Client;

use Throwable;

class Autopilot extends EmailMarketing
{
    // Static Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
    public static function displayName(): string
    {
        return Craft::t('formie', 'Autopilot');
    }
    

    // Properties
    // =========================================================================

    public ?string $apiKey = null;


    // Public Methods
    // =========================================================================

    public function getDescription(): string
    {
        return Craft::t('formie', 'Sign up users to your Autopilot lists to grow your audience for campaigns.');
    }

    /**
     * @inheritDoc
     */
    public function defineRules(): array
    {
        $rules = parent::defineRules();

        $rules[] = [['apiKey'], 'required'];

        return $rules;
    }

    public function fetchFormSettings(): IntegrationFormSettings
    {
        $settings = [];

        try {
            $response = $this->request('GET', 'lists');
            $lists = $response['lists'] ?? [];

            // While we're at it, fetch the fields for the list
            $fields = $this->request('GET', 'contacts/custom_fields');

            foreach ($lists as $list) {
                $listFields = array_merge([
                    new IntegrationField([
                        'handle' => 'Email',
                        'name' => Craft::t('formie', 'Email'),
                        'required' => true,
                    ]),
                    new IntegrationField([
                        'handle' => 'Title',
                        'name' => Craft::t('formie', 'Title'),
                    ]),
                    new IntegrationField([
                        'handle' => 'FirstName',
                        'name' => Craft::t('formie', 'First Name'),
                    ]),
                    new IntegrationField([
                        'handle' => 'LastName',
                        'name' => Craft::t('formie', 'Last Name'),
                    ]),
                    new IntegrationField([
                        'handle' => 'Salutation',
                        'name' => Craft::t('formie', 'Salutation'),
                    ]),
                    new IntegrationField([
                        'handle' => 'Company',
                        'name' => Craft::t('formie', 'Company'),
                    ]),
                    new IntegrationField([
                        'handle' => 'NumberOfEmployees',
                        'name' => Craft::t('formie', 'Number Of Employees'),
                    ]),
                    new IntegrationField([
                        'handle' => 'Industry',
                        'name' => Craft::t('formie', 'Industry'),
                    ]),
                    new IntegrationField([
                        'handle' => 'Phone',
                        'name' => Craft::t('formie', 'Phone'),
                    ]),
                    new IntegrationField([
                        'handle' => 'MobilePhone',
                        'name' => Craft::t('formie', 'Mobile Phone'),
                    ]),
                    new IntegrationField([
                        'handle' => 'Fax',
                        'name' => Craft::t('formie', 'Fax'),
                    ]),
                    new IntegrationField([
                        'handle' => 'Website',
                        'name' => Craft::t('formie', 'Website'),
                    ]),
                    new IntegrationField([
                        'handle' => 'MailingStreet',
                        'name' => Craft::t('formie', 'Mailing Street'),
                    ]),
                    new IntegrationField([
                        'handle' => 'MailingCity',
                        'name' => Craft::t('formie', 'Mailing City'),
                    ]),
                    new IntegrationField([
                        'handle' => 'MailingState',
                        'name' => Craft::t('formie', 'Mailing State'),
                    ]),
                    new IntegrationField([
                        'handle' => 'MailingPostalCode',
                        'name' => Craft::t('formie', 'Mailing Postal Code'),
                    ]),
                    new IntegrationField([
                        'handle' => 'MailingCountry',
                        'name' => Craft::t('formie', 'Mailing Postal Country'),
                    ]),
                    new IntegrationField([
                        'handle' => 'owner_name',
                        'name' => Craft::t('formie', 'Owner Name'),
                    ]),
                    new IntegrationField([
                        'handle' => 'LeadSource',
                        'name' => Craft::t('formie', 'Lead Source'),
                    ]),
                    new IntegrationField([
                        'handle' => 'Status',
                        'name' => Craft::t('formie', 'Status'),
                    ]),
                    new IntegrationField([
                        'handle' => 'Twitter',
                        'name' => Craft::t('formie', 'Twitter'),
                    ]),
                    new IntegrationField([
                        'handle' => 'LinkedIn',
                        'name' => Craft::t('formie', 'LinkedIn'),
                    ]),
                    new IntegrationField([
                        'handle' => 'unsubscribed',
                        'name' => Craft::t('formie', 'Unsubscribed'),
                    ]),
                ], $this->_getCustomFields($fields));

                $settings['lists'][] = new IntegrationCollection([
                    'id' => $list['list_id'],
                    'name' => $list['title'],
                    'fields' => $listFields,
                ]);
            }
        } catch (Throwable $e) {
            Integration::apiError($this, $e);
        }

        return new IntegrationFormSettings($settings);
    }

    public function sendPayload(Submission $submission): bool
    {
        try {
            $fieldValues = $this->getFieldMappingValues($submission, $this->fieldMapping);

            // Pull out email, as it needs to be top level
            $email = ArrayHelper::remove($fieldValues, 'Email');
            $firstName = ArrayHelper::remove($fieldValues, 'FirstName');
            $lastName = ArrayHelper::remove($fieldValues, 'LastName');
            $company = ArrayHelper::remove($fieldValues, 'Company');
            $phone = ArrayHelper::remove($fieldValues, 'Phone');
            $mobilePhone = ArrayHelper::remove($fieldValues, 'MobilePhone');
            $website = ArrayHelper::remove($fieldValues, 'Website');
            $leadSource = ArrayHelper::remove($fieldValues, 'LeadSource');
            $status = ArrayHelper::remove($fieldValues, 'Status');
            $title = ArrayHelper::remove($fieldValues, 'Title');
            $salutation = ArrayHelper::remove($fieldValues, 'Salutation');
            $numberOfEmployees = ArrayHelper::remove($fieldValues, 'NumberOfEmployees');
            $industry = ArrayHelper::remove($fieldValues, 'Industry');
            $fax = ArrayHelper::remove($fieldValues, 'Fax');
            $mailingStreet = ArrayHelper::remove($fieldValues, 'MailingStreet');
            $mailingCity = ArrayHelper::remove($fieldValues, 'MailingCity');
            $mailingState = ArrayHelper::remove($fieldValues, 'MailingState');
            $mailingPostalCode = ArrayHelper::remove($fieldValues, 'MailingPostalCode');
            $mailingCountry = ArrayHelper::remove($fieldValues, 'MailingCountry');
            $owner_name = ArrayHelper::remove($fieldValues, 'owner_name');
            $twitter = ArrayHelper::remove($fieldValues, 'Twitter');
            $linkedIn = ArrayHelper::remove($fieldValues, 'LinkedIn');
            $unsubscribed = ArrayHelper::remove($fieldValues, 'unsubscribed');

            $payload = [
                'contact' => [
                    'Email' => $email,
                    'FirstName' => $firstName,
                    'LastName' => $lastName,
                    'Company' => $company,
                    'Phone' => $phone,
                    'MobilePhone' => $mobilePhone,
                    'Website' => $website,
                    'LeadSource' => $leadSource,
                    'Status' => $status,
                    'MailingCity' => $mailingCity,
                    'Title' => $title,
                    'Salutation' => $salutation,
                    'NumberOfEmployees' => $numberOfEmployees,
                    'Industry' => $industry,
                    'Fax' => $fax,
                    'MailingStreet' => $mailingStreet,
                    'MailingState' => $mailingState,
                    'MailingPostalCode' => $mailingPostalCode,
                    'MailingCountry' => $mailingCountry,
                    'owner_name' => $owner_name,
                    'Twitter' => $twitter,
                    'LinkedIn' => $linkedIn,
                    'unsubscribed' => $unsubscribed,
                    '_autopilot_list' => $this->listId,
                    'custom' => $fieldValues,
                ],
            ];

            $response = $this->deliverPayload($submission, 'contact', $payload);

            if ($response === false) {
                return true;
            }

            $contactId = $response['contact_id'] ?? '';

            if (!$contactId) {
                Integration::error($this, Craft::t('formie', 'API error: “{response}”. Sent payload {payload}', [
                    'response' => Json::encode($response),
                    'payload' => Json::encode($payload),
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
            $response = $this->request('GET', 'account');
            $accountId = $response['instanceId'] ?? '';

            if (!$accountId) {
                Integration::error($this, 'Unable to find “{instanceId}” in response.', true);
                return false;
            }
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
            'base_uri' => 'https://api2.autopilothq.com/v1/',
            'headers' => ['autopilotapikey' => App::parseEnv($this->apiKey)],
        ]);
    }


    // Private Methods
    // =========================================================================

    private function _convertFieldType($fieldType)
    {
        $fieldTypes = [
            'number' => IntegrationField::TYPE_NUMBER,
            'date' => IntegrationField::TYPE_DATETIME,
        ];

        return $fieldTypes[$fieldType] ?? IntegrationField::TYPE_STRING;
    }

    private function _getCustomFields($fields, $excludeNames = []): array
    {
        $customFields = [];

        foreach ($fields as $field) {
            // Exclude any names
            if (in_array($field['name'], $excludeNames)) {
                continue;
            }

            $customFields[] = new IntegrationField([
                'handle' => $field['fieldType'] . '--' . str_replace(' ', '--', $field['name']),
                'name' => $field['name'],
                'type' => $this->_convertFieldType($field['fieldType']),
            ]);
        }

        return $customFields;
    }
}