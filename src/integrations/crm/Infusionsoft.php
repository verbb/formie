<?php
namespace verbb\formie\integrations\crm;

use verbb\formie\Formie;
use verbb\formie\base\Crm;
use verbb\formie\base\Integration;
use verbb\formie\elements\Submission;
use verbb\formie\helpers\ArrayHelper;
use verbb\formie\helpers\StringHelper;
use verbb\formie\models\IntegrationField;
use verbb\formie\models\IntegrationFormSettings;

use Craft;
use craft\helpers\App;
use craft\helpers\Json;

use Throwable;

use verbb\auth\base\OAuthProviderInterface;
use verbb\auth\providers\Infusionsoft as InfusionsoftProvider;

class Infusionsoft extends Crm implements OAuthProviderInterface
{
    // Static Methods
    // =========================================================================

    public static function supportsOAuthConnection(): bool
    {
        return true;
    }

    public static function getOAuthProviderClass(): string
    {
        return InfusionsoftProvider::class;
    }

    public static function displayName(): string
    {
        return Craft::t('formie', 'Infusionsoft');
    }
    

    // Properties
    // =========================================================================

    public bool $mapToContact = false;
    public ?array $contactFieldMapping = null;


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
            if ($this->mapToContact) {
                $response = $this->request('GET', 'contacts/model');
                $fields = $response['custom_fields'] ?? [];

                $settings['contact'] = array_merge([
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

    
    // Protected Methods
    // =========================================================================

    protected function defineRules(): array
    {
        $rules = parent::defineRules();

        $contact = $this->getFormSettingValue('contact');

        // Validate the following when saving form settings
        $rules[] = [
            ['contactFieldMapping'], 'validateFieldMapping', 'params' => $contact, 'when' => function($model) {
                return $model->enabled && $model->mapToContact;
            }, 'on' => [Integration::SCENARIO_FORM],
        ];

        return $rules;
    }


    // Private Methods
    // =========================================================================

    private function _convertFieldType(string $fieldType): string
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

    private function _getCustomFields(array $fields, array $excludeNames = []): array
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
                'sourceType' => $field['field_type'],
            ]);
        }

        return $customFields;
    }

    private function _prepContactPayload($fields): array
    {
        $payload = $fields;
        $customFields = [];

        foreach ($payload as $key => $value) {
            if (str_starts_with($key, 'custom:')) {
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
