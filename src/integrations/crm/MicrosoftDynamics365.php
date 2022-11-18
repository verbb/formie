<?php
namespace verbb\formie\integrations\crm;

use verbb\formie\Formie;
use verbb\formie\base\Crm;
use verbb\formie\base\Integration;
use verbb\formie\elements\Submission;
use verbb\formie\models\IntegrationField;
use verbb\formie\models\IntegrationFormSettings;

use Craft;
use craft\helpers\App;
use craft\helpers\Json;

use League\OAuth1\Client\Server\Server as Oauth1Provider;
use League\OAuth2\Client\Provider\AbstractProvider;
use TheNetworg\OAuth2\Client\Provider\Azure;

use Throwable;

use GuzzleHttp\Client;

class MicrosoftDynamics365 extends Crm
{
    // Static Methods
    // =========================================================================

    public static function supportsOauthConnection(): bool
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public static function displayName(): string
    {
        return Craft::t('formie', 'Microsoft Dynamics 365');
    }
    

    // Properties
    // =========================================================================
    
    public ?string $clientId = null;
    public ?string $clientSecret = null;
    public ?string $apiDomain = null;
    public bool $mapToContact = false;
    public bool $mapToLead = false;
    public bool $mapToOpportunity = false;
    public bool $mapToAccount = false;
    public ?array $contactFieldMapping = null;
    public ?array $leadFieldMapping = null;
    public ?array $opportunityFieldMapping = null;
    public ?array $accountFieldMapping = null;

    private array $_entityOptions = [];


    // Public Methods
    // =========================================================================

    public function getClientId(): string
    {
        return App::parseEnv($this->clientId);
    }

    public function getClientSecret(): string
    {
        return App::parseEnv($this->clientSecret);
    }

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

    public function getOauthProvider(): AbstractProvider|Oauth1Provider
    {
        return new Azure($this->getOauthProviderConfig());
    }

    public function getOauthProviderConfig(): array
    {
        return array_merge(parent::getOauthProviderConfig(), [
            'defaultEndPointVersion' => '1.0',
            'resource' => App::parseEnv($this->apiDomain),
        ]);
    }

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

