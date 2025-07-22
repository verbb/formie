<?php
namespace verbb\formie\integrations\crm;

use verbb\formie\base\Crm;
use verbb\formie\base\Integration;
use verbb\formie\elements\Submission;
use verbb\formie\events\ModifyFieldIntegrationValueEvent;
use verbb\formie\events\ModifyFieldIntegrationValuesEvent;
use verbb\formie\helpers\ArrayHelper;
use verbb\formie\helpers\StringHelper;
use verbb\formie\models\IntegrationField;
use verbb\formie\models\IntegrationFormSettings;

use Craft;
use craft\helpers\App;
use craft\helpers\Json;

use yii\base\Event;

use GuzzleHttp\Client;

use Throwable;

class Attio extends Crm
{
    // Static Methods
    // =========================================================================

    public static function displayName(): string
    {
        return Craft::t('formie', 'Attio');
    }
    

    // Constants
    // =========================================================================

    public const TYPE_PERSONAL_NAME = 'personal-name';
    public const TYPE_EMAIL_ADDRESS = 'email-address';
    public const TYPE_RECORD_REFERENCE = 'record-reference';
    public const TYPE_PHONE_NUMBER = 'phone-number';
    public const TYPE_CURRENCY = 'currency';
    public const TYPE_LOCATION = 'location';
    public const TYPE_RATING = 'rating';
    public const TYPE_STATUS = 'status';
    public const TYPE_INTERACTION = 'interaction';
    public const TYPE_ACTOR_REFERENCE = 'actor-reference';
    public const TYPE_TIMESTAMP = 'timestamp';
    public const TYPE_SUBFIELD = 'subfield';


    // Properties
    // =========================================================================
    
    public ?string $apiKey = null;
    public bool $mapToPeople = false;
    public ?array $peopleFieldMapping = null;


    // Public Methods
    // =========================================================================

    public function init(): void
    {
        parent::init();

        // Handle our custom field types for this integration
        Event::on(self::class, self::EVENT_MODIFY_FIELD_MAPPING_VALUES, function(ModifyFieldIntegrationValuesEvent $event) {
            $subFieldValues = [];

            // First check against all sub-fields that actually have a value
            foreach ($event->fieldValues as $fieldKey => $field) {
                if (str_contains($fieldKey, '.')) {
                    $subFieldValues[] = explode('.', $fieldKey)[0] ?? null;
                }
            }

            $subFieldValues = array_unique($subFieldValues);

            // We have to ensure all sub-fields have a value, even if `null`.
            foreach ($event->fieldSettings as $field) {
                if ($field->type === self::TYPE_SUBFIELD) {
                    if (!array_key_exists($field->handle, $event->fieldMapping)) {
                        $mappingValue = false;

                        // Ensure that we're actually mapping other items for this sub-field
                        foreach ($subFieldValues as $subFieldValue) {
                            if (str_starts_with($field->handle, $subFieldValue . '.')) {
                                $mappingValue = true;
                            }
                        }

                        if ($mappingValue) {
                            $event->fieldValues[$field->handle] = '';
                        }
                    }
                }
            }

        });

        Event::on(self::class, self::EVENT_MODIFY_FIELD_MAPPING_VALUE, function(ModifyFieldIntegrationValueEvent $event) {
            if ($event->integrationField->getType() === self::TYPE_EMAIL_ADDRESS) {
                if (!is_array($event->value)) {
                    $event->value = [$event->value];
                }
            }
        });
    }

    public function getDescription(): string
    {
        return Craft::t('formie', 'Manage your {name} customers by providing important information on their conversion on your site.', ['name' => static::displayName()]);
    }

    public function fetchFormSettings(): IntegrationFormSettings
    {
        $settings = [];

        try {
            if ($this->mapToPeople) {
                $response = $this->request('GET', 'objects/people/attributes');
                $fields = $response['data'] ?? [];

                $settings['people'] = $this->_getCustomFields($fields);
            }
        } catch (Throwable $e) {
            Integration::apiError($this, $e);
        }

        return new IntegrationFormSettings($settings);
    }

