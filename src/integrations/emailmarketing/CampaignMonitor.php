<?php
namespace verbb\formie\integrations\emailmarketing;

use verbb\formie\base\Integration;
use verbb\formie\base\EmailMarketing;
use verbb\formie\elements\Submission;
use verbb\formie\helpers\ArrayHelper;
use verbb\formie\models\IntegrationCollection;
use verbb\formie\models\IntegrationField;
use verbb\formie\models\IntegrationFormSettings;

use Craft;
use craft\helpers\App;

use GuzzleHttp\Client;

use Throwable;

class CampaignMonitor extends EmailMarketing
{
    // Static Methods
    // =========================================================================

    public static function displayName(): string
    {
        return Craft::t('formie', 'Campaign Monitor');
    }


    // Properties
    // =========================================================================
    
    public ?string $apiKey = null;
    public ?string $clientId = null;


    // Public Methods
    // =========================================================================

    public function getDescription(): string
    {
        return Craft::t('formie', 'Sign up users to your {name} lists to grow your audience for campaigns.', ['name' => static::displayName()]);
    }

    public function fetchFormSettings(): IntegrationFormSettings
    {
        $settings = [];

        try {
            $clientId = App::parseEnv($this->clientId);
            $lists = $this->request('GET', "clients/{$clientId}/lists.json");

            foreach ($lists as $list) {
                // While we're at it, fetch the fields for the list
                $fields = $this->request('GET', 'lists/' . $list['ListID'] . '/customfields.json');

                $listFields = array_merge([
                    new IntegrationField([
                        'handle' => 'Email',
                        'name' => Craft::t('formie', 'Email'),
                        'required' => true,
                    ]),
                    new IntegrationField([
                        'handle' => 'Name',
                        'name' => Craft::t('formie', 'Name'),
                    ]),
                    new IntegrationField([
                        'handle' => 'MobileNumber',
                        'name' => Craft::t('formie', 'Mobile Number'),
                        'type' => IntegrationField::TYPE_PHONE,
                    ]),
                    new IntegrationField([
                        'handle' => 'ConsentToTrack',
                        'name' => Craft::t('formie', 'Consent To Track'),
                        'type' => IntegrationField::TYPE_BOOLEAN,
                        'options' => [
                            'label' => Craft::t('formie', 'Consent To Track'),
                            'options' => [
                                [
                                    'label' =>  Craft::t('formie', 'Yes'),
                                    'value' => true,
                                ],
                                [
                                    'label' =>  Craft::t('formie', 'No'),
                                    'value' => false,
                                ],
                            ],
                        ],
                    ]),
                    new IntegrationField([
                        'handle' => 'ConsentToSendSms',
                        'name' => Craft::t('formie', 'Consent To Send SMS'),
                        'type' => IntegrationField::TYPE_BOOLEAN,
                        'options' => [
                            'label' => Craft::t('formie', 'Consent To Track'),
                            'options' => [
                                [
                                    'label' =>  Craft::t('formie', 'Yes'),
                                    'value' => true,
                                ],
                                [
                                    'label' =>  Craft::t('formie', 'No'),
                                    'value' => false,
                                ],
                            ],
                        ],
                    ]),
                ], $this->_getCustomFields($fields));

                $settings['lists'][] = new IntegrationCollection([
                    'id' => $list['ListID'],
                    'name' => $list['Name'],
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
            $name = ArrayHelper::remove($fieldValues, 'Name');
            $mobileNumber = ArrayHelper::remove($fieldValues, 'MobileNumber');
            $consentToTrack = ArrayHelper::remove($fieldValues, 'ConsentToTrack');
            $consentToSendSms = ArrayHelper::remove($fieldValues, 'ConsentToSendSms');

            // Format custom fields
            $customFields = [];

            foreach ($fieldValues as $key => $value) {
                if (is_array($value)) {
                    foreach ($value as $v) {
                        $customFields[] = [
                            'Key' => $key,
                            'Value' => $v,
                        ];
                    }
                } else {
                    $customFields[] = [
                        'Key' => $key,
                        'Value' => $value,
                    ];
                }
            }

            $payload = [
                'EmailAddress' => $email,
                'Name' => $name,
                'CustomFields' => $customFields,
                'Resubscribe' => true,
                'RestartSubscriptionBasedAutoresponders' => true,
            ];

            if ($consentToTrack !== null) {
                $payload['ConsentToTrack'] = $consentToTrack ? 'Yes' : 'No';
            }

            if ($consentToSendSms !== null) {
                $payload['ConsentToSendSms'] = $consentToSendSms ? 'Yes' : 'No';
            }

            if ($mobileNumber) {
                $payload['MobileNumber'] = $mobileNumber;
            }

            $response = $this->deliverPayload($submission, "subscribers/{$this->listId}.json", $payload);

            if ($response === false) {
                return true;
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
            $clientId = App::parseEnv($this->clientId);
            $response = $this->request('GET', "clients/{$clientId}.json");
            $error = $response['error'] ?? '';
            $apiKey = $response['ApiKey'] ?? '';

            if ($error) {
                Integration::error($this, $error, true);
                return false;
            }

            if (!$apiKey) {
                Integration::error($this, 'Unable to find “{ApiKey}” in response.', true);
                return false;
            }
        } catch (Throwable $e) {
            Integration::apiError($this, $e);

            return false;
        }

        return true;
    }

    
    // Protected Methods
    // =========================================================================

    protected function defineClient(): Client
    {
        return Craft::createGuzzleClient([
            'base_uri' => 'https://api.createsend.com/api/v3.2/',
            'auth' => [App::parseEnv($this->apiKey), 'formie'],
        ]);
    }


    // Private Methods
    // =========================================================================

    private function _convertFieldType(string $fieldType): string
    {
        $fieldTypes = [
            'Date' => IntegrationField::TYPE_DATE,
            'Number' => IntegrationField::TYPE_NUMBER,
            'MultiSelectMany' => IntegrationField::TYPE_ARRAY,
        ];

        return $fieldTypes[$fieldType] ?? IntegrationField::TYPE_STRING;
    }

    private function _getCustomFields(array $fields, array $excludeNames = []): array
    {
        $customFields = [];

        foreach ($fields as $field) {
            // Exclude any names
            if (in_array($field['FieldName'], $excludeNames)) {
                continue;
            }

            $customFields[] = new IntegrationField([
                'handle' => str_replace(['[', ']'], '', $field['Key']),
                'name' => $field['FieldName'],
                'type' => $this->_convertFieldType($field['DataType']),
                'sourceType' => $field['DataType'],
            ]);
        }

        return $customFields;
    }
}