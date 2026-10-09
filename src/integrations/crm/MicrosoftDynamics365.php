<?php
namespace verbb\formie\integrations\crm;

use verbb\formie\attributes\FormIntegrationSetting;
use verbb\formie\base\Crm;
use verbb\formie\base\FormInterface;
use verbb\formie\base\Integration;
use verbb\formie\elements\Submission;
use verbb\formie\events\MicrosoftDynamics365EntitiesEvent;
use verbb\formie\events\MicrosoftDynamics365RequiredLevelsEvent;
use verbb\formie\events\MicrosoftDynamics365TargetSchemasEvent;
use verbb\formie\helpers\ArrayHelper;
use verbb\formie\helpers\SchemaHelper;
use verbb\formie\models\IntegrationConfig;
use verbb\formie\models\IntegrationField;
use verbb\formie\models\IntegrationResult;
use verbb\formie\models\MicrosoftDynamics365Entity;

use Craft;
use craft\helpers\App;
use craft\helpers\Json;

use Throwable;

use verbb\auth\base\OAuthProviderInterface;
use verbb\auth\models\Token;
use verbb\auth\providers\Azure as AzureProvider;

use yii\base\InvalidConfigException;

class MicrosoftDynamics365 extends Crm implements OAuthProviderInterface
{
    // Static Methods
    // =========================================================================

    public static function supportsOAuthConnection(): bool
    {
        return true;
    }

    public static function getOAuthProviderClass(): string
    {
        return AzureProvider::class;
    }

    public static function displayName(): string
    {
        return 'Microsoft Dynamics 365';
    }

    protected static function defineOptionSources(): array
    {
        return [
            [
                'handle' => 'dynamics365-picklists',
                'label' => Craft::t('formie', 'Picklists'),
                'storage' => 'objects',
                'objectKeys' => ['contact', 'lead', 'opportunity', 'account', 'incident'],
                'objectLabels' => [
                    'contact' => Craft::t('formie', 'Contact'),
                    'lead' => Craft::t('formie', 'Lead'),
                    'opportunity' => Craft::t('formie', 'Opportunity'),
                    'account' => Craft::t('formie', 'Account'),
                    'incident' => Craft::t('formie', 'Case'),
                ],
                'collectionLabel' => Craft::t('formie', 'Entity'),
                'collectionInstructions' => Craft::t('formie', 'Choose the Dynamics 365 entity that owns the field.'),
                'collectionPlaceholder' => Craft::t('formie', 'Select an entity'),
                'remoteHandleLabel' => Craft::t('formie', 'Option Source'),
                'remoteHandleInstructions' => Craft::t('formie', 'Choose which picklist field supplies the options.'),
                'remoteHandlePlaceholder' => Craft::t('formie', 'Select a picklist'),
                'collectionRequiredMessage' => Craft::t('formie', 'Select a Dynamics 365 entity.'),
                'remoteHandleRequiredMessage' => Craft::t('formie', 'Select a picklist field.'),
                'emptyCollectionsWarning' => Craft::t('formie', 'No Dynamics 365 entities are available. Refresh the integration field mapping first.'),
                'emptySourcesWarning' => Craft::t('formie', 'No picklist fields are available. Refresh the integration field mapping first.'),
            ],
        ];
    }


    // Constants
    // =========================================================================

    public const EVENT_MODIFY_ENTITIES = 'modifyEntities';
    public const EVENT_MODIFY_REQUIRED_LEVELS = 'modifyRequiredLevels';
    public const EVENT_MODIFY_TARGET_SCHEMAS = 'modifyTargetSchemas';

    private const ENTITY_METADATA_CONFIG_PREFIX = '__microsoftDynamics365Entity__';


    // Properties
    // =========================================================================

    public ?string $apiDomain = null;
    public bool $impersonateUser = false;
    public string $impersonateHeader = 'CallerObjectId';
    public ?string $impersonateUserId = null;
    public ?string $apiVersion = 'v9.0';
    public ?string $tenant = 'common';
    #[FormIntegrationSetting]
    public bool $mapToContact = false;
    #[FormIntegrationSetting]
    public bool $mapToLead = false;
    #[FormIntegrationSetting]
    public bool $mapToOpportunity = false;
    #[FormIntegrationSetting]
    public bool $mapToAccount = false;
    #[FormIntegrationSetting]
    public bool $mapToIncident = false;
    #[FormIntegrationSetting]
    public ?array $contactFieldMapping = null;
    #[FormIntegrationSetting]
    public ?array $leadFieldMapping = null;
    #[FormIntegrationSetting]
    public ?array $opportunityFieldMapping = null;
    #[FormIntegrationSetting]
    public ?array $accountFieldMapping = null;
    #[FormIntegrationSetting]
    public ?array $incidentFieldMapping = null;
    #[FormIntegrationSetting]
    public array $customEntitySettings = [];

