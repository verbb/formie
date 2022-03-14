<?php
namespace verbb\formie\integrations\crm;

use verbb\formie\Formie;
use verbb\formie\base\Crm;
use verbb\formie\base\Integration;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\errors\IntegrationException;
use verbb\formie\events\SendIntegrationPayloadEvent;
use verbb\formie\models\IntegrationCollection;
use verbb\formie\models\IntegrationField;
use verbb\formie\models\IntegrationFormSettings;

use Craft;
use craft\helpers\ArrayHelper;
use craft\helpers\Json;
use craft\web\View;

class Pardot extends Crm
{
    // Properties
    // =========================================================================

    public $clientId;
    public $clientSecret;
    public $businessUnitId;
    public $useSandbox = false;
    public $mapToProspect = false;
    public $mapToOpportunity = false;
    public $prospectFieldMapping;
    public $opportunityFieldMapping;


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
    public function getAuthorizeUrl(): string
    {
        return 'https://login.salesforce.com/services/oauth2/authorize';
    }

    /**
     * @inheritDoc
     */
    public function getAccessTokenUrl(): string
    {
        return 'https://login.salesforce.com/services/oauth2/token';
    }

    /**
     * @inheritDoc
     */
    public function getClientId(): string
    {
        return Craft::parseEnv($this->clientId);
    }

    /**
     * @inheritDoc
     */
    public function getClientSecret(): string
    {
        return Craft::parseEnv($this->clientSecret);
    }


    // Public Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
    public static function displayName(): string
    {
        return Craft::t('formie', 'Pardot');
    }

    /**
     * @inheritDoc
     */
    public function getDescription(): string
    {
        return Craft::t('formie', 'Manage your Pardot customers by providing important information on their conversion on your site.');
    }

