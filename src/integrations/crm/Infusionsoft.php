<?php
namespace verbb\formie\integrations\crm;

use verbb\formie\Formie;
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

use Throwable;

use GuzzleHttp\Client;

class Infusionsoft extends Crm
{
    // Static Methods
    // =========================================================================

    public static function supportsOauthConnection(): bool
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public static function displayName(): string
    {
        return Craft::t('formie', 'Infusionsoft');
    }
    

    // Properties
    // =========================================================================

    public ?string $clientId = null;
    public ?string $clientSecret = null;
    public bool $mapToContact = false;
    public ?array $contactFieldMapping = null;


    // Public Methods
    // =========================================================================

    public function getAuthorizeUrl(): string
    {
        return 'https://accounts.infusionsoft.com/app/oauth/authorize';
    }

    public function getAccessTokenUrl(): string
    {
        return 'https://api.infusionsoft.com/token';
    }

    public function getClientId(): string
    {
        return App::parseEnv($this->clientId);
    }

    public function getClientSecret(): string
    {
        return App::parseEnv($this->clientSecret);
    }

    public function getDescription(): string
    {
        return Craft::t('formie', 'Manage your Infusionsoft customers by providing important information on their conversion on your site.');
    }

    /**
     * @inheritDoc
     */
    public function defineRules(): array
    {
        $rules = parent::defineRules();

        $rules[] = [['clientId', 'clientSecret'], 'required'];

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
            $response = $this->request('GET', 'contacts/model');
            $fields = $response['custom_fields'] ?? [];

            $contactFields = array_merge([
                new IntegrationField([
                    'handle' => 'given_name',
                    'name' => Craft::t('formie', 'First Name'),
                ]),
                new IntegrationField([
                    'handle' => 'middle_name',
                    'name' => Craft::t('formie', 'Middle Name'),
                ]),
                new IntegrationField([
                    'handle' => 'family_name',
                    'name' => Craft::t('formie', 'Last Name'),
                ]),
                new IntegrationField([
                    'handle' => 'suffix',
                    'name' => Craft::t('formie', 'Suffix'),
                ]),
                new IntegrationField([
                    'handle' => 'preferred_name',
                    'name' => Craft::t('formie', 'Preferred Name'),
                ]),
                new IntegrationField([
                    'handle' => 'website',
                    'name' => Craft::t('formie', 'Website'),
                ]),
                new IntegrationField([
                    'handle' => 'time_zone',
                    'name' => Craft::t('formie', 'Timezone'),
                ]),
                new IntegrationField([
                    'handle' => 'spouse_name',
                    'name' => Craft::t('formie', 'Spouse Name'),
                ]),
                new IntegrationField([
                    'handle' => 'opt_in_reason',
                    'name' => Craft::t('formie', 'Opt-in Reason'),
                    'required' => true,
                ]),
                new IntegrationField([
                    'handle' => 'lead_source_id',
                    'name' => Craft::t('formie', 'Lead Source ID'),
                ]),
                new IntegrationField([
                    'handle' => 'job_title',
                    'name' => Craft::t('formie', 'Job Title'),
                ]),
                new IntegrationField([
                    'handle' => 'owner_id',
                    'name' => Craft::t('formie', 'Owner ID'),
                ]),
                new IntegrationField([
                    'handle' => 'email',
                    'name' => Craft::t('formie', 'Email'),
                    'required' => true,
                ]),
                new IntegrationField([
                    'handle' => 'line1',
                    'name' => Craft::t('formie', 'Address Street'),
                ]),
                new IntegrationField([
                    'handle' => 'line2',
                    'name' => Craft::t('formie', 'Address Street 2'),
                ]),
                new IntegrationField([
                    'handle' => 'locality',
                    'name' => Craft::t('formie', 'Address City'),
                ]),
                new IntegrationField([
                    'handle' => 'postal_code',
                    'name' => Craft::t('formie', 'Address Postal Code'),
                ]),
                new IntegrationField([
                    'handle' => 'region',
                    'name' => Craft::t('formie', 'Address Region'),
                ]),
                new IntegrationField([
                    'handle' => 'zip_code',
                    'name' => Craft::t('formie', 'Address Zip Code'),
                ]),
                new IntegrationField([
                    'handle' => 'country_code',
                    'name' => Craft::t('formie', 'Address Country Code'),
                ]),
                new IntegrationField([
                    'handle' => 'number',
                    'name' => Craft::t('formie', 'Phone Number'),
                ]),
                new IntegrationField([
                    'handle' => 'anniversary',
                    'name' => Craft::t('formie', 'Anniversary'),
                ]),
                new IntegrationField([
                    'handle' => 'birthday',
                    'name' => Craft::t('formie', 'Birthday'),
                ]),
                new IntegrationField([
                    'handle' => 'source_type',
                    'name' => Craft::t('formie', 'Source Type'),
                ]),
            ], $this->_getCustomFields($fields));

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

            // Special processing on this due to nested content in payload
            $contactPayload = $this->_prepContactPayload($contactValues);

            $response = $this->deliverPayload($submission, 'contacts', $contactPayload);

            if ($response === false) {
                return true;
            }

            $contactId = $response['id'] ?? '';

            if (!$contactId) {
                Integration::error($this, Craft::t('formie', 'Missing return “contactId” {response}. Sent payload {payload}', [
                    'response' => Json::encode($response),
                    'payload' => Json::encode($contactPayload),
                ]), true);

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

        $token = $this->getToken();

        if (!$token) {
            Integration::apiError($this, 'Token not found for integration.', true);
        }

        $this->_client = Craft::createGuzzleClient([
            'base_uri' => 'https://api.infusionsoft.com/crm/rest/v1/',
            'headers' => [
                'Authorization' => 'Bearer ' . ($token->accessToken ?? 'empty'),
                'Content-Type' => 'application/json',
            ],
        ]);

        // Always provide an authenticated client - so check first.
        // We can't always rely on the EOL of the token.
        try {
            $response = $this->request('GET', 'account/profile');
        } catch (Throwable $e) {
            if ($e->getCode() === 401) {
                // Force-refresh the token
                Formie::$plugin->getTokens()->refreshToken($token, true);

                // Then try again, with the new access token
                $this->_client = Craft::createGuzzleClient([
                    'base_uri' => 'https://api.infusionsoft.com/crm/rest/v1/',
                    'headers' => [
                        'Authorization' => 'Bearer ' . ($token->accessToken ?? 'empty'),
                        'Content-Type' => 'application/json',
                    ],
                ]);
            }
        }

        return $this->_client;
    }


    // Private Methods
    // =========================================================================

    private function _convertFieldType($fieldType)
    {
        $fieldTypes = [
            'ListBox' => IntegrationField::TYPE_ARRAY,
            'Number' => IntegrationField::TYPE_FLOAT,
            'WholeNumber' => IntegrationField::TYPE_NUMBER,
            'Currency' => IntegrationField::TYPE_FLOAT,
            'Date' => IntegrationField::TYPE_DATE,
            'DateTime' => IntegrationField::TYPE_DATETIME,
        ];

        return $fieldTypes[$fieldType] ?? IntegrationField::TYPE_STRING;
    }

    private function _getCustomFields($fields, $excludeNames = []): array
    {
        $customFields = [];

        $supportedFields = [
            'Text',
            'TextArea',
            'Radio',
            'Dropdown',
            'YesNo',
            'ListBox',
            'Number',
            'WholeNumber',
            'Currency',
            'Date',
            'DateTime',
        ];

        foreach ($fields as $key => $field) {
            // Only allow supported types
            if (!in_array($field['field_type'], $supportedFields)) {
                continue;
            }

            // Exclude any names
            if (in_array($field['field_type'], $excludeNames)) {
                continue;
            }

            $customFields[] = new IntegrationField([
                'handle' => 'custom:' . $field['id'],
                'name' => $field['label'],
                'type' => $this->_convertFieldType($field['field_type']),
            ]);
        }

        return $customFields;
    }

    private function _prepContactPayload($fields): array
    {
        $payload = $fields;
        $customFields = [];

        foreach ($payload as $key => $value) {
            if (StringHelper::startsWith($key, 'custom:')) {
                $field = ArrayHelper::remove($payload, $key);

                $payload['custom_fields'][] = [
                    'id' => str_replace('custom:', '', $key),
                    'content' => $value,
                ];
            }
        }

        // Rip out some fields that need to be structured correctly
        $payload['email_addresses'] = [
            [
                'email' => ArrayHelper::remove($payload, 'email'),
                'field' => 'EMAIL1',
            ],
        ];

        $phone = ArrayHelper::remove($payload, 'number');

        if ($phone) {
            $payload['phone_numbers'] = [
                [
                    'number' => $phone,
                    'field' => 'PHONE1',
                ],
            ];
        }

        $address = array_filter([
            'country_code' => ArrayHelper::remove($payload, 'country_code'),
            'line1' => ArrayHelper::remove($payload, 'line1'),
            'line2' => ArrayHelper::remove($payload, 'line2'),
            'locality' => ArrayHelper::remove($payload, 'locality'),
            'postal_code' => ArrayHelper::remove($payload, 'postal_code'),
            'region' => ArrayHelper::remove($payload, 'region'),
            'zip_code' => ArrayHelper::remove($payload, 'zip_code'),
        ]);

        if ($address) {
            $payload['addresses'] = [
                array_merge(['field' => 'BILLING'], $address),
            ];
        }

        return $payload;
    }
}