    private ?array $_entities = null;
    private array $_resolvedEntityMetadata = [];
    private array $_entityOptions = [];
    private array $_systemUsers = [];


    // Public Methods
    // =========================================================================

    public function getClassHandle(): string
    {
        return 'microsoft-dynamics-365';
    }

    public function getApiDomain(): string
    {
        return App::parseEnv($this->apiDomain);
    }

    public function getApiVersion(): string
    {
        return App::parseEnv($this->apiVersion);
    }

    public function getTenant(): string
    {
        return App::parseEnv($this->tenant);
    }

    public function getBaseApiUrl(?Token $token): ?string
    {
        $url = rtrim($this->getApiDomain(), '/');
        $apiVersion = $this->getApiVersion();

        return "$url/api/data/$apiVersion/";
    }

    public function getOAuthProviderConfig(): array
    {
        $config = parent::getOAuthProviderConfig();
        $config['baseApiUrl'] = fn(?Token $token) => $this->getBaseApiUrl($token);
        $config['defaultEndPointVersion'] = '1.0';
        $config['resource'] = $this->getApiDomain();
        $config['tenant'] = $this->getTenant();

        return $config;
    }

    public function getAuthorizationUrlOptions(): array
    {
        $options = parent::getAuthorizationUrlOptions();

        $options['scope'] = [
            'openid',
            'profile',
            'email',
            'offline_access',
            'user.read',
        ];

        return $options;
    }

    public function getDescription(): string
    {
        return Craft::t('formie', 'Manage your {name} customers by providing important information on their conversion on your site.', ['name' => static::displayName()]);
    }

    /**
     * @return array<string, MicrosoftDynamics365Entity>
     */
    public function getEntities(): array
    {
        if ($this->_entities !== null) {
            return $this->_entities;
        }

        $event = new MicrosoftDynamics365EntitiesEvent([
            'entities' => $this->defineEntities(),
        ]);

        $this->trigger(self::EVENT_MODIFY_ENTITIES, $event);

        $entities = [];
        $allowedSettings = array_flip($this->getFormSettingAttributes());

        foreach ($event->entities as $handle => $entity) {
            if (is_array($entity)) {
                $entity = new MicrosoftDynamics365Entity($entity);
            }

            if (!$entity instanceof MicrosoftDynamics365Entity) {
                throw new InvalidConfigException('Microsoft Dynamics 365 entities must be MicrosoftDynamics365Entity models or configuration arrays.');
            }

            if (!$entity->handle && is_string($handle)) {
                $entity->handle = $handle;
            }

            if (!$entity->validate()) {
                throw new InvalidConfigException('Invalid Microsoft Dynamics 365 entity “' . ($entity->handle ?: (string)$handle) . '”: ' . implode(' ', $entity->getErrorSummary(true)));
            }

            foreach ([$entity->getEnabledSetting(), $entity->getMappingSetting()] as $settingPath) {
                $settingRoot = strtok($settingPath, '.');

                if (!isset($allowedSettings[$settingRoot])) {
                    throw new InvalidConfigException("Microsoft Dynamics 365 entity “{$entity->handle}” targets unannotated form setting “{$settingRoot}”.");
                }
            }

            if (isset($entities[$entity->handle])) {
                throw new InvalidConfigException("Duplicate Microsoft Dynamics 365 entity handle: {$entity->handle}.");
            }

            $entities[$entity->handle] = $entity;
        }

        return $this->_entities = $entities;
    }

    public function fetchConfig(): IntegrationConfig
    {
        $settings = [];

        try {
            $handle = (string)$this->settingsContext->dataKey;
            $entity = $this->getEntities()[$handle] ?? null;

            if ($entity && $this->_isEntityEnabled($entity)) {
                $settings[$handle] = $this->_getEntityFields($entity->logicalName);

                if ($metadata = $this->_resolvedEntityMetadata[$entity->logicalName] ?? []) {
                    $settings[$this->_getEntityMetadataConfigKey($entity)] = $metadata;
                }
            }
        } catch (Throwable $e) {
            Integration::apiError($this, $e);
        }

        return new IntegrationConfig($settings);
    }

