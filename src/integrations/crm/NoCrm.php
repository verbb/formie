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

class NoCrm extends Crm
{
    // Static Methods
    // =========================================================================

    public static function displayName(): string
    {
        return Craft::t('formie', 'noCRM');
    }
    

    // Properties
    // =========================================================================
    
    public ?string $apiKey = null;
    public ?string $apiDomain = null;
    public bool $mapToLead = false;
    public ?array $leadFieldMapping = null;


    // Public Methods
    // =========================================================================

    public function getDescription(): string
    {
        return Craft::t('formie', 'Manage your {name} customers by providing important information on their conversion on your site.', ['name' => static::displayName()]);
    }

    public function defineRules(): array
    {
        $rules = parent::defineRules();

        $rules[] = [['apiKey', 'apiDomain'], 'required'];

        $lead = $this->getFormSettingValue('lead');

        // Validate the following when saving form settings
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
            if ($this->mapToLead) {
                $fields = [];

                $settings['lead'] = array_merge([
                    new IntegrationField([
                        'handle' => 'title',
                        'name' => Craft::t('formie', 'Title'),
                        'required' => true,
                    ]),
                    new IntegrationField([
                        'handle' => 'description',
                        'name' => Craft::t('formie', 'Description'),
                        'required' => true,
                    ]),
                    new IntegrationField([
                        'handle' => 'user_id',
                        'name' => Craft::t('formie', 'Email'),
                        'required' => true,
                    ]),
                    new IntegrationField([
                        'handle' => 'tags',
                        'name' => Craft::t('formie', 'Tags'),
                    ]),
                    new IntegrationField([
                        'handle' => 'step',
                        'name' => Craft::t('formie', 'Step'),
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
            if ($this->mapToLead) {
                $leadValues = $this->getFieldMappingValues($submission, $this->leadFieldMapping, 'lead');

                $payload = $leadValues;

                $response = $this->deliverPayload($submission, 'leads', $payload);

                if ($response === false) {
                    return true;
                }

                $leadId = $response['id'] ?? '';

                if (!$leadId) {
                    Integration::error($this, Craft::t('formie', 'Missing return “leadId” {response}. Sent payload {payload}', [
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
            $response = $this->request('GET', 'ping');
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

        $url = rtrim(App::parseEnv($this->apiDomain), '/');

        return $this->_client = Craft::createGuzzleClient([
            'base_uri' => "$url/api/v2/",
            'headers' => ['X-API-KEY' => App::parseEnv($this->apiKey)],
        ]);
    }


    // Private Methods
    // =========================================================================

    private function _convertFieldType($fieldType)
    {
        $fieldTypes = [
            'date' => IntegrationField::TYPE_DATE,
            'datetime' => IntegrationField::TYPE_DATETIME,
            'number' => IntegrationField::TYPE_NUMBER,
        ];

        return $fieldTypes[$fieldType] ?? IntegrationField::TYPE_STRING;
    }

    private function _getCustomFields($fields, $excludeNames = []): array
    {
        $customFields = [];

        foreach ($fields as $key => $field) {
            $customFields[] = new IntegrationField([
                'handle' => (string)$field['id'],
                'name' => $field['name'],
                'type' => $this->_convertFieldType($field['type']),
                'sourceType' => $field['type'],
            ]);
        }

        return $customFields;
    }
}