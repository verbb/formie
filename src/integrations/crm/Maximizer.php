<?php
namespace verbb\formie\integrations\crm;

use verbb\formie\base\Crm;
use verbb\formie\base\Integration;
use verbb\formie\elements\Submission;
use verbb\formie\helpers\ArrayHelper;
use verbb\formie\models\IntegrationField;
use verbb\formie\models\IntegrationFormSettings;

use Craft;
use craft\helpers\App;
use craft\helpers\Json;

use GuzzleHttp\Client;

use Throwable;
use Exception;

class Maximizer extends Crm
{
    // Static Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
    public static function displayName(): string
    {
        return Craft::t('formie', 'Maximizer');
    }
    

    // Properties
    // =========================================================================
    
    public ?string $username = null;
    public ?string $password = null;
    public ?string $webAccessUrl = null;
    public ?string $databaseId = null;
    public ?string $vendorId = null;
    public ?string $appKey = null;
    public bool $mapToContact = false;
    public bool $mapToOpportunity = false;
    public ?array $contactFieldMapping = null;
    public ?array $opportunityFieldMapping = null;


    // Public Methods
    // =========================================================================

    public function getDescription(): string
    {
        return Craft::t('formie', 'Manage your Maximizer customers by providing important information on their conversion on your site.');
    }

    /**
     * @inheritDoc
     */
    public function defineRules(): array
    {
        $rules = parent::defineRules();

        $rules[] = [['username', 'password', 'webAccessUrl', 'databaseId', 'vendorId', 'appKey'], 'required'];

        $contact = $this->getFormSettingValue('contact');
        $opportunity = $this->getFormSettingValue('opportunity');

        // Validate the following when saving form settings
        $rules[] = [
            ['contactFieldMapping'], 'validateFieldMapping', 'params' => $contact, 'when' => function($model) {
                return $model->enabled && $model->mapToContact;
            }, 'on' => [Integration::SCENARIO_FORM],
        ];

        $rules[] = [
            ['contactFieldMapping'], 'validateFieldMapping', 'params' => $opportunity, 'when' => function($model) {
                return $model->enabled && $model->mapToOpportunity;
            }, 'on' => [Integration::SCENARIO_FORM],
        ];

        return $rules;
    }