    public function validateEntityMappings(string $attribute): void
    {
        foreach ($this->getEntities() as $entity) {
            if (!$this->_isEntityEnabled($entity)) {
                continue;
            }

            $mappingSetting = $entity->getMappingSetting();
            $mapping = $this->_getEntityMapping($entity);
            $fields = $this->getConfigValue($entity->handle);

            if (!is_array($fields)) {
                continue;
            }

            foreach ($fields as $field) {
                if ($field instanceof IntegrationField && $field->required && $this->normalizeFieldMappingValue($mapping[$field->handle] ?? '') === '') {
                    $this->addError($mappingSetting, Craft::t('formie', '{name} must be mapped.', ['name' => $field->name]));
                    break;
                }
            }
        }
    }

    public function request(string $method, string $uri, array $options = [], bool $decodeJson = true): mixed
    {
        // Recommended headers to pass for all web API requests
        // https://learn.microsoft.com/en-us/power-apps/developer/data-platform/webapi/compose-http-requests-handle-errors#http-headers
        $defaultOptions = [
            'base_uri' => $this->getBaseApiUrl(null),
            'headers' => [
                'Accept' => 'application/json',
                'OData-MaxVersion' => '4.0',
                'OData-Version' => '4.0',
                'If-None-Match' => null
            ],
        ];

        $options = ArrayHelper::merge($defaultOptions, $options);

        // Ensure a proper response is returned on POST/PATCH operations
        // https://learn.microsoft.com/en-us/power-apps/developer/data-platform/webapi/compose-http-requests-handle-errors#prefer-headers
        if ($method === 'POST' || $method === 'PATCH') {
            $options['headers']['Prefer'] = 'return=representation';
        }

        // Impersonate user when creating records if enabled
        if ($this->impersonateUser && $method === 'POST') {
            $options['headers'][$this->impersonateHeader] = $this->impersonateUserId;
        }

        // Prevent create when using upsert
        // https://learn.microsoft.com/en-us/power-apps/developer/data-platform/webapi/perform-conditional-operations-using-web-api#prevent-create-in-upsert
        if ($method === 'PATCH') {
            $options['headers']['If-Match'] = '*';
        }

        return parent::request($method, $uri, $options, $decodeJson);
    }


    // Protected Methods
    // =========================================================================

    /**
     * @return array<string, MicrosoftDynamics365Entity|array>
     */
    protected function defineEntities(): array
    {
        return [
            'contact' => new MicrosoftDynamics365Entity([
                'label' => Craft::t('formie', 'Contact'),
                'pluralLabel' => Craft::t('formie', 'Contacts'),
                'logicalName' => 'contact',
                'entitySetName' => 'contacts',
                'primaryIdAttribute' => 'contactid',
                'enabledSetting' => 'mapToContact',
                'mappingSetting' => 'contactFieldMapping',
                'deliveryOrder' => 10,
            ]),
            'lead' => new MicrosoftDynamics365Entity([
                'label' => Craft::t('formie', 'Lead'),
                'pluralLabel' => Craft::t('formie', 'Leads'),
                'logicalName' => 'lead',
                'entitySetName' => 'leads',
                'primaryIdAttribute' => 'leadid',
                'enabledSetting' => 'mapToLead',
                'mappingSetting' => 'leadFieldMapping',
                'deliveryOrder' => 30,
            ]),
            'opportunity' => new MicrosoftDynamics365Entity([
                'label' => Craft::t('formie', 'Opportunity'),
                'pluralLabel' => Craft::t('formie', 'Opportunities'),
                'logicalName' => 'opportunity',
                'entitySetName' => 'opportunities',
                'primaryIdAttribute' => 'opportunityid',
                'enabledSetting' => 'mapToOpportunity',
                'mappingSetting' => 'opportunityFieldMapping',
                'deliveryOrder' => 40,
            ]),
            'account' => new MicrosoftDynamics365Entity([
                'label' => Craft::t('formie', 'Account'),
                'pluralLabel' => Craft::t('formie', 'Accounts'),
                'logicalName' => 'account',
                'entitySetName' => 'accounts',
                'primaryIdAttribute' => 'accountid',
                'enabledSetting' => 'mapToAccount',
                'mappingSetting' => 'accountFieldMapping',
                'deliveryOrder' => 20,
            ]),
            'incident' => new MicrosoftDynamics365Entity([
                'label' => Craft::t('formie', 'Incident'),
                'pluralLabel' => Craft::t('formie', 'Incidents'),
                'optionSourceLabel' => Craft::t('formie', 'Case'),
                'logicalName' => 'incident',
                'entitySetName' => 'incidents',
                'primaryIdAttribute' => 'incidentid',
                'enabledSetting' => 'mapToIncident',
                'mappingSetting' => 'incidentFieldMapping',
                'deliveryOrder' => 50,
            ]),
        ];
    }