    /**
     * @inheritDoc
     */
    public function defineRules(): array
    {
        $rules = parent::defineRules();

        $rules[] = [['clientId', 'clientSecret', 'businessUnitId'], 'required'];

        $prospect = $this->getFormSettingValue('prospect');
        $opportunity = $this->getFormSettingValue('opportunity');

        // Validate the following when saving form settings
        $rules[] = [['prospectFieldMapping'], 'validateFieldMapping', 'params' => $prospect, 'when' => function($model) {
            return $model->enabled && $model->mapToProspect;
        }, 'on' => [Integration::SCENARIO_FORM]];

        $rules[] = [['opportunityFieldMapping'], 'validateFieldMapping', 'params' => $opportunity, 'when' => function($model) {
            return $model->enabled && $model->mapToOpportunity;
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
            $response = $this->request('GET', 'customField/version/4/do/query');
            $fields = $response['result']['customField'] ?? [];

            $prospectFields = array_merge([
                new IntegrationField([
                    'handle' => 'salutation',
                    'name' => Craft::t('formie', 'Salutation'),
                ]),
                new IntegrationField([
                    'handle' => 'first_name',
                    'name' => Craft::t('formie', 'First Name'),
                ]),
                new IntegrationField([
                    'handle' => 'last_name',
                    'name' => Craft::t('formie', 'Last Name'),
                ]),
                new IntegrationField([
                    'handle' => 'email',
                    'name' => Craft::t('formie', 'Email'),
                    'required' => true,
                ]),
                new IntegrationField([
                    'handle' => 'password',
                    'name' => Craft::t('formie', 'Password'),
                ]),
                new IntegrationField([
                    'handle' => 'company',
                    'name' => Craft::t('formie', 'Company'),
                ]),
                new IntegrationField([
                    'handle' => 'prospect_account_id',
                    'name' => Craft::t('formie', 'Prospect Account Id'),
                    'type' => IntegrationField::TYPE_NUMBER,
                ]),
                new IntegrationField([
                    'handle' => 'website',
                    'name' => Craft::t('formie', 'Website'),
                ]),
                new IntegrationField([
                    'handle' => 'job_title',
                    'name' => Craft::t('formie', 'Job Title'),
                ]),
                new IntegrationField([
                    'handle' => 'department',
                    'name' => Craft::t('formie', 'Department'),
                ]),
                new IntegrationField([
                    'handle' => 'country',
                    'name' => Craft::t('formie', 'Country'),
                ]),
                new IntegrationField([
                    'handle' => 'address_one',
                    'name' => Craft::t('formie', 'Address Line 1'),
                ]),
                new IntegrationField([
                    'handle' => 'address_two',
                    'name' => Craft::t('formie', 'Address Line 2'),
                ]),
                new IntegrationField([
                    'handle' => 'city',
                    'name' => Craft::t('formie', 'City'),
                ]),
                new IntegrationField([
                    'handle' => 'state',
                    'name' => Craft::t('formie', 'State'),
                ]),
                new IntegrationField([
                    'handle' => 'territory',
                    'name' => Craft::t('formie', 'Territory'),
                ]),
                new IntegrationField([
                    'handle' => 'zip',
                    'name' => Craft::t('formie', 'Zip'),
                ]),
                new IntegrationField([
                    'handle' => 'phone',
                    'name' => Craft::t('formie', 'Phone'),
                ]),
                new IntegrationField([
                    'handle' => 'fax',
                    'name' => Craft::t('formie', 'Fax'),
                ]),
                new IntegrationField([
                    'handle' => 'source',
                    'name' => Craft::t('formie', 'Source'),
                ]),
                new IntegrationField([
                    'handle' => 'annual_revenue',
                    'name' => Craft::t('formie', 'Annual Revenue'),
                ]),
                new IntegrationField([
                    'handle' => 'employees',
                    'name' => Craft::t('formie', 'Employees'),
                ]),
                new IntegrationField([
                    'handle' => 'industry',
                    'name' => Craft::t('formie', 'Industry'),
                ]),
                new IntegrationField([
                    'handle' => 'years_in_business',
                    'name' => Craft::t('formie', 'Years in Business'),
                ]),
                new IntegrationField([
                    'handle' => 'comments',
                    'name' => Craft::t('formie', 'Comments'),
                ]),
                new IntegrationField([
                    'handle' => 'notes',
                    'name' => Craft::t('formie', 'Notes'),
                ]),
                new IntegrationField([
                    'handle' => 'score',
                    'name' => Craft::t('formie', 'Score'),
                    'type' => IntegrationField::TYPE_NUMBER,
                ]),
                new IntegrationField([
                    'handle' => 'is_do_not_email',
                    'name' => Craft::t('formie', 'Do Not Email'),
                    'type' => IntegrationField::TYPE_BOOLEAN,
                ]),
                new IntegrationField([
                    'handle' => 'is_do_not_call',
                    'name' => Craft::t('formie', 'Do Not Call'),
                    'type' => IntegrationField::TYPE_BOOLEAN,
                ]),
                new IntegrationField([
                    'handle' => 'is_reviewed',
                    'name' => Craft::t('formie', 'Reviewed'),
                    'type' => IntegrationField::TYPE_BOOLEAN,
                ]),
                new IntegrationField([
                    'handle' => 'is_archived',
                    'name' => Craft::t('formie', 'Archived'),
                    'type' => IntegrationField::TYPE_BOOLEAN,
                ]),
                new IntegrationField([
                    'handle' => 'is_starred',
                    'name' => Craft::t('formie', 'Starred'),
                    'type' => IntegrationField::TYPE_BOOLEAN,
                ]),
                new IntegrationField([
                    'handle' => 'campaign_id',
                    'name' => Craft::t('formie', 'Campaign ID'),
                    'type' => IntegrationField::TYPE_NUMBER,
                ]),
                new IntegrationField([
                    'handle' => 'profile',
                    'name' => Craft::t('formie', 'Profile'),
                ]),
                new IntegrationField([
                    'handle' => 'assign_to',
                    'name' => Craft::t('formie', 'Assign To'),
                ]),
            ], $this->_getCustomFields($fields));

            $opportunityFields = [
                new IntegrationField([
                    'handle' => 'name',
                    'name' => Craft::t('formie', 'Name'),
                ]),
                new IntegrationField([
                    'handle' => 'value',
                    'name' => Craft::t('formie', 'Value'),
                    'type' => IntegrationField::TYPE_NUMBER,
                ]),
                new IntegrationField([
                    'handle' => 'probability',
                    'name' => Craft::t('formie', 'Probability'),
                    'type' => IntegrationField::TYPE_NUMBER,
                ]),
                new IntegrationField([
                    'handle' => 'prospect_email',
                    'name' => Craft::t('formie', 'Prospect Email'),
                ]),
            ];

            $settings = [
                'prospect' => $prospectFields,
                'opportunity' => $opportunityFields,
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
            $prospectValues = $this->getFieldMappingValues($submission, $this->prospectFieldMapping, 'prospect');
            $opportunityValues = $this->getFieldMappingValues($submission, $this->opportunityFieldMapping, 'opportunity');

            if ($this->mapToProspect) {
                $prospectPayload = $this->_prepPayload($prospectValues);

                $response = $this->deliverPayload($submission, "prospect/version/4/do/upsert/email/{$prospectPayload['email']}", $prospectPayload, 'POST', 'form_params');

                if ($response === false) {
                    return true;
                }

                $prospectId = $response['prospect']['id'] ?? '';

                if (!$prospectId) {
                    Integration::error($this, Craft::t('formie', 'Missing return “prospectId” {response}. Sent payload {payload}', [
                        'response' => Json::encode($response),
                        'payload' => Json::encode($prospectPayload),
                    ]), true);

                    return false;
                }
            }

            if ($this->mapToOpportunity) {
                $opportunityPayload = $this->_prepPayload($opportunityValues);

                $response = $this->deliverPayload($submission, 'opportunity/version/4/do/create', $opportunityPayload, 'POST', 'form_params');

                if ($response === false) {
                    return true;
                }

                $opportunityId = $response['opportunity']['id'] ?? '';

                if (!$opportunityId) {
                    Integration::error($this, Craft::t('formie', 'Missing return “opportunityId” {response}. Sent payload {payload}', [
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
    public function getMappedFieldValue($mappedFieldValue, $submission, $integrationField)
    {
        $value = parent::getMappedFieldValue($mappedFieldValue, $submission, $integrationField);

        // SalesForce needs values delimited with semicolon's
        if ($integrationField->getType() === IntegrationField::TYPE_ARRAY) {
            $value = is_array($value) ? implode(';', $value) : $value;
        }

        return $value;
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
        $baseUrl = $this->useSandbox ? 'https://pi.demo.pardot.com/api/' : 'https://pi.pardot.com/api/';
        $businessUnitId = Craft::parseEnv($this->businessUnitId);

        $this->_client = Craft::createGuzzleClient([
            'base_uri' => $baseUrl,
            'headers' => [
                'Authorization' => 'Bearer ' . ($token->accessToken ?? 'empty'),
                'Pardot-Business-Unit-Id' => $businessUnitId,
                'Content-Type' => 'application/json',
            ],
            'query' => [
                'format' => 'json',
            ],
        ]);

        // Always provide an authenticated client - so check first.
        // We can't always rely on the EOL of the token.
        try {
            $response = $this->request('GET', 'list/version/4/do/query');
        } catch (\Throwable $e) {
            if ($e->getCode() === 401) {
                // Force-refresh the token
                Formie::$plugin->getTokens()->refreshToken($token, true);

                // Then try again, with the new access token
                $this->_client = Craft::createGuzzleClient([
                    'base_uri' => $baseUrl,
                    'headers' => [
                        'Authorization' => 'Bearer ' . ($token->accessToken ?? 'empty'),
                        'Pardot-Business-Unit-Id' => $businessUnitId,
                        'Content-Type' => 'application/json',
                    ],
                    'query' => [
                        'format' => 'json',
                    ],
                ]);
            }
        }

        return $this->_client;
    }


    // Private Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
    private function _prepPayload($fields)
    {
        $payload = $fields;

        // Check to see if the ownerId is an email, special handling for that
        $ownerId = $payload['OwnerId'] ?? '';

        if ($ownerId && strstr($ownerId, '@')) {
            $ownerId = ArrayHelper::remove($payload, 'OwnerId');

            $payload['Owner'] = [
                'attributes' => ['type' => 'User'],
                'Email' => $ownerId,
            ];
        }

        return $payload;
    }

    /**
     * @inheritDoc
     */
    private function _convertFieldType($fieldType)
    {
        $fieldTypes = [
            'Checkbox' => IntegrationField::TYPE_ARRAY,
            'Multi-Select' => IntegrationField::TYPE_ARRAY,
            'Number' => IntegrationField::TYPE_NUMBER,
            'Date' => IntegrationField::TYPE_DATE,
        ];

        return $fieldTypes[$fieldType] ?? IntegrationField::TYPE_STRING;
    }

    /**
     * @inheritDoc
     */
    private function _getCustomFields($fields, $excludeNames = [])
    {
        $customFields = [];

        foreach ($fields as $key => $field) {
            $type = $field['type'] ?? null;

            if (!$type) {
                continue;
            }

            $customFields[] = new IntegrationField([
                'handle' => $field['field_id'],
                'name' => $field['name'],
                'type' => $this->_convertFieldType($type),
            ]);
        }

        return $customFields;
    }
}