<?php
namespace verbb\formie\integrations\crm;

use verbb\formie\attributes\FormIntegrationSetting;
use verbb\formie\attributes\Sensitive;
use verbb\formie\base\Crm;
use verbb\formie\base\FormInterface;
use verbb\formie\base\Integration;
use verbb\formie\elements\Submission;
use verbb\formie\helpers\SchemaHelper;
use verbb\formie\models\IntegrationConfig;
use verbb\formie\models\IntegrationField;
use verbb\formie\models\IntegrationResult;

use Craft;
use craft\helpers\App;
use craft\helpers\Json;

use Throwable;

use GuzzleHttp\Client;

class CiviCrm extends Crm
{
    // Static Methods
    // =========================================================================

    public static function displayName(): string
    {
        return 'CiviCRM';
    }


    // Properties
    // =========================================================================

    #[Sensitive]
    public ?string $apiKey = null;
    #[Sensitive]
    public ?string $siteKey = null;
    public ?string $apiDomain = null;
    #[FormIntegrationSetting]
    public bool $mapToContact = false;
    #[FormIntegrationSetting]
    public ?array $contactFieldMapping = null;


    // Public Methods
    // =========================================================================

    public function getDescription(): string
    {
        return Craft::t('formie', 'Manage your {name} customers by providing important information on their conversion on your site.', ['name' => static::displayName()]);
    }

    public function fetchConfig(): IntegrationConfig
    {
        $settings = [];

        try {
            if ($this->mapToContact && $this->settingsContext->dataKey === 'contact') {
                $response = $this->request('GET', 'Contact/getFields');
                $fields = $response['values'] ?? [];

                $settings['contact'] = $this->_getCustomFields($fields);
            }
        } catch (Throwable $e) {
            Integration::apiError($this, $e);
        }

        return new IntegrationConfig($settings);
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

    protected function executePayload(Submission $submission): IntegrationResult
    {
        $this->beginPayloadDelivery($submission);

        try {
            if ($this->mapToContact) {
                $contactValues = $this->getFieldMappingValues($submission, $this->contactFieldMapping, 'contact');

                $payload = [
                    'values' => $contactValues,
                ];

                $response = $this->deliverPayload($submission, 'Contact/create', $payload);

                if ($response === false) {
                    return $this->resultForPayload(true);
                }

                $contactId = $response['values'][0]['id'] ?? '';

                if (!$contactId) {
                    Integration::error($this, Craft::t('formie', 'Missing return “contactId” {response}. Sent payload {payload}', [
                        'response' => Json::encode($response),
                        'payload' => Json::encode($payload),
                    ]), true);

                    return $this->resultForPayload(false);
                }
            }
        } catch (Throwable $e) {
            Integration::apiError($this, $e);

            return $this->resultForPayload(false);
        }

        return $this->resultForPayload(true);
    }

    protected function defineRules(): array
    {
        $rules = parent::defineRules();

        $rules[] = [['apiKey', 'siteKey', 'apiDomain'], 'required'];

        $contact = $this->getConfigValue('contact');

        // Validate the following when saving form settings
        $rules[] = [
            ['contactFieldMapping'], 'validateFieldMapping', 'params' => $contact, 'when' => function($model) {
                return $model->enabled && $model->mapToContact;
            }, 'on' => [Integration::SCENARIO_FORM], 'skipOnEmpty' => false,
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

    protected function defineFormSettingsSchema(FormInterface $form): array
    {
        $schema = parent::defineFormSettingsSchema($form);
        $schema[] = SchemaHelper::lightswitchField([
            'name' => 'mapToContact',
            'label' => Craft::t('formie', 'Map to {name}', ['name' => 'Contact']),
            'instructions' => Craft::t('formie', 'Whether to map form data to {name} {label}.', ['name' => $this->displayName(), 'label' => 'Contacts']),
        ]);
        $schema[] = $this->getIntegrationFieldMappingField([
            'name' => 'contactFieldMapping',
            'if' => 'mapToContact',
            'dataLabel' => 'Contact',
            'dataKey' => 'contact',
        ]);

        return $schema;
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
