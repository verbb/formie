<?php
namespace verbb\formie\integrations\crm;

use Craft;
use craft\helpers\App;
use craft\helpers\ArrayHelper;
use craft\helpers\Json;
use TheNetworg\OAuth2\Client\Provider\Azure;
use verbb\formie\base\Crm;
use verbb\formie\base\Integration;
use verbb\formie\elements\Submission;
use verbb\formie\events\MicrosoftDynamics365RequiredLevelsEvent;
use verbb\formie\events\MicrosoftDynamics365TargetSchemasEvent;
use verbb\formie\Formie;
use verbb\formie\models\IntegrationField;
use verbb\formie\models\IntegrationFormSettings;

class MicrosoftDynamics365 extends Crm
{
    // Properties
    // =========================================================================

    public $clientId;
    public $clientSecret;
    public $apiDomain;
    public $mapToContact = false;
    public $mapToLead = false;
    public $mapToOpportunity = false;
    public $mapToAccount = false;
    public $contactFieldMapping;
    public $leadFieldMapping;
    public $opportunityFieldMapping;
    public $accountFieldMapping;

    private $_entityOptions = [];

    // Constants
    // =========================================================================

    public const EVENT_MODIFY_REQUIRED_LEVELS = 'modifyRequiredLevels';
    public const EVENT_MODIFY_TARGET_SCHEMAS = 'modifyTargetSchemas';

    // OAuth Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
    public static function supportsOauthConnection(): bool
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function getClientId(): string
    {
        return App::parseEnv($this->clientId);
    }

    /**
     * @inheritDoc
     */
    public function getClientSecret(): string
    {
        return App::parseEnv($this->clientSecret);
    }

    /**
     * @inheritDoc
     */
    public function getOauthScope(): array
    {
        return [
            'openid',
            'profile',
            'email',
            'offline_access',
            'user.read',
        ];
    }

    /**
     * @inheritDoc
     */
    public function getOauthProvider()
    {
        return new Azure($this->getOauthProviderConfig());
    }

    /**
     * @inheritDoc
     */
    public function getOauthProviderConfig(): array
    {
        return array_merge(parent::getOauthProviderConfig(), [
            'defaultEndPointVersion' => '1.0',
            'resource' => App::parseEnv($this->apiDomain),
        ]);
    }


    // Public Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
    public static function displayName(): string
    {
        return Craft::t('formie', 'Microsoft Dynamics 365');
    }

    /**
     * @inheritDoc
     */
    public function getDescription(): string
    {
        return Craft::t('formie', 'Manage your Microsoft Dynamics 365 customers by providing important information on their conversion on your site.');
    }

    /**
     * @inheritDoc
     */
    public function defineRules(): array
    {
        $rules = parent::defineRules();

        $rules[] = [['clientId', 'clientSecret'], 'required'];

        $contact = $this->getFormSettingValue('contact');
        $lead = $this->getFormSettingValue('lead');
        $opportunity = $this->getFormSettingValue('opportunity');
        $account = $this->getFormSettingValue('account');

        // Validate the following when saving form settings
        $rules[] = [['contactFieldMapping'], 'validateFieldMapping', 'params' => $contact, 'when' => function($model) {
            return $model->enabled && $model->mapToContact;
        }, 'on' => [Integration::SCENARIO_FORM]];

        $rules[] = [['leadFieldMapping'], 'validateFieldMapping', 'params' => $lead, 'when' => function($model) {
            return $model->enabled && $model->mapToLead;
        }, 'on' => [Integration::SCENARIO_FORM]];

        $rules[] = [['opportunityFieldMapping'], 'validateFieldMapping', 'params' => $opportunity, 'when' => function($model) {
            return $model->enabled && $model->mapToOpportunity;
        }, 'on' => [Integration::SCENARIO_FORM]];

        $rules[] = [['accountFieldMapping'], 'validateFieldMapping', 'params' => $account, 'when' => function($model) {
            return $model->enabled && $model->mapToAccount;
        }, 'on' => [Integration::SCENARIO_FORM]];

        return $rules;
    }