    protected function executePayload(Submission $submission): IntegrationResult
    {
        $this->beginPayloadDelivery($submission);

        try {
            $entities = array_values($this->getEntities());
            usort($entities, fn(MicrosoftDynamics365Entity $a, MicrosoftDynamics365Entity $b) => $a->deliveryOrder <=> $b->deliveryOrder);
            $createdEntities = [];

            foreach ($entities as $entity) {
                if (!$this->_isEntityEnabled($entity)) {
                    continue;
                }

                $mapping = $this->_getEntityMapping($entity);
                $payload = $this->getFieldMappingValues($submission, $mapping, $this->getConfigValue($entity->handle));
                $metadata = $this->_getEntityMetadata($entity);
                $payload = $this->prepareEntityPayload($entity, $payload, $createdEntities);
                $endpoint = "{$metadata['entitySetName']}?\$select={$metadata['primaryIdAttribute']}";
                $response = $this->deliverPayload($submission, $endpoint, $payload);

                if ($response === false) {
                    return $this->resultForPayload(true);
                }

                $entityId = $response[$metadata['primaryIdAttribute']] ?? '';

                if (!$entityId) {
                    Integration::error($this, Craft::t('formie', 'Missing return “{attribute}” {response}. Sent payload {payload}', [
                        'attribute' => $metadata['primaryIdAttribute'],
                        'response' => Json::encode($response),
                        'payload' => Json::encode($payload),
                    ]), true);

                    return $this->resultForPayload(false);
                }

                $createdEntities[$entity->handle] = [
                    'id' => $entityId,
                    'entitySetName' => $metadata['entitySetName'],
                ];
            }
        } catch (Throwable $e) {
            Integration::apiError($this, $e);

            return $this->resultForPayload(false);
        }

        return $this->resultForPayload(true);
    }

    protected function prepareEntityPayload(MicrosoftDynamics365Entity $entity, array $payload, array $createdEntities): array
    {
        $contact = $createdEntities['contact'] ?? null;
        $account = $createdEntities['account'] ?? null;

        if ($entity->handle === 'account' && $contact) {
            $payload['primarycontactid@odata.bind'] = $this->_formatLookupValue($contact['entitySetName'], $contact['id']);
        }

        if ($entity->handle === 'lead') {
            if ($contact) {
                $contactLookupValue = $this->_formatLookupValue($contact['entitySetName'], $contact['id']);
                $payload['parentcontactid@odata.bind'] = $contactLookupValue;
                $payload['customerid_contact@odata.bind'] = $contactLookupValue;
            }

            if ($account) {
                $accountLookupValue = $this->_formatLookupValue($account['entitySetName'], $account['id']);
                $payload['parentaccountid@odata.bind'] = $accountLookupValue;
                $payload['customerid_account@odata.bind'] = $accountLookupValue;
            }
        }

        if ($entity->handle === 'opportunity') {
            if ($contact) {
                $payload['parentcontactid@odata.bind'] = $this->_formatLookupValue($contact['entitySetName'], $contact['id']);
            }

            if ($account) {
                $payload['parentaccountid@odata.bind'] = $this->_formatLookupValue($account['entitySetName'], $account['id']);
            }
        }

        if ($entity->handle === 'incident' && $contact) {
            $payload['customerid_contact@odata.bind'] = $this->_formatLookupValue($contact['entitySetName'], $contact['id']);
        }

        return $payload;
    }

    protected function defineRules(): array
    {
        $rules = parent::defineRules();
        $rules[] = [
            ['customEntitySettings'], 'validateEntityMappings', 'when' => function($model) {
                return $model->enabled;
            }, 'on' => [Integration::SCENARIO_FORM], 'skipOnEmpty' => false,
        ];

        return $rules;
    }

    protected function defineFormSettingsSchema(FormInterface $form): array
    {
        $schema = parent::defineFormSettingsSchema($form);

        foreach ($this->getEntities() as $entity) {
            $enabledSetting = $entity->getEnabledSetting();

            $schema[] = SchemaHelper::lightswitchField([
                'name' => $enabledSetting,
                'label' => Craft::t('formie', 'Map to {name}', ['name' => $entity->label]),
                'instructions' => Craft::t('formie', 'Whether to map form data to {name} {label}.', ['name' => $this->displayName(), 'label' => $entity->getPluralLabel()]),
            ]);
            $schema[] = $this->getIntegrationFieldMappingField([
                'name' => $entity->getMappingSetting(),
                'if' => $enabledSetting,
                'dataLabel' => $entity->label,
                'dataKey' => $entity->handle,
            ]);
        }

        return $schema;
    }

