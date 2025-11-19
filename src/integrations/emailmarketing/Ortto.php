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

class Ortto extends EmailMarketing
{
    // Static Methods
    // =========================================================================

    public static function displayName(): string
    {
        return Craft::t('formie', 'Ortto');
    }
    

    // Properties
    // =========================================================================

    public ?string $apiKey = null;
    public ?string $dataCenter = 'INT';


    // Public Methods
    // =========================================================================

    public function getDescription(): string
    {
        return Craft::t('formie', 'Sign up users to your {name} lists to grow your audience for campaigns.', ['name' => static::displayName()]);
    }

    public function getApiUrl(): string
    {
        if ($this->dataCenter === 'AU') {
            return 'https://api.au.ap3api.com/';
        }

        if ($this->dataCenter === 'EU') {
            return 'https://api.eu.ap3api.com/';
        }

        return 'https://api.ap3api.com/';
    }

    public function fetchFormSettings(): IntegrationFormSettings
    {
        $settings = [];

        try {
            $lists = $this->request('POST', 'audiences/get', [
                'json' => [
                    'offset' => 0,
                ],
            ]) ?? [];

            // While we're at it, fetch the fields for the list
            $response = $this->request('POST', 'person/custom-field/get');
            $fields = $response['fields'] ?? [];

            foreach ($lists as $list) {
                $listFields = array_merge([
                    new IntegrationField([
                        'handle' => 'str::email',
                        'name' => Craft::t('formie', 'Email'),
                        'required' => true,
                    ]),
                    new IntegrationField([
                        'handle' => 'str::first',
                        'name' => Craft::t('formie', 'First Name'),
                    ]),
                    new IntegrationField([
                        'handle' => 'str::last',
                        'name' => Craft::t('formie', 'Last Name'),
                    ]),
                    new IntegrationField([
                        'handle' => 'phn::phone',
                        'name' => Craft::t('formie', 'Phone'),
                    ]),
                ], $this->_getCustomFields($fields));

                $settings['lists'][] = new IntegrationCollection([
                    'id' => $list['id'],
                    'name' => $list['name'],
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

            // Create the contact first, then subscribe
            $payload = [
                'async' => false,
                'merge_by' => ['str::email'],
                'merge_strategy' => 2,
                'find_strategy' => 0,
                'suppression_list_field_id' => 'str::email',
                'people' => [
                    [
                        'fields' => $fieldValues,
                    ],
                ],
            ];

            $response = $this->deliverPayload($submission, 'person/merge', $payload);

            if ($response === false) {
                return true;
            }

            $personId = $response['people'][0]['person_id'] ?? '';

            if (!$personId) {
                Integration::error($this, Craft::t('formie', 'API error: “{response}”. Sent payload {payload}', [
                    'response' => Json::encode($response),
                    'payload' => Json::encode($payload),
                ]), true);

                return false;
            }

            $email = ArrayHelper::remove($fieldValues, 'str::email');

            $payload = [
                'audience_id' => $this->listId,
                'async' => false,
                'people' => [
                    [
                        'email' => $email,
                        'subscribed' => true,
                    ],
                ],
            ];

            $response = $this->deliverPayload($submission, 'audience/subscribe', $payload, 'PUT');

            if ($response === false) {
                return true;
            }

            $contactId = $response['audience_id'] ?? '';

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
            $response = $this->request('POST', 'instance-schema/get');
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

        $rules[] = [['apiKey', 'dataCenter'], 'required'];

        return $rules;
    }

    protected function defineClient(): Client
    {
        return Craft::createGuzzleClient([
            'base_uri' => $this->getApiUrl() . 'v1/',
            'headers' => ['X-Api-Key' => App::parseEnv($this->apiKey)],
        ]);
    }


    // Private Methods
    // =========================================================================

    private function _convertFieldType($fieldType)
    {
        $fieldTypes = [
            'bool' => IntegrationField::TYPE_BOOLEAN,
            'integer' => IntegrationField::TYPE_NUMBER,
            'text_digits' => IntegrationField::TYPE_NUMBER,
            'decimal' => IntegrationField::TYPE_NUMBER,
            'date' => IntegrationField::TYPE_DATE,
            'time' => IntegrationField::TYPE_DATETIME,
            'multi_select' => IntegrationField::TYPE_ARRAY,
        ];

        return $fieldTypes[$fieldType] ?? IntegrationField::TYPE_STRING;
    }

    private function _getCustomFields($fields, $excludeNames = []): array
    {
        $customFields = [];

        foreach ($fields as $fieldDef) {
            $field = $fieldDef['field'] ?? [];

            // Exclude any names
            if (in_array($field['name'], $excludeNames)) {
                continue;
            }

            // Add in any options for some fields
            $options = [];
            $fieldOptions = $field['dic_items'] ?? [];

            foreach ($fieldOptions as $fieldOption) {
                $options[] = [
                    'label' => $fieldOption,
                    'value' => $fieldOption,
                ];
            }

            if ($options) {
                $options = [
                    'label' => $field['name'],
                    'options' => $options,
                ];
            }

            $customFields[] = new IntegrationField([
                'handle' => $field['id'],
                'name' => $field['name'],
                'type' => $this->_convertFieldType($field['display_type']),
                'sourceType' => $field['display_type'],
                'options' => $options,
            ]);
        }

        return $customFields;
    }
}