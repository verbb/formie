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

class CiviCrm extends Crm
{
    // Static Methods
    // =========================================================================

    public static function displayName(): string
    {
        return Craft::t('formie', 'CiviCRM');
    }
    

    // Properties
    // =========================================================================
    
    public ?string $apiKey = null;
    public ?string $siteKey = null;
    public ?string $apiDomain = null;
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
                $response = $this->request('GET', 'Contact/getFields');
                $fields = $response['values'] ?? [];

                $settings['contact'] = $this->_getCustomFields($fields);
            }
        } catch (Throwable $e) {
            Integration::apiError($this, $e);
        }

        return new IntegrationFormSettings($settings);
    }

    public function sendPayload(Submission $submission): bool
    {
        try {
            if ($this->mapToContact) {
                $contactValues = $this->getFieldMappingValues($submission, $this->contactFieldMapping, 'contact');

                $payload = [
                    'values' => $contactValues,
                ];

                $response = $this->deliverPayload($submission, 'Contact/create', $payload);

                if ($response === false) {
                    return true;
                }

                $contactId = $response['values'][0]['id'] ?? '';

                if (!$contactId) {
                    Integration::error($this, Craft::t('formie', 'Missing return “contactId” {response}. Sent payload {payload}', [
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
            $response = $this->request('GET', 'Contact/getFields');
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

        $rules[] = [['apiKey', 'siteKey', 'apiDomain'], 'required'];

        $contact = $this->getFormSettingValue('contact');

        // Validate the following when saving form settings
        $rules[] = [
            ['contactFieldMapping'], 'validateFieldMapping', 'params' => $contact, 'when' => function($model) {
                return $model->enabled && $model->mapToContact;
            }, 'on' => [Integration::SCENARIO_FORM],
        ];

        return $rules;
    }

    protected function defineClient(): Client
    {
        $url = rtrim(App::parseEnv($this->apiDomain), '/');

        return Craft::createGuzzleClient([
            'base_uri' => "$url/civicrm/ajax/api4/",
            'headers' => [
                'X-Civi-Key' => App::parseEnv($this->siteKey),
                'X-Civi-Auth' => 'Bearer ' . App::parseEnv($this->apiKey),
            ],
        ]);
    }


    // Private Methods
    // =========================================================================

    private function _convertFieldType(string $fieldType): string
    {
        $fieldTypes = [
            'Date' => IntegrationField::TYPE_DATE,
            'Number' => IntegrationField::TYPE_NUMBER,
        ];

        return $fieldTypes[$fieldType] ?? IntegrationField::TYPE_STRING;
    }

    private function _getCustomFields(array $fields): array
    {
        $customFields = [];

        foreach ($fields as $key => $field) {
            if (($field['readonly'] ?? false)) {
                continue;
            }

            $type = $field['input_type'] ?? IntegrationField::TYPE_STRING;

            $customFields[] = new IntegrationField([
                'handle' => $field['name'],
                'name' => $field['label'],
                'type' => $this->_convertFieldType($type),
                'sourceType' => $type,
                'required' => $field['required'] ?? false,
            ]);
        }

        return $customFields;
    }
}