    protected function getOptionSourceCollections(IntegrationConfig $config, array $definition): array
    {
        if (($definition['handle'] ?? null) === 'dynamics365-picklists') {
            $definition['objectKeys'] = [];
            $definition['objectLabels'] = [];

            foreach ($this->getEntities() as $entity) {
                if (!$entity->exposePicklists) {
                    continue;
                }

                $definition['objectKeys'][] = $entity->handle;
                $definition['objectLabels'][$entity->handle] = $entity->getOptionSourceLabel();
            }
        }

        return parent::getOptionSourceCollections($config, $definition);
    }

    protected function convertFieldType($fieldType)
    {
        $fieldTypes = [
            'Decimal' => IntegrationField::TYPE_FLOAT,
            'Double' => IntegrationField::TYPE_FLOAT,
            'BigInt' => IntegrationField::TYPE_NUMBER,
            'Integer' => IntegrationField::TYPE_NUMBER,
            'Boolean' => IntegrationField::TYPE_BOOLEAN,
            'Money' => IntegrationField::TYPE_FLOAT,
            'Date' => IntegrationField::TYPE_DATE,
            'DateTime' => IntegrationField::TYPE_DATETIME,
        ];

        return $fieldTypes[$fieldType] ?? IntegrationField::TYPE_STRING;
    }


    // Private Methods
    // =========================================================================

    private function _isEntityEnabled(MicrosoftDynamics365Entity $entity): bool
    {
        return (bool)ArrayHelper::getValue($this, $entity->getEnabledSetting());
    }

    private function _getEntityMapping(MicrosoftDynamics365Entity $entity): array
    {
        $mapping = ArrayHelper::getValue($this, $entity->getMappingSetting());

        return is_array($mapping) ? $mapping : [];
    }

    private function _getEntityMetadataConfigKey(MicrosoftDynamics365Entity $entity): string
    {
        return self::ENTITY_METADATA_CONFIG_PREFIX . $entity->handle;
    }

    private function _getEntityMetadata(MicrosoftDynamics365Entity $entity): array
    {
        $metadata = $this->getConfigValue($this->_getEntityMetadataConfigKey($entity));
        $entitySetName = $entity->entitySetName ?: ($metadata['entitySetName'] ?? null);
        $primaryIdAttribute = $entity->primaryIdAttribute ?: ($metadata['primaryIdAttribute'] ?? null);

        if (!$entitySetName || !$primaryIdAttribute) {
            $metadata = $this->_fetchEntityMetadata($entity->logicalName);
            $entitySetName = $entitySetName ?: ($metadata['entitySetName'] ?? null);
            $primaryIdAttribute = $primaryIdAttribute ?: ($metadata['primaryIdAttribute'] ?? null);
        }

        foreach (['entitySetName' => $entitySetName, 'primaryIdAttribute' => $primaryIdAttribute] as $key => $value) {
            if (!is_string($value) || !preg_match('/^[a-zA-Z][a-zA-Z0-9_]*$/', $value)) {
                throw new InvalidConfigException("Unable to resolve a valid {$key} for Microsoft Dynamics 365 entity “{$entity->handle}”.");
            }
        }

        return [
            'entitySetName' => $entitySetName,
            'primaryIdAttribute' => $primaryIdAttribute,
        ];
    }

    private function _fetchEntityMetadata(string $logicalName): array
    {
        $metadata = $this->request('GET', $this->_getEntityDefinitionsUri($logicalName), [
            'query' => [
                '$select' => 'EntitySetName,PrimaryIdAttribute',
            ],
        ]);

        return $this->_resolvedEntityMetadata[$logicalName] = [
            'entitySetName' => $metadata['EntitySetName'] ?? null,
            'primaryIdAttribute' => $metadata['PrimaryIdAttribute'] ?? null,
        ];
    }

