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
use verbb\auth\providers\SuiteCrm as SuiteCrmProvider;

class SuiteCrm extends Crm implements OAuthProviderInterface
{
    // Static Methods
    // =========================================================================

    public static function supportsOAuthConnection(): bool
    {
        return true;
    }

    public static function getOAuthProviderClass(): string
    {
        return SuiteCrmProvider::class;
    }

    public static function displayName(): string
    {
        return Craft::t('formie', 'SuiteCRM');
    }


    // Properties
    // =========================================================================

    public ?string $apiDomain = null;
    public bool $mapToContact = false;
    public ?array $contactFieldMapping = null;


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

        try {
            if ($this->mapToContact) {
                $response = $this->request('GET', 'meta/Contacts');
                $fields = $response['meta']['fields'] ?? [];

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
            $contactValues = $this->getFieldMappingValues($submission, $this->contactFieldMapping, 'contact');

            if ($this->mapToContact) {
                $contactPayload = [
                    'data' => [
                        'type' => 'Contacts',
                        'attributes' => $contactValues,
                    ],
                ];

                $response = $this->deliverPayload($submission, 'module', $contactPayload);

                if ($response === false) {
                    return true;
                }

                $contactId = $response['data']['id'] ?? '';
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
            'bool' => IntegrationField::TYPE_BOOLEAN,
            'int' => IntegrationField::TYPE_NUMBER,
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
            $handle = $field['name'] ?? null;
            $type = $field['type'] ?? 'string';
            $readOnly = $field['read_only'] ?? false;

            if (!$handle || $readOnly) {
                continue;
            }

            $customFields[] = new IntegrationField([
                'handle' => $handle,
                'name' => ucwords(str_replace('_', ' ', $handle)),
                'type' => $this->_convertFieldType($type),
                'sourceType' => $type,
            ]);
        }

        return $customFields;
    }
}