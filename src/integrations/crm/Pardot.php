<?php
namespace verbb\formie\integrations\crm;

use verbb\formie\Formie;
use verbb\formie\base\Crm;
use verbb\formie\base\Integration;
use verbb\formie\elements\Submission;
use verbb\formie\events\ModifyPayloadEvent;
use verbb\formie\helpers\ArrayHelper;
use verbb\formie\models\IntegrationField;
use verbb\formie\models\IntegrationFormSettings;

use Craft;
use craft\helpers\App;
use craft\helpers\Json;

use Throwable;

use verbb\auth\base\OAuthProviderInterface;
use verbb\auth\models\Token;
use verbb\auth\providers\Salesforce as SalesforceProvider;

class Pardot extends Crm implements OAuthProviderInterface
{
    // Constants
    // =========================================================================

    public const EVENT_MODIFY_FORM_HANDLER_PAYLOAD = 'modifyFormHandlerPayload';


    // Static Methods
    // =========================================================================

    public static function supportsOAuthConnection(): bool
    {
        return true;
    }

    public static function getOAuthProviderClass(): string
    {
        return SalesforceProvider::class;
    }

    public static function displayName(): string
    {
        return Craft::t('formie', 'Pardot');
    }


    // Properties
    // =========================================================================

    public ?string $businessUnitId = null;
    public bool|string $useSandbox = false;
    public bool $mapToProspect = false;
    public bool $mapToOpportunity = false;
    public bool $enableFormHandler = false;
    public ?array $prospectFieldMapping = null;
    public ?array $opportunityFieldMapping = null;
    public ?string $endpointUrl = null;


    // Public Methods
    // =========================================================================

    public function getUseSandbox(): string
    {
        return App::parseBooleanEnv($this->useSandbox);
    }

    public function getBusinessUnitId(): string
    {
        return App::parseEnv($this->businessUnitId);
    }

    public function getApiDomain(): string
    {
        $prefix = $this->getUseSandbox() ? 'pi.demo' : 'pi';

        return "https://{$prefix}.pardot.com/api";
    }

    public function getBaseApiUrl(?Token $token): ?string
    {
        return rtrim($this->getApiDomain(), '/') . '/';
    }

    public function getOAuthProviderConfig(): array
    {
        $config = parent::getOAuthProviderConfig();
        $config['baseApiUrl'] = fn(?Token $token) => $this->getBaseApiUrl($token);

        return $config;
    }

    public function getDescription(): string
    {
        return Craft::t('formie', 'Manage your {name} customers by providing important information on their conversion on your site.', ['name' => static::displayName()]);
    }

    public function getAuthorizationUrlOptions(): array
    {
        $options = parent::getAuthorizationUrlOptions();

        $options['scope'] = [
            'pardot_api',
            'refresh_token',
        ];

        return $options;
    }

    public function request(string $method, string $uri, array $options = []): mixed
    {
        $options['headers']['Pardot-Business-Unit-Id'] = $this->getBusinessUnitId();
        $options['query']['format'] = 'json';

        return parent::request($method, $uri, $options);
    }