    private function _getEntityFields($entity): array
    {
        $fields = [];

        try {
            $metadataAttributesForSelect = [
                'AttributeType',
                'IsCustomAttribute',
                'IsValidForCreate',
                'IsValidForUpdate',
                'CanBeSecuredForCreate',
                'CanBeSecuredForUpdate',
                'LogicalName',
                'SchemaName',
                'DisplayName',
                'RequiredLevel',
            ];

            // Fetch all defined fields on the entity
            // https://docs.microsoft.com/en-us/dynamics365/customer-engagement/web-api/contact?view=dynamics-ce-odata-9
            // https://docs.microsoft.com/en-us/dynamics365/customerengagement/on-premises/developer/entities/contact?view=op-9-1#BKMK_Address1_Telephone1
            $metadata = $this->request('GET', $this->_getEntityDefinitionsUri($entity), [
                'query' => [
                    '$select' => 'Attributes,EntitySetName,PrimaryIdAttribute',
                    '$expand' => 'Attributes($select='. implode(',', $metadataAttributesForSelect) . ')',
                ],
            ]);

            $this->_resolvedEntityMetadata[$entity] = [
                'entitySetName' => $metadata['EntitySetName'] ?? null,
                'primaryIdAttribute' => $metadata['PrimaryIdAttribute'] ?? null,
            ];

            // We also need to query DateTime attribute data to check if any are DateOnly
            $dateTimeAttributes = $this->request('GET', $this->_getEntityDefinitionsUri($entity, 'DateTime'), [
                'query' => [
                    '$select' => 'SchemaName,LogicalName,DateTimeBehavior',
                ],
            ]);

            $dateTimeBehaviourValues = ArrayHelper::map($dateTimeAttributes, 'MetadataId', 'DateTimeBehavior.Value');

            $attributes = $metadata['Attributes'] ?? [];

            // Default to SystemRequired and ApplicationRequired
            $requiredLevels = [
                'SystemRequired',
                'ApplicationRequired',
            ];

            $event = new MicrosoftDynamics365RequiredLevelsEvent([
                'requiredLevels' => $requiredLevels,
            ]);

            $this->trigger(self::EVENT_MODIFY_REQUIRED_LEVELS, $event);

            foreach ($attributes as $field) {
                $label = $field['DisplayName']['UserLocalizedLabel']['Label'] ?? '';
                $logicalName = $field['LogicalName'] ?? '';
                $handle = $this->_getFieldHandle($field);
                $canCreate = $field['IsValidForCreate'] ?? false;
                $requiredLevel = $field['RequiredLevel']['Value'] ?? 'None';
                $type = $field['AttributeType'] ?? '';
                $odataType = $field['@odata.type'] ?? '';
                $metadataId = $field['MetadataId'] ?? '';

                $excludedTypes = [
                    'Customer',
                    'EntityName',
                    'State',
                    'Uniqueidentifier',
                    'Virtual',
                ];

                if (!$logicalName || !$label || !$handle || !$canCreate || in_array($type, $excludedTypes, true)) {
                    continue;
                }

                // DateTime attributes, just because the AttributeType is DateTime doesn't mean it actually accepts one!
                // If a field DateTimeBehaviour is set to DateOnly, it will not accept DateTime values ever!
                // https://learn.microsoft.com/en-us/dynamics365/customerengagement/on-premises/developer/behavior-format-date-time-attribute
                if ($type === 'DateTime') {
                    $dateTimeBehavior = $dateTimeBehaviourValues[$metadataId] ?? null;

                    if ($dateTimeBehavior === 'DateOnly') {
                        $type = 'Date';
                    }
                }

                // Index by `LogicalName` for easy lookup later with Picklist and Lookup fields
                $fields[$logicalName] = new IntegrationField([
                    'handle' => $handle,
                    'name' => $label,
                    'type' => $this->convertFieldType($type),
                    'required' => in_array($requiredLevel, $event->requiredLevels, true),
                ]);
            }

            // Add default true/false values for boolean fields
            foreach ($fields as $field) {
                if ($field->type === IntegrationField::TYPE_BOOLEAN) {
                    $field->options = [
                        'label' => Craft::t('formie', 'Default options'),
                        'options' => [
                            ['label' => Craft::t('formie', 'True'), 'value' => 'true'],
                            ['label' => Craft::t('formie', 'False'), 'value' => 'false'],
                        ]
                    ];
                }
            }

            // Do another call for PickList fields, to populate any set options to pick from
            try {
                $response = $this->request('GET', $this->_getEntityDefinitionsUri($entity, 'Picklist'), [
                    'query' => [
                        '$select' => 'LogicalName,SchemaName',
                        '$expand' => 'GlobalOptionSet($select=Options),OptionSet($select=Options)',
                    ]
                ]);

                $pickListFields = $response['value'] ?? [];

                foreach ($pickListFields as $pickListField) {
                    $pickList = $pickListField['GlobalOptionSet']['Options'] ?? [];
                    $options = [];

                    $logicalName = $pickListField['LogicalName'] ?? '';

                    // Get the field to add options to
                    $field = $fields[$logicalName] ?? null;

                    if (!$pickList || !$field) {
                        continue;
                    }

                    foreach ($pickList as $pickListOption) {
                        $options[] = [
                            'label' => $pickListOption['Label']['UserLocalizedLabel']['Label'] ?? '',
                            'value' => $pickListOption['Value'],
                        ];
                    }

                    if ($options) {
                        $field->options = [
                            'label' => $field->name,
                            'options' => $options,
                        ];
                    }
                }
            } catch (Throwable $e) {
                Integration::apiError($this, $e, false);
            }

            // Do the same thing for any fields with an Owner, we have to do multiple queries.
            // This can be for multiple entities, so have some cache.
            $this->_getEntityOwnerOptions($entity, $fields);

            // Add a list of system users for "Created By"
            $fields['createdby'] = new IntegrationField([
                'handle' => 'createdby',
                'name' => Craft::t('formie', 'Created By'),
                'options' => [
                    'label' => Craft::t('formie', 'Created By'),
                    'options' => $this->_getSystemUsersOptions(),
                ],
            ]);

            // Reset array keys
            $fields = array_values($fields);

            // Sort by required field and then name
            ArrayHelper::multisort($fields, ['required', 'name'], [SORT_DESC, SORT_ASC]);
        } catch (Throwable $e) {
            Integration::apiError($this, $e, false);
        }

        return $fields;
    }

