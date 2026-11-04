<?php
namespace verbb\formie\integrations\emailmarketing;

use verbb\formie\attributes\Sensitive;
use verbb\formie\base\EmailMarketing;
use verbb\formie\base\Integration;
use verbb\formie\elements\Submission;
use verbb\formie\helpers\ArrayHelper;
use verbb\formie\helpers\IntegrationApiErrors;
use verbb\formie\models\IntegrationCollection;
use verbb\formie\models\IntegrationConfig;
use verbb\formie\models\IntegrationField;
use verbb\formie\models\IntegrationResult;

use Craft;
use craft\helpers\App;
use craft\helpers\Json;

use Throwable;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class CampaignMonitor extends EmailMarketing
{
    // Static Methods
    // =========================================================================

    public static function displayName(): string
    {
        return 'Campaign Monitor';
    }


    // Properties
    // =========================================================================

    #[Sensitive]
    public ?string $apiKey = null;

    public ?string $clientId = null;

    public array $apiErrorHandling = [];


    // Public Methods
    // =========================================================================

    public function getDescription(): string
    {
        return Craft::t('formie', 'Sign up users to your {name} lists to grow your audience for campaigns.', ['name' => static::displayName()]);
    }

    public function getSettingsHtmlVariables(): array
    {
        return parent::getSettingsHtmlVariables() + [
            'apiErrorHandling' => IntegrationApiErrors::handlingActions($this),
            'apiErrorActionOptions' => IntegrationApiErrors::actionOptions(),
        ];
    }

    public function fetchConfig(): IntegrationConfig
    {
        $settings = [];

        try {
            $clientId = App::parseEnv($this->clientId);
            $lists = $this->request('GET', "clients/{$clientId}/lists.json");

            foreach ($lists as $list) {
                // While we're at it, fetch the fields for the list
                $fields = $this->request('GET', 'lists/' . $list['ListID'] . '/customfields.json');

                $listFields = array_merge([
                    new IntegrationField([
                        'handle' => 'Email',
                        'name' => Craft::t('formie', 'Email'),
                        'required' => true,
                    ]),
                    new IntegrationField([
                        'handle' => 'Name',
                        'name' => Craft::t('formie', 'Name'),
                    ]),
                    new IntegrationField([
                        'handle' => 'MobileNumber',
                        'name' => Craft::t('formie', 'Mobile Number'),
                        'type' => IntegrationField::TYPE_PHONE,
                    ]),
                    new IntegrationField([
                        'handle' => 'ConsentToTrack',
                        'name' => Craft::t('formie', 'Consent To Track'),
                        'type' => IntegrationField::TYPE_BOOLEAN,
                        'options' => [
                            'label' => Craft::t('formie', 'Consent To Track'),
                            'options' => [
                                [
                                    'label' =>  Craft::t('formie', 'Yes'),
                                    'value' => true,
                                ],
                                [
                                    'label' =>  Craft::t('formie', 'No'),
                                    'value' => false,
                                ],
                            ],
                        ],
                    ]),
                    new IntegrationField([
                        'handle' => 'ConsentToSendSms',
                        'name' => Craft::t('formie', 'Consent To Send SMS'),
                        'type' => IntegrationField::TYPE_BOOLEAN,
                        'options' => [
                            'label' => Craft::t('formie', 'Consent To Track'),
                            'options' => [
                                [
                                    'label' =>  Craft::t('formie', 'Yes'),
                                    'value' => true,
                                ],
                                [
                                    'label' =>  Craft::t('formie', 'No'),
                                    'value' => false,
                                ],
                            ],
                        ],
                    ]),
                ], $this->_getCustomFields($fields));

                $settings['lists'][] = new IntegrationCollection([
                    'id' => $list['ListID'],
                    'name' => $list['Name'],
                    'fields' => $listFields,
                ]);
            }
        } catch (Throwable $e) {
            Integration::apiError($this, $e);
        }

        return new IntegrationConfig($settings);
    }

    public function supportsIntegrationApiErrorSeverity(): bool
    {
        return true;
    }

    public function classifyIntegrationApiError(Throwable $exception): ?string
    {
        if (!$exception instanceof RequestException || !$exception->getResponse()) {
            return null;
        }

        $statusCode = $exception->getResponse()->getStatusCode();
        $body = json_decode((string)$exception->getResponse()->getBody(), true);

        // A subscriber-specific throttle uses Code 429 too. Match it before the
        // general API limit, and don't confuse a JSON error code with HTTP status.
        if (in_array($statusCode, [400, 429], true) && is_array($body) && in_array($body['Code'] ?? null, [429, '429'], true) &&
            ($body['Message'] ?? null) === 'Subscriber was added too many times too quickly, try again later.') {
            return IntegrationApiErrors::SEVERITY_REJECTED;
        }

        if ($statusCode === 429) {
            return IntegrationApiErrors::SEVERITY_RATE_LIMITED;
        }

        return null;
    }

    public function fetchConnection(): bool
    {
        try {
            $clientId = App::parseEnv($this->clientId);
            $response = $this->request('GET', "clients/{$clientId}.json");
            $error = $response['error'] ?? '';
            $apiKey = $response['ApiKey'] ?? '';

            if ($error) {
                Integration::error($this, $error, true);

                return false;
            }

            if (!$apiKey) {
                Integration::error($this, 'Unable to find “{ApiKey}” in response.', true);

                return false;
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
        $rules[] = ['apiErrorHandling', 'each', 'rule' => ['in', 'range' => array_column(IntegrationApiErrors::actionOptions(), 'value')]];

        return $rules;
    }

    protected function executePayload(Submission $submission): IntegrationResult
    {
        $this->beginPayloadDelivery($submission);

        try {
            $fieldValues = $this->getFieldMappingValues($submission, $this->fieldMapping);

            // Pull out email, as it needs to be top level
            $email = ArrayHelper::remove($fieldValues, 'Email');
            $name = ArrayHelper::remove($fieldValues, 'Name');
            $mobileNumber = ArrayHelper::remove($fieldValues, 'MobileNumber');
            $consentToTrack = ArrayHelper::remove($fieldValues, 'ConsentToTrack');
            $consentToSendSms = ArrayHelper::remove($fieldValues, 'ConsentToSendSms');

            // Format custom fields
            $customFields = [];

            foreach ($fieldValues as $key => $value) {
                if (is_array($value)) {
                    foreach ($value as $v) {
                        $customFields[] = [
                            'Key' => $key,
                            'Value' => $v,
                        ];
                    }
                } else {
                    $customFields[] = [
                        'Key' => $key,
                        'Value' => $value,
                    ];
                }
            }

            $payload = [
                'EmailAddress' => $email,
                'Name' => $name,
                'CustomFields' => $customFields,
                'Resubscribe' => true,
                'RestartSubscriptionBasedAutoresponders' => true,
            ];

            if ($consentToTrack !== null) {
                $payload['ConsentToTrack'] = $consentToTrack ? 'Yes' : 'No';
            }

            if ($consentToSendSms !== null) {
                $payload['ConsentToSendSms'] = $consentToSendSms ? 'Yes' : 'No';
            }

            if ($mobileNumber) {
                $payload['MobileNumber'] = $mobileNumber;
            }

            $response = $this->deliverPayload($submission, "subscribers/{$this->listId}.json", $payload);

            if ($response === false) {
                return $this->resultForPayload(true);
            }
        } catch (Throwable $e) {
            if ($this->supportsIntegrationApiErrorSeverity()) {
                return $this->resultForPayload($this->handleSubmissionApiError($e, $submission));
            }

            Integration::apiError($this, $e, true, $submission);

            return $this->resultForPayload(false);
        }

        return $this->resultForPayload(true);
    }

    protected function defineClient(): Client
    {
        return Craft::createGuzzleClient([
            'base_uri' => 'https://api.createsend.com/api/v3.2/',
            'auth' => [App::parseEnv($this->apiKey), 'formie'],
        ]);
    }


    // Private Methods
    // =========================================================================

    private function _convertFieldType(string $fieldType): string
    {
        $fieldTypes = [
            'Date' => IntegrationField::TYPE_DATE,
            'Number' => IntegrationField::TYPE_NUMBER,
            'MultiSelectMany' => IntegrationField::TYPE_ARRAY,
        ];

        return $fieldTypes[$fieldType] ?? IntegrationField::TYPE_STRING;
    }

    private function _getCustomFields(array $fields, array $excludeNames = []): array
    {
        $customFields = [];

        foreach ($fields as $field) {
            // Exclude any names
            if (in_array($field['FieldName'], $excludeNames)) {
                continue;
            }

            $customFields[] = new IntegrationField([
                'handle' => str_replace(['[', ']'], '', $field['Key']),
                'name' => $field['FieldName'],
                'type' => $this->_convertFieldType($field['DataType']),
                'sourceType' => $field['DataType'],
            ]);
        }

        return $customFields;
    }
}