        return $rules;
    }

    public function fetchFormSettings(): IntegrationFormSettings
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
                    $accountPayload['primarycontactid@odata.bind'] = 'contacts(' . $contactId . ')';
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
                    $leadPayload['parentcontactid@odata.bind'] = 'contacts(' . $contactId . ')';
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
                    $accountPayload['parentcontactid@odata.bind'] = 'contacts(' . $contactId . ')';
                }

                if ($accountId) {
                    $accountPayload['parentaccountid@odata.bind'] = 'accounts(' . $accountId . ')';
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
        } catch (Throwable $e) {
            Integration::apiError($this, $e);

            return false;
        }

        return true;
    }

    public function request(string $method, string $uri, array $options = [])
    {
        // Dynamics doesn't return a response for POST requests by default. Riiiiight...
        if ($method === 'POST') {
            $options['headers'] = [
                'Prefer' => 'return=representation',
            ];
        }

        return parent::request($method, $uri, $options);
    }

    public function getClient(): Client
    {
        if ($this->_client) {
            return $this->_client;
        }

        $token = $this->getToken();

        if (!$token) {
            Integration::apiError($this, 'Token not found for integration.', true);
        }

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
            $response = $this->request('GET', 'contacts');
        } catch (Throwable $e) {
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


    // Private Methods
    // =========================================================================

    private function _convertFieldType($fieldType)
    {
        $fieldTypes = [
            'Decimal' => IntegrationField::TYPE_FLOAT,
            'Double' => IntegrationField::TYPE_FLOAT,
            'BigInt' => IntegrationField::TYPE_NUMBER,
            'Integer' => IntegrationField::TYPE_NUMBER,
            'Boolean' => IntegrationField::TYPE_BOOLEAN,
            'Money' => IntegrationField::TYPE_FLOAT,
            'DateTime' => IntegrationField::TYPE_DATETIME,
        ];

        return $fieldTypes[$fieldType] ?? IntegrationField::TYPE_STRING;
    }

    private function _getEntityFields($entity): array
    {
        // Fetch all defined fields on the entity
        // https://docs.microsoft.com/en-us/dynamics365/customer-engagement/web-api/contact?view=dynamics-ce-odata-9
        // https://docs.microsoft.com/en-us/dynamics365/customerengagement/on-premises/developer/entities/contact?view=op-9-1#BKMK_Address1_Telephone1
        $response = $this->request('GET', "EntityDefinitions(LogicalName='$entity')?\$select=Attributes&\$expand=Attributes(\$select=AttributeType,IsCustomAttribute,IsValidForCreate,IsValidForUpdate,CanBeSecuredForCreate,CanBeSecuredForUpdate,LogicalName,SchemaName,DisplayName,RequiredLevel)");

        $fields = [];
        $attributes = $response['Attributes'] ?? [];

        foreach ($attributes as $field) {
            $label = $field['DisplayName']['UserLocalizedLabel']['Label'] ?? '';
            $customField = $field['IsCustomAttribute'] ?? false;
            $canCreate = $field['IsValidForCreate'] ?? false;
            $requiredLevel = $field['RequiredLevel']['Value'] ?? 'None';
            $type = $field['AttributeType'] ?? '';
            $odataType = $field['@odata.type'] ?? '';

            // Pick the correct field handle, depending on custom fields
            if ($customField) {
                $handle = $field['SchemaName'] ?? '';
            } else {
                $handle = $field['LogicalName'] ?? '';
            }

            $key = $handle;

            $excludedTypes = [
                'Virtual',
                'Uniqueidentifier',
                'Customer',
                'EntityName',
            ];

            if (!$label || !$handle || !$canCreate || in_array($type, $excludedTypes)) {
                continue;
            }

            // Relational fields need a special handle
            if ($odataType === '#Microsoft.Dynamics.CRM.LookupAttributeMetadata') {
                $handle .= '@odata.bind';
            }

            // Index by handle for easy lookup with PickLists
            $fields[$key] = new IntegrationField([
                'handle' => $handle,
                'name' => $label,
                'type' => $this->_convertFieldType($type),
                'required' => !($requiredLevel === 'None'),
            ]);
        }

        // Do another call for PickList fields, to populate any set options to pick from
        $response = $this->request('GET', "EntityDefinitions(LogicalName='$entity')/Attributes/Microsoft.Dynamics.CRM.PicklistAttributeMetadata?\$select=IsCustomAttribute,LogicalName,SchemaName&\$expand=GlobalOptionSet(\$select=Options)");
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
        // This can be for multiple entities, so have some cache.
        $this->_getEntityOwnerOptions($entity, $fields);

        // Reset array keys
        $fields = array_values($fields);

        // Sort alphabetically by label
        usort($fields, function($a, $b) {
            return strcmp($a->name, $b->name);
        });

        return $fields;
    }

    private function _getEntityOwnerOptions($entityName, $fields): void
    {
        // Get all the fields that are relational
        $response = $this->request('GET', "EntityDefinitions(LogicalName='$entityName')/Attributes/Microsoft.Dynamics.CRM.LookupAttributeMetadata?\$select=IsCustomAttribute,LogicalName,SchemaName,Targets");
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
                'label' => 'fullname',
                'value' => 'campaignid',
            ],
            'pricelevel' => [
                'entity' => 'pricelevels',
                'label' => 'name',
                'value' => 'pricelevelid',
            ],
        ];

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
                        '$top' => '100',
                        '$select' => implode(',', $select),
                    ],
                ]);

                $entities = $response['value'] ?? [];

                foreach ($entities as $entity) {
                    // Special-case for systemusers
                    if (($target === 'systemuser') && isset($entity['applicationid'])) {
                        continue;
                    }

                    $label = $entity[$targetSchema['label']] ?? '';
                    $value = $entity[$targetSchema['value']] ?? '';

                    $this->_entityOptions[$target][] = [
                        'label' => $label,
                        'value' => $targetSchema['entity'] . '(' . $value . ')',
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
                    $options = array_merge($options, $this->_entityOptions[$target]);
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
}