    private function _getEntityOwnerOptions($entity, $fields): void
    {
        try {
            // Get all the fields that are relational
            $response = $this->request('GET', $this->_getEntityDefinitionsUri($entity, 'Lookup'), [
                'query' => [
                    '$select' => 'LogicalName,SchemaName,Targets',
                ],
            ]);

            $relationFields = $response['value'] ?? [];

            // Create a unique list of entities we need to fetch things for.
            $entities = [];

            foreach ($relationFields as $relationField) {
                $entities[] = $relationField['Targets'] ?? [];
            }

            // Get unique entities used (destructure for performance)
            $entities = $entities ? array_values(array_unique(array_merge(...$entities))) : [];

            // Filter out some core entities, which seem to require admin priviledges
            $entities = array_values(array_filter($entities, function($entityName) {
                return !str_starts_with($entityName, 'msdyn');
            }));

            // For each entity, define a schema so that we can query each entity according to the target (index)
            // the endpoint to query (entity) and what attributes to use for the label/value to pick from
            $targetSchemas = [];

            // Build a filter string to read `(LogicalName eq 'account') or (LogicalName eq 'businessunit')`
            // to fetch just the entities we have Lookup fields for.
            $entityDefinitionFilter = array_map(function($entityName) {
                return "(LogicalName eq '$entityName')";
            }, $entities);

            // Note there's a max filter limit of 25, so we need to chunk
            $entityDefinitionFilterChunks = array_chunk($entityDefinitionFilter, 25);

            // Get all entity definitions
            foreach ($entityDefinitionFilterChunks as $entityDefinitionFilterChunk) {
                try {
                    $response = $this->request('GET', 'EntityDefinitions', [
                        'query' => [
                            '$filter' => implode(' or ', $entityDefinitionFilterChunk),
                            '$select' => 'DisplayName,LogicalName,SchemaName,PrimaryIdAttribute,PrimaryNameAttribute,LogicalCollectionName,EntitySetName',
                        ],
                    ]);

                    $entityDefinitions = $response['value'] ?? [];

                    foreach ($entityDefinitions as $entityDefinition) {
                        $entitySchema = [
                            'entity' => $entityDefinition['EntitySetName'],
                            'label' => $entityDefinition['PrimaryNameAttribute'],
                            'value' => $entityDefinition['PrimaryIdAttribute'],
                            'select' => [$entityDefinition['PrimaryNameAttribute'], $entityDefinition['PrimaryIdAttribute']],
                        ];

                        // Special handling for system users
                        if ($entityDefinition['LogicalName'] === 'systemuser') {
                            $entitySchema['select'][] = 'applicationid';
                            $entitySchema['orderby'] = $entityDefinition['PrimaryNameAttribute'];

                            // Exclude system accounts that are application default
                            $entitySchema['filter'] = 'applicationid eq null and isdisabled eq false';
                        }

                        $targetSchemas[$entityDefinition['LogicalName']] = $entitySchema;
                    }
                } catch (Throwable $e) {
                    Integration::apiError($this, $e, false);
                }
            }

            $event = new MicrosoftDynamics365TargetSchemasEvent([
                'targetSchemas' => $targetSchemas,
            ]);

            $this->trigger(self::EVENT_MODIFY_TARGET_SCHEMAS, $event);

            $targetSchemas = ArrayHelper::merge($targetSchemas, $event->targetSchemas);

            // Populate our cached entity options, cached across multiple calls because we only need to
            // fetch the collection once, for each entity type. Subsequent fields can re-use the options.
            foreach ($relationFields as $relationField) {
                $targets = $relationField['Targets'] ?? [];

                foreach ($targets as $target) {
                    // Get the schema definition to do stuff
                    $targetSchema = $targetSchemas[$target] ?? '';

                    if (!$targetSchema) {
                        continue;
                    }

                    // Provide a little cache, if we've already fetched items, no need to do again
                    if (isset($this->_entityOptions[$target])) {
                        continue;
                    }

                    // We don't really need that much from the entities
                    $select = $targetSchema['select'] ?? [$targetSchema['label'], $targetSchema['value']];

                    // Fetch the entities and use the schema options to store. Be sure to limit and be performant.
                    try {
                        $response = $this->request('GET', $targetSchema['entity'], [
                            'query' => [
                                '$expand' => $targetSchema['expand'] ?? null,
                                '$filter' => $targetSchema['filter'] ?? null,
                                '$orderby' => $targetSchema['orderby'] ?? null,
                                '$select' => implode(',', $select),
                                '$top' => $targetSchema['limit'] ?? '100'
                            ],
                        ]);

                        $entities = $response['value'] ?? [];

                        foreach ($entities as $entity) {
                            $label = $entity[$targetSchema['label']] ?? '';
                            $value = $entity[$targetSchema['value']] ?? '';

                            $this->_entityOptions[$target][] = [
                                'label' => $label,
                                'value' => $this->_formatLookupValue($targetSchema['entity'], $value),
                            ];
                        }
                    } catch (Throwable $e) {
                        Integration::apiError($this, $e, false);
                    }
                }
            }

            // With all possible options populated, add the options into the fields
            foreach ($relationFields as $relationField) {
                $targets = $relationField['Targets'] ?? [];
                $options = [];

                foreach ($targets as $target) {
                    // Get the options for this field
                    if (isset($this->_entityOptions[$target])) {
                        $options = ArrayHelper::merge($options, $this->_entityOptions[$target]);
                    }
                }

                $logicalName = $relationField['LogicalName'] ?? '';

                // Get the field to add options to
                $field = $fields[$logicalName] ?? null;

                if (!$field || !$options) {
                    continue;
                }

                // Add the options to the field
                $field->options = [
                    'label' => $field->name,
                    'options' => $options,
                ];
            }
        } catch (Throwable $e) {
            Integration::apiError($this, $e, false);
        }
    }