    public function fetchFormSettings(): IntegrationFormSettings
    {
        $settings = [];

        try {
            $response = $this->request('POST', 'AbEntryGetFieldInfo', [
                'json' => [
                    'AbEntry' => [
                        'Options' => [
                            'Complex' => true,
                        ],
                    ],
                ],
            ]);

            $fields = $response['AbEntry']['Data']['properties'] ?? [];
            $contactFields = $this->_getCustomFields($fields);

            $response = $this->request('POST', 'OpportunityGetFieldInfo', [
                'json' => [
                    'Opportunity' => [
                        'Options' => [
                            'Complex' => true,
                        ],
                    ],
                ],
            ]);

            $fields = $response['Opportunity']['Data']['properties'] ?? [];
            $opportunityFields = $this->_getCustomFields($fields);

            $settings = [
                'contact' => $contactFields,
                'opportunity' => $opportunityFields,
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
            $opportunityValues = $this->getFieldMappingValues($submission, $this->opportunityFieldMapping, 'opportunity');

            $contactId = null;

            if ($this->mapToContact) {
                $contactPayload = [
                    'AbEntry' => [
                        'Data' => array_merge([
                            'Key' => null,
                            'Type' => 'Individual',
                            'Lead' => true,
                        ], $this->_prepPayload($contactValues)),
                    ],
                ];

                $response = $this->deliverPayload($submission, 'AbEntryCreate', $contactPayload);

                if ($response === false) {
                    return true;
                }

                $contactId = $response['AbEntry']['Data']['Key'] ?? null;
                $code = $response['Code'] ?? -1;

                if ($code !== 0) {
                    throw new Exception(Json::encode($response));
                }
            }

            if ($this->mapToOpportunity) {
                $opportunityPayload = [
                    'Opportunity' => [
                        'Data' => array_merge([
                            'Key' => null,
                            'AbEntryKey' => $contactId,
                        ], $this->_prepPayload($opportunityValues)),
                    ],
                ];

                $response = $this->deliverPayload($submission, 'OpportunityCreate', $opportunityPayload);

                if ($response === false) {
                    return true;
                }

                $opportunityId = $response['Opportunity']['Data']['Key'] ?? null;
                $code = $response['Code'] ?? -1;

                if ($code !== 0) {
                    throw new Exception(Json::encode($response));
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
        // Fetch and merge the token in for each request, which isn't a header, but part of every request
        if ($method === 'POST') {
            $options['json']['Token'] = $this->getClient()->getConfig()['headers']['X-Token'] ?? null;
        }

        return parent::request($method, $uri, $options);
    }

    public function fetchConnection(): bool
    {
        try {
            $response = $this->request('POST', 'AbEntryRead', [
                'json' => [
                    'AbEntry' => [
                        'Criteria' => [
                            'SearchQuery' => [
                                'CompanyName' => [
                                    '$LIKE' => '%',
                                ],
                            ],
                        ],
                        'Scope' => [
                            'Fields' => [
                                'Key' => 1,
                                'CompanyName' => 1,
                            ],
                        ],
                    ],
                ],
            ]);

            $errorMessage = $response['Msg'][0]['Message'] ?? '';

            if ($errorMessage) {
                throw new Exception(Json::encode($response));
            }
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

        // From the Web Access URL, get the API Base URL
        $webAccessUrl = App::parseEnv($this->webAccessUrl);
        $baseApiUrl = file_get_contents($webAccessUrl . '?request=api');

        // Then, fetch the token we need to use on every request for this session (10min)
        $request = Craft::createGuzzleClient()->request('POST', "$baseApiUrl/Data.svc/json/Authenticate", [
            'json' => [
                'Database' => App::parseEnv($this->databaseId),
                'UID' => App::parseEnv($this->username),
                'Password' => App::parseEnv($this->password),
                'VendorId' => App::parseEnv($this->vendorId),
                'AppKey' => App::parseEnv($this->appKey),
            ],
        ]);

        $response = Json::decode((string)$request->getBody());
        $token = $response['Data']['Token'] ?? '';

        return $this->_client = Craft::createGuzzleClient([
            'base_uri' => "$baseApiUrl/Data.svc/json/",
            'headers' => [
                'Content-Type' => 'application/json',

                // Save it in the header, so we can attach it on every request later
                'X-Token' => $token,
            ],
        ]);
    }


    // Private Methods
    // =========================================================================

    private function _convertFieldType($fieldType)
    {
        $fieldTypes = [
            'BooleanField' => IntegrationField::TYPE_BOOLEAN,
            'IntegerField' => IntegrationField::TYPE_NUMBER,
            'NumericField' => IntegrationField::TYPE_NUMBER,
            'CurrencyField' => IntegrationField::TYPE_FLOAT,
            'DateTimeField' => IntegrationField::TYPE_DATETIME,
        ];

        return $fieldTypes[$fieldType] ?? IntegrationField::TYPE_STRING;
    }

    // /**
    //  * @inheritDoc
    //  */
    private function _getCustomFields($fields, $parentFieldKey = '', $parentField = []): array
    {
        $customFields = [];

        $excludedIds = [
            'Key',
            'Type',
            'ParentKey',
            'AbEntry',
            'ReadOnly',
            'ContactKey',
        ];

        $excludedTypes = [
            'Key',
            'UidKey',
            'UidObject',
            'TxDictionary<>',
            'SecAccess2LvlField',
            'SecStatusField',
            'SalesTeamObject',
            'SalesProcessObject',
            'SalesStageObject',
            'RefLongField',
        ];

        foreach ($fields as $key => $field) {
            $readOnly = $field['readonly'] ?? false;
            $type = $field['mxtype'] ?? '';

            // Filter out some fields
            if ($readOnly || in_array($key, $excludedIds) || in_array($type, $excludedTypes)) {
                continue;
            }

            // Check for nested fields - each field has properties, so check for nested fields
            $nested = array_filter(($field['properties'] ?? []), function($item) {
                return $item['name'] ?? false;
            });

            if ($nested) {
                $customFields = array_merge($customFields, $this->_getCustomFields($nested, $key, $field));
            } else {
                $handle = $parentFieldKey ? $parentFieldKey . ':' . $key : $key;
                $name = $parentField ? $parentField['name'] . ': ' . $field['name'] : $field['name'];

                $customFields[] = new IntegrationField([
                    'handle' => $handle,
                    'name' => $name,
                    'type' => $this->_convertFieldType($type),
                ]);
            }
        }

        return $customFields;
    }

    private function _prepPayload($fields): array
    {
        return ArrayHelper::expand($fields, ':');
    }
}
