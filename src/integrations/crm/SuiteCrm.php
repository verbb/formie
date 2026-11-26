<?php
namespace verbb\formie\integrations\crm;

use verbb\formie\attributes\FormIntegrationSetting;
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

use Throwable;

use verbb\auth\base\OAuthProviderInterface;
use verbb\auth\providers\SuiteCrm as SuiteCrmProvider;

class SuiteCrm extends Crm implements OAuthProviderInterface
{
    // Constants
    // =========================================================================

    private const MODULE_NAME_PATTERN = '/^[A-Za-z][A-Za-z0-9_]{0,99}$/D';


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
        return 'SuiteCRM';
    }


    // Properties
    // =========================================================================

    public ?string $apiDomain = null;

    #[FormIntegrationSetting]
    public bool $mapToContact = false;

    #[FormIntegrationSetting]
    public ?array $contactFieldMapping = null;

    #[FormIntegrationSetting]
    public bool $mapToLead = false;

    #[FormIntegrationSetting]
    public ?array $leadFieldMapping = null;

    #[FormIntegrationSetting]
    public bool $mapToModule = false;

    #[FormIntegrationSetting]
    public ?string $moduleName = null;

    #[FormIntegrationSetting]
    public ?array $moduleFieldMapping = null;


    // Public Methods
    // =========================================================================

    public function getApiDomain(): string
    {
        return App::parseEnv($this->apiDomain);
    }

    public function getModuleName(): string
    {
        $moduleName = (string)$this->moduleName;

        // The module name becomes part of the API path, so only SuiteCRM module names are allowed.
        return preg_match(self::MODULE_NAME_PATTERN, $moduleName) ? $moduleName : '';
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

    public function fetchConfig(): IntegrationConfig
    {
        $settings = [];
        $dataKey = $this->settingsContext->dataKey;
        $moduleName = $this->getModuleName();

        try {
            // Full refreshes have no data key, but still need the schemas used to resolve mappings.
            if ($this->mapToContact && (!$dataKey || $dataKey === 'contact')) {
                $settings['contact'] = $this->_fetchModuleFields('Contacts');
            }

            if ($this->mapToLead && (!$dataKey || $dataKey === 'lead')) {
                $settings['lead'] = $this->_fetchModuleFields('Leads');
            }

            if ($this->mapToModule && $moduleName !== '' && (!$dataKey || $dataKey === 'module')) {
                // Forms can each use a different custom module, so each module keeps its own fields.
                $modules = array_values(array_filter((array)$this->getConfigValue('modules'), function($module) use ($moduleName) {
                    return ($module['id'] ?? null) !== $moduleName;
                }));

                $modules[] = [
                    'id' => $moduleName,
                    'name' => $moduleName,
                    'fields' => $this->_fetchModuleFields($moduleName),
                ];

                $settings['modules'] = $modules;
            }
        } catch (Throwable $e) {
            Integration::apiError($this, $e);
        }

        return new IntegrationConfig($settings);
    }


    // Protected Methods
    // =========================================================================

    protected function executePayload(Submission $submission): IntegrationResult
    {
        $this->beginPayloadDelivery($submission);

        try {
            if ($this->mapToModule && $this->getModuleName() === '') {
                Integration::error($this, Craft::t('formie', 'Invalid module name “{name}”.', [
                    'name' => (string)$this->moduleName,
                ]), true);

                return $this->resultForPayload(false);
            }

            foreach ($this->_getTargets() as [$module, $fieldMapping, $fieldSettings]) {
                $payload = [
                    'data' => [
                        'type' => $module,
                        'attributes' => $this->getFieldMappingValues($submission, $fieldMapping, $fieldSettings),
                    ],
                ];

                $response = $this->deliverPayload($submission, 'module', $payload);

                if ($response === false) {
                    return $this->resultForPayload(true);
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

        $contact = $this->getConfigValue('contact');
        $lead = $this->getConfigValue('lead');
        $module = $this->_getModuleFields();

        // Validate the following when saving form settings
        $rules[] = [
            ['contactFieldMapping'], 'validateFieldMapping', 'params' => $contact, 'when' => function($model) {
                return $model->enabled && $model->mapToContact;
            }, 'on' => [Integration::SCENARIO_FORM], 'skipOnEmpty' => false,
        ];

        $rules[] = [
            ['leadFieldMapping'], 'validateFieldMapping', 'params' => $lead, 'when' => function($model) {
                return $model->enabled && $model->mapToLead;
            }, 'on' => [Integration::SCENARIO_FORM], 'skipOnEmpty' => false,
        ];

        $rules[] = [
            ['moduleName'], 'required', 'when' => function($model) {
                return $model->enabled && $model->mapToModule;
            }, 'on' => [Integration::SCENARIO_FORM],
        ];

        $rules[] = [
            ['moduleName'], 'match', 'pattern' => self::MODULE_NAME_PATTERN, 'when' => function($model) {
                return $model->enabled && $model->mapToModule;
            }, 'on' => [Integration::SCENARIO_FORM],
        ];

        $rules[] = [
            ['moduleFieldMapping'], 'validateFieldMapping', 'params' => $module, 'when' => function($model) {
                return $model->enabled && $model->mapToModule;
            }, 'on' => [Integration::SCENARIO_FORM], 'skipOnEmpty' => false,
        ];

        return $rules;
    }

    protected function defineFormSettingsSchema(FormInterface $form): array
    {
        $schema = parent::defineFormSettingsSchema($form);
        $schema[] = SchemaHelper::lightswitchField([
            'name' => 'mapToContact',
            'label' => Craft::t('formie', 'Map to {name}', ['name' => 'Contact']),
            'instructions' => Craft::t('formie', 'Whether to map form data to {name} {label}.', ['name' => $this->displayName(), 'label' => 'Contacts']),
        ]);
        $schema[] = $this->createFieldMappingField([
            'name' => 'contactFieldMapping',
            'if' => 'mapToContact',
            'dataLabel' => 'Contact',
            'dataKey' => 'contact',
        ]);

        $schema[] = SchemaHelper::lightswitchField([
            'name' => 'mapToLead',
            'label' => Craft::t('formie', 'Map to {name}', ['name' => 'Lead']),
            'instructions' => Craft::t('formie', 'Whether to map form data to {name} {label}.', ['name' => $this->displayName(), 'label' => 'Leads']),
        ]);
        $schema[] = $this->createFieldMappingField([
            'name' => 'leadFieldMapping',
            'if' => 'mapToLead',
            'dataLabel' => 'Lead',
            'dataKey' => 'lead',
        ]);

        $modules = $this->getConfigValue('modules');
        $fieldCollections = [];

        foreach (is_array($modules) ? $modules : [] as $module) {
            $fieldCollections[] = [
                'id' => (string)($module['id'] ?? ''),
                'fields' => $this->convertIntegrationFieldsToSchema($module['fields'] ?? []),
            ];
        }

        $schema[] = SchemaHelper::lightswitchField([
            'name' => 'mapToModule',
            'label' => Craft::t('formie', 'Map to {name}', ['name' => 'Custom Module']),
            'instructions' => Craft::t('formie', 'Whether to map form data to a {name} custom module, such as one made with Module Builder.', ['name' => $this->displayName()]),
        ]);
        $schema[] = SchemaHelper::textField([
            'name' => 'moduleName',
            'if' => 'mapToModule',
            'label' => Craft::t('formie', 'Module Name'),
            'instructions' => Craft::t('formie', 'The API name of the custom module, such as `abc_Requests`. Click **Refresh** in the field mapping below to fetch its fields.'),
            'required' => true,
        ]);
        $schema[] = $this->createFieldMappingField([
            'name' => 'moduleFieldMapping',
            'if' => 'mapToModule',
            'dataLabel' => 'Custom Module',
            'dataKey' => 'module',
            'selectedCollectionField' => 'moduleName',
            'integrationFieldCollections' => $fieldCollections,
            'integrationFields' => $this->convertIntegrationFieldsToSchema($this->_getModuleFields()),
        ]);

        return $schema;
    }


    // Private Methods
    // =========================================================================

    /**
     * The records to create, in the order they're sent, as [module, field mapping, field settings].
     */
    private function _getTargets(): array
    {
        $targets = [];

        if ($this->mapToContact) {
            $targets[] = ['Contacts', $this->contactFieldMapping, 'contact'];
        }

        if ($this->mapToLead) {
            $targets[] = ['Leads', $this->leadFieldMapping, 'lead'];
        }

        if ($this->mapToModule) {
            // Until the module's fields have been fetched, mapped values are sent as they are rather than all being dropped.
            $targets[] = [$this->getModuleName(), $this->moduleFieldMapping, $this->_getModuleFields() ?: null];
        }

        return $targets;
    }

    private function _getModuleFields(): array
    {
        $moduleName = $this->getModuleName();
        $modules = $this->getConfigValue('modules');

        foreach (is_array($modules) ? $modules : [] as $module) {
            if ($moduleName !== '' && ($module['id'] ?? null) === $moduleName) {
                return is_array($module['fields'] ?? null) ? $module['fields'] : [];
            }
        }

        return [];
    }

    private function _fetchModuleFields(string $module): array
    {
        $response = $this->request('GET', 'meta/fields/' . $module);

        return $this->_getCustomFields($response['data']['attributes'] ?? []);
    }

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

        // V8 metadata keys are the field handles, including custom fields.
        foreach ($fields as $handle => $field) {
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