    public function sendPayload(Submission $submission): bool
    {
        try {
            if ($this->mapToPeople) {
                $peopleValues = $this->getFieldMappingValues($submission, $this->peopleFieldMapping, 'people');

                $payload = [
                    'data' => [
                        'values' => ArrayHelper::expand($peopleValues),
                    ],
                ];

                $response = $this->deliverPayload($submission, 'objects/people/records?matching_attribute=email_addresses', $payload, 'PUT');

                if ($response === false) {
                    return true;
                }

                $peopleId = $response['data']['id']['record_id'] ?? '';

                if (!$peopleId) {
                    Integration::error($this, Craft::t('formie', 'Missing return “peopleId” {response}. Sent payload {payload}', [
                        'response' => Json::encode($response),
                        'payload' => Json::encode($payload),
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
            $response = $this->request('GET', 'objects');
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

        $rules[] = [['apiKey'], 'required'];

        $people = $this->getFormSettingValue('people');

        // Validate the following when saving form settings
        $rules[] = [
            ['peopleFieldMapping'], 'validateFieldMapping', 'params' => $people, 'when' => function($model) {
                return $model->enabled && $model->mapToPeople;
            }, 'on' => [Integration::SCENARIO_FORM],
        ];

        return $rules;
    }

    protected function defineClient(): Client
    {
        return Craft::createGuzzleClient([
            'base_uri' => 'https://api.attio.com/v2/',
            'headers' => ['Authorization' => 'Bearer ' . App::parseEnv($this->apiKey)],
        ]);
    }


    // Private Methods
    // =========================================================================

    private function _convertFieldType(string $fieldType): string
    {
        $fieldTypes = [
            'date' => IntegrationField::TYPE_DATE,
            'datetime' => IntegrationField::TYPE_DATETIME,
            'number' => IntegrationField::TYPE_NUMBER,

            // Custom types for this integration
            'personal-name' => self::TYPE_PERSONAL_NAME,
            'email-address' => self::TYPE_EMAIL_ADDRESS,
            'record-reference' => self::TYPE_RECORD_REFERENCE,
            'phone-number' => self::TYPE_PHONE_NUMBER,
            'location' => self::TYPE_LOCATION,
            'interaction' => self::TYPE_INTERACTION,
            'actor-reference' => self::TYPE_ACTOR_REFERENCE,
            'timestamp' => self::TYPE_TIMESTAMP,
        ];

        return $fieldTypes[$fieldType] ?? IntegrationField::TYPE_STRING;
    }

    private function _getCustomFields(array $fields): array
    {
        $customFields = [];

        foreach ($fields as $key => $field) {
            if (!($field['is_writable'] ?? false)) {
                continue;
            }

            // Some field types can return multiple fields
            $fieldType = $this->_convertFieldType($field['type']);

            if ($fieldType === self::TYPE_PERSONAL_NAME) {
                $nameParts = [
                    'first_name' => Craft::t('formie', 'First Name'),
                    'last_name' => Craft::t('formie', 'Last Name'),
                    'full_name' => Craft::t('formie', 'Full Name'),
                ];

                foreach ($nameParts as $suffix => $label) {
                    $customFields[] = $this->_getCustomField($field, [
                        'handleSuffix' => $suffix,
                        'nameSuffix' => $label,
                        'type' => self::TYPE_SUBFIELD,
                    ]);
                }
            } else if ($fieldType === self::TYPE_LOCATION) {
                $locationParts = [
                    'line_1' => Craft::t('formie', 'Line 1'),
                    'line_2' => Craft::t('formie', 'Line 2'),
                    'line_3' => Craft::t('formie', 'Line 3'),
                    'locality' => Craft::t('formie', 'City'),
                    'region' => Craft::t('formie', 'Region'),
                    'postcode' => Craft::t('formie', 'Postcode'),
                    'country_code' => Craft::t('formie', 'Country'),
                    'latitude' => Craft::t('formie', 'Latitude'),
                    'longitude' => Craft::t('formie', 'Longitude'),
                ];

                foreach ($locationParts as $suffix => $label) {
                    $customFields[] = $this->_getCustomField($field, [
                        'handleSuffix' => $suffix,
                        'nameSuffix' => $label,
                        'type' => self::TYPE_SUBFIELD,
                    ]);
                }
            } else {
                $customFields[] = $this->_getCustomField($field);
            }
        }

        return $customFields;
    }

    private function _getCustomField(array $field, array $extra = []): IntegrationField
    {
        $fieldType = $this->_convertFieldType($field['type']);

        $handle = $field['api_slug'];
        $name = $field['title'];

        // Add suffixes if provided
        if ($handleSuffix = ArrayHelper::remove($extra, 'handleSuffix')) {
            $handle .= '.' . $handleSuffix;
        }

        if ($nameSuffix = ArrayHelper::remove($extra, 'nameSuffix')) {
            $name .= ': ' . $nameSuffix;
        }

        return new IntegrationField(array_merge([
            'handle' => $handle,
            'name' => $name,
            'type' => $fieldType,
            'sourceType' => $field['type'],
            'required' => $field['is_required'] ?? false,
            'defaultValue' => '',
        ], $extra));
    }
}