    public function fetchFormSettings(): IntegrationFormSettings
    {
        $settings = [];

        try {
            if ($this->mapToProspect) {
                $response = $this->request('GET', 'customField/version/4/do/query');
                $fields = $response['result']['customField'] ?? [];

                $booleanOptions = [
                    [
                        'label' => Craft::t('formie', 'Yes'),
                        'value' => true,
                    ],
                    [
                        'label' => Craft::t('formie', 'No'),
                        'value' => false,
                    ],
                ];

                $settings['prospect'] = array_merge([
                    new IntegrationField([
                        'handle' => 'list_id',
                        'name' => Craft::t('formie', 'Segmentation List'),
                        'options' => [
                            'label' => Craft::t('formie', 'Lists'),
                            'options' => array_map(function($list) {
                                return [
                                    'label' => $list['name'],
                                    'value' => $list['id'],
                                ];
                            }, $this->request('GET', 'list/version/4/do/query')['result']['list'] ?? []),
                        ],
                    ]),
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
                        'options' => [
                            'label' => Craft::t('formie', 'Prospect Accounts'),
                            'options' => array_map(function($prospectAccount) {
                                return [
                                    'label' => $prospectAccount['name'],
                                    'value' => $prospectAccount['id'],
                                ];
                            }, $this->request('GET', 'prospectAccount/version/4/do/query')['result']['prospectAccount'] ?? []),
                        ],
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
                        'options' => [
                            'label' => Craft::t('formie', 'Do Not Email'),
                            'options' => $booleanOptions,
                        ],
                    ]),
                    new IntegrationField([
                        'handle' => 'is_do_not_call',
                        'name' => Craft::t('formie', 'Do Not Call'),
                        'type' => IntegrationField::TYPE_BOOLEAN,
                        'options' => [
                            'label' => Craft::t('formie', 'Do Not Call'),
                            'options' => $booleanOptions,
                        ],
                    ]),
                    new IntegrationField([
                        'handle' => 'is_reviewed',
                        'name' => Craft::t('formie', 'Reviewed'),
                        'type' => IntegrationField::TYPE_BOOLEAN,
                        'options' => [
                            'label' => Craft::t('formie', 'Reviewed'),
                            'options' => $booleanOptions,
                        ],
                    ]),
                    new IntegrationField([
                        'handle' => 'is_archived',
                        'name' => Craft::t('formie', 'Archived'),
                        'type' => IntegrationField::TYPE_BOOLEAN,
                        'options' => [
                            'label' => Craft::t('formie', 'Archived'),
                            'options' => $booleanOptions,
                        ],
                    ]),
                    new IntegrationField([
                        'handle' => 'is_starred',
                        'name' => Craft::t('formie', 'Starred'),
                        'type' => IntegrationField::TYPE_BOOLEAN,
                        'options' => [
                            'label' => Craft::t('formie', 'Starred'),
                            'options' => $booleanOptions,
                        ],
                    ]),
                    new IntegrationField([
                        'handle' => 'campaign_id',
                        'name' => Craft::t('formie', 'Campaign ID'),
                        'type' => IntegrationField::TYPE_NUMBER,
                        'options' => [
                            'label' => Craft::t('formie', 'Campaigns'),
                            'options' => array_map(function($campaign) {
                                return [
                                    'label' => $campaign['name'],
                                    'value' => $campaign['id'],
                                ];
                            }, $this->request('GET', 'campaign/version/4/do/query')['result']['campaign'] ?? []),
                        ],
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
            }

            if ($this->mapToOpportunity) {
                $settings['opportunity'] = [
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
            }
        } catch (Throwable $e) {
            Integration::apiError($this, $e);
        }

        return new IntegrationFormSettings($settings);
    }

    public function sendPayload(Submission $submission): bool
    {
        try {
            $prospectValues = $this->getFieldMappingValues($submission, $this->prospectFieldMapping, 'prospect');
            $opportunityValues = $this->getFieldMappingValues($submission, $this->opportunityFieldMapping, 'opportunity');

            if ($this->mapToProspect) {
                // Remove a segmented list ID, if picked
                $listId = ArrayHelper::remove($prospectValues, 'list_id');

                $prospectPayload = $this->_prepPayload($prospectValues);

                // It'd be great to use `upsert/email/{email}` but that always creates a new prospect - useless!!
                // https://developer.salesforce.com/docs/marketing/pardot/guide/prospects-v4.html#prospect-upsert
                // Even more annoying it throws an error if the email wasn't found...
                try {
                    $response = $this->request('GET', "prospect/version/4/do/read/email/{$prospectPayload['email']}");

                    // This can either be a single prospect, or multiple prospects
                    $prospectId = $response['prospect']['id'] ?? $response['prospect'][0]['id'] ?? '';

                    if ($prospectId) {
                        $response = $this->deliverPayload($submission, "prospect/version/4/do/update/id/{$prospectId}", $prospectPayload, 'POST', 'form_params');
                    } else {
                        $response = $this->deliverPayload($submission, "prospect/version/4/do/create/{$prospectPayload['email']}", $prospectPayload, 'POST', 'form_params');
                    }
                } catch (Throwable $e) {
                    $response = $this->deliverPayload($submission, "prospect/version/4/do/create/{$prospectPayload['email']}", $prospectPayload, 'POST', 'form_params');
                }

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

                // If there was a segmented list to add the prospect to...
                if ($listId) {
                    try {
                        $this->deliverPayload($submission, "listMembership/version/4/do/create/list_id/{$listId}/{$prospectId}", [
                            'list_id' => $listId,
                            'prospect_id' => $prospectId,
                        ], 'POST', 'form_params');
                    } catch (Throwable $e) {
                        // An error will indicate they're already part of the list, so ignore
                    }
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

            if ($this->enableFormHandler) {
                // Generate flat payload values to send
                $payload = $this->generatePayloadValues($submission);

                // Send a raw request to the endpoint
                $client = Craft::createGuzzleClient();
                $request = $client->request('POST', $this->endpointUrl, [
                    'form_params' => $payload,
                ]);

                // Parse the response, which will be plain text
                $response = (string)$request->getBody();

                if (str_contains($response, 'Please correct the following errors')) {
                    Integration::error($this, Craft::t('formie', 'Error in form handler response {response}. Sent payload {payload}', [
                        'response' => $response,
                        'payload' => Json::encode($payload),
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

    public function getMappedFieldValue(string $mappedFieldValue, Submission $submission, IntegrationField $integrationField): mixed
    {
        $value = parent::getMappedFieldValue($mappedFieldValue, $submission, $integrationField);

        // SalesForce needs values delimited with semicolon's
        if ($integrationField->getType() === IntegrationField::TYPE_ARRAY) {
            $value = is_array($value) ? implode(';', $value) : $value;
        }

        return $value;
    }

    public function populateContext(): void
    {
        parent::populateContext();

        // Allow us to save the tracking cookie at the time of submission, so grab later
        $trackingData = [];
        $pattern = '/^visitor_id[0-9]+(-hash)?$/';

        foreach ($_COOKIE as $key => $value) {
            if (preg_match($pattern, $key)) {
                $trackingData[$key] = $value;
            }
        }

        $this->context['pardot_tracking'] = $trackingData;
    }

    
    // Protected Methods
    // =========================================================================

    protected function defineRules(): array
    {
        $rules = parent::defineRules();

        $rules[] = [['businessUnitId'], 'required'];

        $prospect = $this->getFormSettingValue('prospect');
        $opportunity = $this->getFormSettingValue('opportunity');

        // Validate the following when saving form settings
        $rules[] = [
            ['prospectFieldMapping'], 'validateFieldMapping', 'params' => $prospect, 'when' => function($model) {
                return $model->enabled && $model->mapToProspect;
            }, 'on' => [Integration::SCENARIO_FORM],
        ];

        $rules[] = [
            ['opportunityFieldMapping'], 'validateFieldMapping', 'params' => $opportunity, 'when' => function($model) {
                return $model->enabled && $model->mapToOpportunity;
            }, 'on' => [Integration::SCENARIO_FORM],
        ];

        $rules[] = [
            ['endpointUrl'], 'required', 'when' => function($model) {
                return $model->enabled && $model->enableFormHandler;
            }, 'on' => [Integration::SCENARIO_FORM],
        ];

        return $rules;
    }

    protected function generatePayloadValues(Submission $submission): array
    {
        $payloadData = $this->generateSubmissionPayloadValues($submission);

        $payload = $payloadData['submission'] ?? [];

        // Flatten array to dot-notation
        $payload = ArrayHelper::flatten($payload);

        // Add in cookie tracking support, if available
        $trackingData = $this->context['pardot_tracking'] ?? [];
        $payload = array_merge($payload, $trackingData);

        // Fire a 'modifyPayload' event
        $event = new ModifyPayloadEvent([
            'submission' => $submission,
            'payload' => $payload,
        ]);
        $this->trigger(self::EVENT_MODIFY_FORM_HANDLER_PAYLOAD, $event);

        return $event->payload;
    }


    // Private Methods
    // =========================================================================

    private function _prepPayload($fields)
    {
        $payload = $fields;

        // Check to see if the ownerId is an email, special handling for that
        $ownerId = $payload['OwnerId'] ?? '';

        if ($ownerId && str_contains($ownerId, '@')) {
            $ownerId = ArrayHelper::remove($payload, 'OwnerId');

            $payload['Owner'] = [
                'attributes' => ['type' => 'User'],
                'Email' => $ownerId,
            ];
        }

        return $payload;
    }

    private function _convertFieldType(string $fieldType): string
    {
        $fieldTypes = [
            'Checkbox' => IntegrationField::TYPE_ARRAY,
            'Multi-Select' => IntegrationField::TYPE_ARRAY,
            'Number' => IntegrationField::TYPE_NUMBER,
            'Date' => IntegrationField::TYPE_DATE,
        ];

        return $fieldTypes[$fieldType] ?? IntegrationField::TYPE_STRING;
    }

    private function _getCustomFields(array $fields, array $excludeNames = []): array
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
                'sourceType' => $type,
            ]);
        }

        return $customFields;
    }
}