    /**
     * @inheritDoc
     */
    public function fetchFormSettings()
    {
        $settings = [];

        try {
            $contactFields = $this->_getEntityFields('contact');
            $leadFields = $this->_getEntityFields('lead');
            $opportunityFields = $this->_getEntityFields('opportunity');
            $accountFields = $this->_getEntityFields('account');

            $settings = [
                'contact' => $contactFields,
                'lead' => $leadFields,
                'opportunity' => $opportunityFields,
                'account' => $accountFields,
            ];
        } catch (\Throwable $e) {
            Integration::apiError($this, $e);
        }

        return new IntegrationFormSettings($settings);
    }

    /**
     * @inheritDoc
     */
    public function sendPayload(Submission $submission): bool
    {
        try {
            $contactValues = $this->getFieldMappingValues($submission, $this->contactFieldMapping, 'contact');
            $leadValues = $this->getFieldMappingValues($submission, $this->leadFieldMapping, 'lead');
            $opportunityValues = $this->getFieldMappingValues($submission, $this->opportunityFieldMapping, 'opportunity');
            $accountValues = $this->getFieldMappingValues($submission, $this->accountFieldMapping, 'account');

            $contactId = null;
            $leadId = null;
            $opportunityId = null;
            $accountId = null;

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
                    $leadPayload['parentcontactid@odata.bind'] = $this->_formatLookupValue('contacts', $contactId);
                    $leadPayload['customerid_contact@odata.bind'] = $this->_formatLookupValue('contacts', $contactId);
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
        } catch (\Throwable $e) {
            Integration::apiError($this, $e);

            return false;
        }

        return true;
    }

    /**
     * @inheritDoc
     */
    public function request(string $method, string $uri, array $options = [])
    {
        // Recommended headers to pass for all web API requests
        // https://learn.microsoft.com/en-us/power-apps/developer/data-platform/webapi/compose-http-requests-handle-errors#http-headers
        $defaultOptions = [
            'headers' => [
                'Accept' => 'application/json',
                'OData-MaxVersion' => '4.0',
                'OData-Version' => '4.0',
                'If-None-Match' => null
            ]
        ];

        $options = ArrayHelper::merge($defaultOptions, $options);

        // Ensure a proper response is returned on POST/PATCH operations
        // https://learn.microsoft.com/en-us/power-apps/developer/data-platform/webapi/compose-http-requests-handle-errors#prefer-headers
        if ($method === 'POST' || $method === 'PATCH') {
            $options['headers']['Prefer'] = 'return=representation';
        }

        // Prevent create when using upsert
        // https://learn.microsoft.com/en-us/power-apps/developer/data-platform/webapi/perform-conditional-operations-using-web-api#prevent-create-in-upsert
        if ($method === 'PATCH') {
            $options['headers']['If-Match'] = '*';
        }

        return parent::request($method, $uri, $options);
    }

    /**
     * @inheritDoc
     */
    public function getClient()
    {
        if ($this->_client) {
            return $this->_client;
        }

        $token = $this->getToken();
        $url = rtrim(App::parseEnv($this->apiDomain), '/');

        $this->_client = Craft::createGuzzleClient([
            'base_uri' => "$url/api/data/v9.0/",
            'headers' => [
                'Authorization' => 'Bearer ' . ($token->accessToken ?? 'empty'),
                'Content-Type' => 'application/json',
            ],
        ]);

        // Always provide an authenticated client - so check first.
        // We can't always rely on the EOL of the token.
        try {
            $this->request('GET', 'WhoAmI');
        } catch (\Throwable $e) {
            if ($e->getCode() === 401) {
                // Force-refresh the token
                Formie::$plugin->getTokens()->refreshToken($token, true);

                // Then try again, with the new access token
                $this->_client = Craft::createGuzzleClient([
                    'base_uri' => "$url/api/data/v9.0/",
                    'headers' => [
                        'Authorization' => 'Bearer ' . ($token->accessToken ?? 'empty'),
                        'Content-Type' => 'application/json',
                    ],
                ]);
            }
        }

        return $this->_client;
    }


    // Protected Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
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

