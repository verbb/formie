<?php
namespace verbb\formie\integrations\crm;

use verbb\formie\Formie;
use verbb\formie\base\Crm;
use verbb\formie\base\Integration;
use verbb\formie\elements\Submission;
use verbb\formie\helpers\ArrayHelper;
use verbb\formie\models\IntegrationField;
use verbb\formie\models\IntegrationFormSettings;

use Craft;
use craft\helpers\App;
use craft\helpers\Json;

use Throwable;

use verbb\auth\base\OAuthProviderInterface;
use verbb\auth\models\Token;
use verbb\auth\providers\Marketo as MarketoProvider;

class Marketo extends Crm implements OAuthProviderInterface
{
    // Static Methods
    // =========================================================================

    public static function supportsOAuthConnection(): bool
    {
        return true;
    }

    public static function getOAuthProviderClass(): string
    {
        return MarketoProvider::class;
    }

    public static function displayName(): string
    {
        return Craft::t('formie', 'Marketo');
    }


    // Properties
    // =========================================================================

    public ?string $apiDomain = null;
    public bool $mapToLead = false;
    public ?array $leadFieldMapping = null;


    // Public Methods
    // =========================================================================

    public function getApiDomain(): string
    {
        return App::parseEnv($this->apiDomain);
    }

    public function getOAuthProviderConfig(): array
    {
        $config = parent::getOAuthProviderConfig();
        $config['apiDomain'] = $this->getApiDomain();

        return $config;
    }

    public function getDescription(): string
    {
        return Craft::t('formie', 'Manage your {name} customers by providing important information on their conversion on your site.', ['name' => static::displayName()]);
    }

    public function fetchFormSettings(): IntegrationFormSettings
    {
        $settings = [];

        // Populate some options for some values
        try {
            if ($this->mapToLead) {
                $response = $this->request('GET', 'rest/v1/leads/describe.json');
                $fields = $response['result'] ?? [];

                $settings['lead'] = $this->_getCustomFields($fields);
            }
        } catch (Throwable $e) {
            Integration::apiError($this, $e);
        }

        return new IntegrationFormSettings($settings);
    }

    public function sendPayload(Submission $submission): bool
    {
        try {
            $leadValues = $this->getFieldMappingValues($submission, $this->leadFieldMapping, 'lead');

            if ($this->mapToLead) {
                $leadPayload = [
                    'action' => 'createOrUpdate',
                    'lookupField' => 'email',
                    'input' => [$leadValues],
                ];

                $response = $this->deliverPayload($submission, 'rest/v1/leads.json', $leadPayload);

                if ($response === false) {
                    return true;
                }
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

        $rules[] = [['apiDomain'], 'required'];

        $lead = $this->getFormSettingValue('lead');

        // Validate the following when saving form settings
        $rules[] = [
            ['leadFieldMapping'], 'validateFieldMapping', 'params' => $lead, 'when' => function($model) {
                return $model->enabled && $model->mapToLead;
            }, 'on' => [Integration::SCENARIO_FORM],
        ];

        return $rules;
    }


    // Private Methods
    // =========================================================================

    private function _convertFieldType(string $fieldType): string
    {
        $fieldTypes = [
            'boolean' => IntegrationField::TYPE_BOOLEAN,
            'int' => IntegrationField::TYPE_NUMBER,
            'integer' => IntegrationField::TYPE_NUMBER,
            'float' => IntegrationField::TYPE_FLOAT,
            'double' => IntegrationField::TYPE_FLOAT,
            'date' => IntegrationField::TYPE_DATE,
            'datetime' => IntegrationField::TYPE_DATETIME,
        ];

        return $fieldTypes[$fieldType] ?? IntegrationField::TYPE_STRING;
    }

    private function _getCustomFields(array $fields): array
    {
        $customFields = [];

        foreach ($fields as $field) {
            $restName = $field['rest']['name'] ?? null;
            $displayName = $field['displayName'] ?? null;
            $dataType = $field['dataType'] ?? 'string';
            $isReadOnly = $field['rest']['readOnly'] ?? false;

            // Skip read-only fields
            if ($isReadOnly || !$restName || !$displayName) {
                continue;
            }

            $customFields[] = new IntegrationField([
                'handle' => (string)$restName,
                'name' => (string)$displayName,
                'type' => $this->_convertFieldType($dataType),
                'sourceType' => $dataType,
            ]);
        }

        return $customFields;
    }
}