    private function _formatLookupValue($entity, $value): string
    {
        return $entity . '(' . $value . ')';
    }

    private function _getEntityDefinitionsUri($entity, $type = null): string
    {
        $path = "EntityDefinitions(LogicalName='$entity')";

        if ($type) {
            $path .= "/Attributes/Microsoft.Dynamics.CRM.{$type}AttributeMetadata";
        }

        return $path;
    }

    private function _getFieldHandle(array $field): string
    {
        $customField = $field['IsCustomAttribute'] ?? null;
        $type = $field['@odata.type'] ?? '';

        // Relational fields use the `SchemaName`, but only if a custom field or entity
        if ($type === '#Microsoft.Dynamics.CRM.LookupAttributeMetadata') {
            $schemaName = $field['SchemaName'] ?? '';
            $logicalName = $field['LogicalName'] ?? '';
            $handle = ($customField) ? $schemaName : $logicalName;

            return $handle . '@odata.bind';
        }

        return $field['LogicalName'] ?? '';
    }

    private function _getSystemUsersOptions(): array
    {
        if ($this->_systemUsers) {
            return $this->_systemUsers;
        }

        try {
            $response = $this->request('GET', 'systemusers', [
                'query' => [
                    '$top' => '100',
                    '$select' => 'fullname,systemuserid,applicationid',
                    '$orderby' => 'fullname',
                    '$filter' => 'applicationid eq null and invitestatuscode eq 4 and isdisabled eq false',
                ]
            ]);

            foreach (($response['value'] ?? []) as $user) {
                $this->_systemUsers[] = ['label' => $user['fullname'], 'value' => 'systemusers(' . $user['systemuserid'] . ')'];
            }
        } catch (Throwable $e) {
            Integration::apiError($this, $e, false);
        }

        return $this->_systemUsers;
    }
}
