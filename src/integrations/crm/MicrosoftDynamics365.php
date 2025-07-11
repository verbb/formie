<?php
namespace verbb\formie\integrations\crm;

use verbb\formie\Formie;
use verbb\formie\base\Crm;
use verbb\formie\base\Integration;
use verbb\formie\elements\Submission;
use verbb\formie\events\MicrosoftDynamics365RequiredLevelsEvent;
use verbb\formie\events\MicrosoftDynamics365TargetSchemasEvent;
use verbb\formie\helpers\ArrayHelper;
use verbb\formie\models\IntegrationField;
use verbb\formie\models\IntegrationFormSettings;

use Craft;
use craft\helpers\App;
use craft\helpers\Json;

use Throwable;

use verbb\auth\base\OAuthProviderInterface;
use verbb\auth\models\Token;
use verbb\auth\providers\Azure as AzureProvider;

class MicrosoftDynamics365 extends Crm implements OAuthProviderInterface
{
    // Constants
    // =========================================================================

    public const EVENT_MODIFY_REQUIRED_LEVELS = 'modifyRequiredLevels';
    public const EVENT_MODIFY_TARGET_SCHEMAS = 'modifyTargetSchemas';
    


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
        return Craft::t('formie', 'Microsoft Dynamics 365');
    }
    

    // Properties
    // =========================================================================
    
    public ?string $apiDomain = null;
    public bool $impersonateUser = false;
    public string $impersonateHeader = 'CallerObjectId';
    public ?string $impersonateUserId = null;
    public ?string $apiVersion = 'v9.0';
    public ?string $tenant = 'common';
    public bool $mapToContact = false;
    public bool $mapToLead = false;
    public bool $mapToOpportunity = false;
    public bool $mapToAccount = false;
    public bool $mapToIncident = false;
    public ?array $contactFieldMapping = null;
    public ?array $leadFieldMapping = null;
    public ?array $opportunityFieldMapping = null;
    public ?array $accountFieldMapping = null;
    public ?array $incidentFieldMapping = null;

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

    public function fetchFormSettings(): IntegrationFormSettings
    {
        $settings = [];

        try {
            if ($this->mapToContact) {
                $settings['contact'] = $this->_getEntityFields('contact');
            }

            if ($this->mapToLead) {
                $settings['lead'] = $this->_getEntityFields('lead');
            }

            if ($this->mapToOpportunity) {
                $settings['opportunity'] = $this->_getEntityFields('opportunity');
            }

            if ($this->mapToAccount) {
                $settings['account'] = $this->_getEntityFields('account');
            }

            if ($this->mapToIncident) {
                $settings['incident'] = $this->_getEntityFields('incident');
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
            $leadValues = $this->getFieldMappingValues($submission, $this->leadFieldMapping, 'lead');
            $opportunityValues = $this->getFieldMappingValues($submission, $this->opportunityFieldMapping, 'opportunity');
            $accountValues = $this->getFieldMappingValues($submission, $this->accountFieldMapping, 'account');
            $incidentValues = $this->getFieldMappingValues($submission, $this->incidentFieldMapping, 'incident');

            $contactId = null;
            $leadId = null;
            $opportunityId = null;
            $accountId = null;
            $incidentId = null;

            if ($this->mapToContact) {
                $contactPayload = $contactValues;

                $response = $this->deliverPayload($submission, 'contacts?$select=contactid', $contactPayload);

                if ($response === false) {
                    return true;
                }

                $contactId = $response['contactid'] ?? '';

                if (!$contactId) {
                    Integration::error($this, Craft::t('formie', 'Missing return “contactId” {response}. Sent payload {payload}', [
                        'response' => Json::encode($response),
                        'payload' => Json::encode($contactPayload),
                    ]), true);

                    return false;
                }
            }

            if ($this->mapToAccount) {
                $accountPayload = $accountValues;

                if ($contactId) {
                    $accountPayload['primarycontactid@odata.bind'] = $this->_formatLookupValue('contacts', $contactId);
                }

                $response = $this->deliverPayload($submission, 'accounts?$select=accountid', $accountPayload);

                if ($response === false) {
                    return true;
                }

                $accountId = $response['accountid'] ?? '';

                if (!$accountId) {
                    Integration::error($this, Craft::t('formie', 'Missing return accountid {response}. Sent payload {payload}', [
                        'response' => Json::encode($response),
                        'payload' => Json::encode($accountPayload),
                    ]), true);

                    return false;
                }
            }

            if ($this->mapToLead) {
                $leadPayload = $leadValues;

                if ($contactId) {
                    $contactLookupValue = $this->_formatLookupValue('contacts', $contactId);

                    $leadPayload['parentcontactid@odata.bind'] = $contactLookupValue;
                    $leadPayload['customerid_contact@odata.bind'] = $contactLookupValue;
                }

                if ($accountId) {
                    $accountLookupValue = $this->_formatLookupValue('accounts', $accountId);

                    $leadPayload['parentaccountid@odata.bind'] = $accountLookupValue;
                    $leadPayload['customerid_account@odata.bind'] = $accountLookupValue;
                }

                $response = $this->deliverPayload($submission, 'leads?$select=leadid', $leadPayload);

                if ($response === false) {
                    return true;
                }

                $leadId = $response['leadid'] ?? '';

                if (!$leadId) {
                    Integration::error($this, Craft::t('formie', 'Missing return leadid {response}. Sent payload {payload}', [
                        'response' => Json::encode($response),
                        'payload' => Json::encode($leadPayload),
                    ]), true);

                    return false;
                }
            }

            if ($this->mapToOpportunity) {
                $opportunityPayload = $opportunityValues;

                if ($contactId) {
                    $accountPayload['parentcontactid@odata.bind'] = $this->_formatLookupValue('contacts', $contactId);
                }

                if ($accountId) {
                    $accountPayload['parentaccountid@odata.bind'] = $this->_formatLookupValue('accounts', $accountId);
                }

                $response = $this->deliverPayload($submission, 'opportunities?$select=opportunityid', $opportunityPayload);

                if ($response === false) {
                    return true;
                }

                $opportunityId = $response['opportunityid'] ?? '';

                if (!$opportunityId) {
                    Integration::error($this, Craft::t('formie', 'Missing return opportunityid {response}. Sent payload {payload}', [
                        'response' => Json::encode($response),
                        'payload' => Json::encode($opportunityPayload),
                    ]), true);

                    return false;
                }
            }

            if ($this->mapToIncident) {
                $incidentPayload = $incidentValues;

                if ($contactId) {
                    $incidentPayload['customerid_contact@odata.bind'] = $this->_formatLookupValue('contacts', $contactId);
                }

                $response = $this->deliverPayload($submission, 'incidents?$select=incidentid', $incidentPayload);

                if ($response === false) {
                    return true;
                }

                $incidentId = $response['incidentid'] ?? '';

                if (!$incidentId) {
                    Integration::error($this, Craft::t('formie', 'Missing return incidentid {response}. Sent payload {payload}', [
                        'response' => Json::encode($response),
                        'payload' => Json::encode($incidentPayload),
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

    protected function defineRules(): array
    {
        $rules = parent::defineRules();

        $contact = $this->getFormSettingValue('contact');
        $lead = $this->getFormSettingValue('lead');
        $opportunity = $this->getFormSettingValue('opportunity');
        $account = $this->getFormSettingValue('account');
        $incident = $this->getFormSettingValue('incident');

        // Validate the following when saving form settings
        $rules[] = [
            ['contactFieldMapping'], 'validateFieldMapping', 'params' => $contact, 'when' => function($model) {
                return $model->enabled && $model->mapToContact;
            }, 'on' => [Integration::SCENARIO_FORM],
        ];

        $rules[] = [
            ['leadFieldMapping'], 'validateFieldMapping', 'params' => $lead, 'when' => function($model) {
                return $model->enabled && $model->mapToLead;
            }, 'on' => [Integration::SCENARIO_FORM],
        ];

        $rules[] = [
            ['opportunityFieldMapping'], 'validateFieldMapping', 'params' => $opportunity, 'when' => function($model) {
                return $model->enabled && $model->mapToOpportunity;
            }, 'on' => [Integration::SCENARIO_FORM],
        ];

        $rules[] = [
            ['accountFieldMapping'], 'validateFieldMapping', 'params' => $account, 'when' => function($model) {
                return $model->enabled && $model->mapToAccount;
            }, 'on' => [Integration::SCENARIO_FORM],
        ];

        $rules[] = [
            ['incidentFieldMapping'], 'validateFieldMapping', 'params' => $incident, 'when' => function($model) {
                return $model->enabled && $model->mapToIncident;
            }, 'on' => [Integration::SCENARIO_FORM]
        ];

        return $rules;
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
                    '$select' => 'Attributes',
                    '$expand' => 'Attributes($select='. implode(',', $metadataAttributesForSelect) . ')',
                ],
            ]);

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
            $entities = array_values(array_unique(array_merge(...$entities)));

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