    /**
     * @inheritDoc
     */
    private function _getEntityFields($entity)
    {
        $metadataAttributesForSelect = [
            'AttributeType',
            'IsCustomAttribute',
            'IsValidForCreate',
            'IsValidForUpdate',
            'CanBeSecuredForCreate',
            'CanBeSecuredForUpdate',
            'LogicalName',
            'DisplayName',
            'RequiredLevel'
        ];

        // Fetch all defined fields on the entity
        // https://docs.microsoft.com/en-us/dynamics365/customer-engagement/web-api/contact?view=dynamics-ce-odata-9
        // https://docs.microsoft.com/en-us/dynamics365/customerengagement/on-premises/developer/entities/contact?view=op-9-1#BKMK_Address1_Telephone1
        $metadata = $this->request('GET', $this->_getEntityDefinitionsUri($entity), [
            'query' => [
                '$select' => 'Attributes',
                '$expand' => 'Attributes($select='. implode(',', $metadataAttributesForSelect) . ')'
            ]
        ]);

        // We also need to query DateTime attribute data to check if any are DateOnly
        $dateTimeAttributes = $this->request('GET', $this->_getEntityDefinitionsUri($entity, 'DateTime'), [
            'query' => [
                '$select' => 'SchemaName,LogicalName,DateTimeBehavior'
            ]
        ]);

        $dateTimeBehaviourValues = ArrayHelper::map($dateTimeAttributes, 'MetadataId','DateTimeBehavior.Value');

        $fields = [];
        $attributes = $metadata['Attributes'] ?? [];

        // Default to SystemRequired and ApplicationRequired
        $requiredLevels = [
            'SystemRequired',
            'ApplicationRequired'
        ];

        $event = new MicrosoftDynamics365RequiredLevelsEvent([
            'requiredLevels' => $requiredLevels,
        ]);

        $this->trigger(self::EVENT_MODIFY_REQUIRED_LEVELS, $event);

        foreach ($attributes as $field) {
            $label = $field['DisplayName']['UserLocalizedLabel']['Label'] ?? '';
            $customField = $field['IsCustomAttribute'] ?? false;
            $canCreate = $field['IsValidForCreate'] ?? false;
            $requiredLevel = $field['RequiredLevel']['Value'] ?? 'None';
            $type = $field['AttributeType'] ?? '';
            $odataType = $field['@odata.type'] ?? '';
            $metadataId = $field['MetadataId'] ?? '';

            // Pick the correct field handle, depending on custom fields
            if ($customField) {
                $handle = $field['SchemaName'] ?? '';
            } else {
                $handle = $field['LogicalName'] ?? '';
            }

            $key = $handle;

            $excludedTypes = [
                'Customer',
                'EntityName',
                'State',
                'Uniqueidentifier',
                'Virtual',
            ];

            if (!$label || !$handle || !$canCreate || in_array($type, $excludedTypes, true)) {
                continue;
            }

            // Relational fields need a special handle
            if ($odataType === '#Microsoft.Dynamics.CRM.LookupAttributeMetadata') {
                $handle .= '@odata.bind';
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

            // Index by handle for easy lookup with PickLists
            $fields[$key] = new IntegrationField([
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
                        ['label' => Craft::t('formie', 'False'), 'value' => 'false']
                    ]
                ];
            }
        }

        // Do another call for PickList fields, to populate any set options to pick from
        $response = $this->request('GET', $this->_getEntityDefinitionsUri($entity, 'Picklist'), [
            'query' => [
                '$select' => 'IsCustomAttribute,LogicalName,SchemaName',
                '$expand' => 'GlobalOptionSet($select=Options)'
            ]
        ]);
        $pickListFields = $response['value'] ?? [];

        foreach ($pickListFields as $pickListField) {
            $customField = $pickListField['IsCustomAttribute'] ?? false;
            $pickList = $pickListField['GlobalOptionSet']['Options'] ?? [];
            $options = [];

            // Pick the correct field handle, depending on custom fields
            if ($customField) {
                $handle = $pickListField['SchemaName'] ?? '';
            } else {
                $handle = $pickListField['LogicalName'] ?? '';
            }

            // Get the field to add options to
            $field = $fields[$handle] ?? null;

            if (!$handle || !$pickList || !$field) {
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

        // Do the same thing for any fields with an Owner, we have to do multiple queries.
        // This is for multiple entities, so have some cache.
        $this->_getEntityOwnerOptions($entity, $fields);

        // Reset array keys
        $fields = array_values($fields);

        // Sort by required field and then name
        ArrayHelper::multisort($fields, ['required', 'name'], [SORT_DESC, SORT_ASC]);

        return $fields;
    }

    /**
     * @inheritDoc
     */
    private function _getEntityOwnerOptions($entity, &$fields)
    {
        // Get all the fields that are relational
        $response = $this->request('GET', $this->_getEntityDefinitionsUri($entity, 'Lookup'), [
            'query' => [
                '$select' => 'IsCustomAttribute,LogicalName,SchemaName,Targets'
            ]
        ]);
        $relationFields = $response['value'] ?? [];

        // Define a schema so that we can query each entity according to the target (index)
        // the endpoint to query (entity) and what attributes to use for the label/value to pick from
        $targetSchemas = [
            'businessunit' => [
                'entity' => 'businessunits',
                'label' => 'name',
                'value' => 'businessunitid',
            ],
            'systemuser' => [
                'entity' => 'systemusers',
                'label' => 'fullname',
                'value' => 'systemuserid',
            ],
            'account' => [
                'entity' => 'accounts',
                'label' => 'name',
                'value' => 'accountid',
            ],
            'contact' => [
                'entity' => 'contacts',
                'label' => 'fullname',
                'value' => 'contactid',
            ],
            'lead' => [
                'entity' => 'leads',
                'label' => 'fullname',
                'value' => 'leadid',
            ],
            'transactioncurrency' => [
                'entity' => 'transactioncurrencies',
                'label' => 'currencyname',
                'value' => 'transactioncurrencyid',
            ],
            'team' => [
                'entity' => 'teams',
                'label' => 'name',
                'value' => 'teamid',
            ],
            'campaign' => [
                'entity' => 'campaigns',
                'label' => 'name',
                'value' => 'campaignid',
            ],
            'pricelevel' => [
                'entity' => 'pricelevels',
                'label' => 'name',
                'value' => 'pricelevelid',
            ],
        ];

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
                $select = [$targetSchema['label'], $targetSchema['value']];

                if ($target === 'systemuser') {
                    $select[] = 'applicationid';
                }

                // Fetch the entities and use the schema options to store. Be sure to limit and be performant.
                $response = $this->request('GET', $targetSchema['entity'], [
                    'query' => [
                        '$top' => $targetSchema['limit'] ?? '100',
                        '$select' => implode(',', $select),
                        '$orderby' => $targetSchema['orderby'] ?? null,
                        '$filter' => $targetSchema['filter'] ?? null
                    ],
                ]);

                $entities = $response['value'] ?? [];

                foreach ($entities as $entity) {
                    // Special-case for systemusers
                    if ($target === 'systemuser' && isset($entity['applicationid'])) {
                        continue;
                    }

                    $label = $entity[$targetSchema['label']] ?? '';
                    $value = $entity[$targetSchema['value']] ?? '';

                    $this->_entityOptions[$target][] = [
                        'label' => $label,
                        'value' => $this->_formatLookupValue($targetSchema['entity'], $value),
                    ];
                }
            }
        }

        // With all possible options populated, add the options into the fields
        foreach ($relationFields as $relationField) {
            $customField = $relationField['IsCustomAttribute'] ?? false;
            $targets = $relationField['Targets'] ?? [];
            $options = [];

            // Pick the correct field handle, depending on custom fields
            if ($customField) {
                $handle = $relationField['SchemaName'] ?? '';
            } else {
                $handle = $relationField['LogicalName'] ?? '';
            }

            foreach ($targets as $target) {
                // Get the options for this field
                if (isset($this->_entityOptions[$target])) {
                    $options = ArrayHelper::merge($options, $this->_entityOptions[$target]);
                }
            }

            // Get the field to add options to
            $field = $fields[$handle] ?? null;

            if (!$handle || !$field || !$options) {
                continue;
            }

            // Add the options to the field
            $field->options = [
                'label' => $field->name,
                'options' => $options,
            ];
        }
    }

    /**
     * Formats lookup values as entityname(GUID)
     *
     * @param $entity
     * @param $value
     * @return string
     */
    private function _formatLookupValue($entity, $value): string
    {
        return $entity . '(' . $value . ')';
    }

    /**
     * Format EntityDefintions uri request path
     *
     * @param $entity
     * @param $type
     * @return string
     */
    private function _getEntityDefinitionsUri($entity, $type = null): string
    {
        $path = "EntityDefinitions(LogicalName='$entity')";

        if ($type) {
            $path .= "/Attributes/Microsoft.Dynamics.CRM.{$type}AttributeMetadata";
        }

        return $path;
    }
}
