<?php
namespace verbb\formie\integrations\emailmarketing;

use verbb\formie\Formie;
use verbb\formie\base\Integration;
use verbb\formie\base\EmailMarketing;
use verbb\formie\elements\Submission;
use verbb\formie\models\IntegrationCollection;
use verbb\formie\models\IntegrationField;
use verbb\formie\models\IntegrationFormSettings;

use Craft;
use craft\helpers\App;
use craft\helpers\ArrayHelper;
use craft\helpers\DateTimeHelper;
use craft\helpers\Json;

use Throwable;

use GuzzleHttp\Client;

class ConstantContact extends EmailMarketing
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
        return Craft::t('formie', 'Constant Contact');
    }


    // Properties
    // =========================================================================

    public ?string $apiKey = null;
    public ?string $appSecret = null;


    // Public Methods
    // =========================================================================

    public function getAuthorizeUrl(): string
    {
        $useNewEndpoint = App::parseEnv('$FORMIE_INTEGRATION_CC_NEW_ENDPOINT');

        // Check for deprecated endpoint
        if (!DateTimeHelper::isInThePast('2022-04-01 00:00:00') && $useNewEndpoint !== true) {
            return 'https://api.cc.email/v3/idfed';
        }

        return 'https://authz.constantcontact.com/oauth2/default/v1/authorize';
    }

    public function getAccessTokenUrl(): string
    {
        $useNewEndpoint = App::parseEnv('$FORMIE_INTEGRATION_CC_NEW_ENDPOINT');

        // Check for deprecated endpoint
        if (!DateTimeHelper::isInThePast('2022-04-01 00:00:00') && $useNewEndpoint !== true) {
            return 'https://idfed.constantcontact.com/as/token.oauth2';
        }

        return 'https://authz.constantcontact.com/oauth2/default/v1/token';
    }

    public function getClientId(): string
    {
        return App::parseEnv($this->apiKey);
    }

    public function getClientSecret(): string
    {
        return App::parseEnv($this->appSecret);
    }

    public function getOauthScope(): array
    {
        // The non-array syntax here is deliberate
        // https://community.constantcontact.com/t5/Developer-Support-ask-questions/One-or-more-scopes-are-not-configured-for-the-authorization/m-p/383293#M12904
        return ['contact_data offline_access'];
    }

    public function getDescription(): string
    {
        return Craft::t('formie', 'Sign up users to your Constant Contact lists to grow your audience for campaigns.');
    }

    /**
     * @inheritDoc
     */
    public function defineRules(): array
    {
        $rules = parent::defineRules();

        $rules[] = [['apiKey', 'appSecret'], 'required'];

        return $rules;
    }

    public function fetchFormSettings(): IntegrationFormSettings
    {
        $settings = [];

        try {
            $response = $this->request('GET', 'contact_lists');
            $lists = $response['lists'] ?? [];

            // While we're at it, fetch the fields for the list
            $response = $this->request('GET', 'contact_custom_fields');
            $fields = $response['custom_fields'] ?? [];

            foreach ($lists as $list) {
                $listFields = array_merge([
                    new IntegrationField([
                        'handle' => 'email',
                        'name' => Craft::t('formie', 'Email'),
                        'required' => true,
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
                        'handle' => 'job_title',
                        'name' => Craft::t('formie', 'Job Title'),
                    ]),
                    new IntegrationField([
                        'handle' => 'company_name',
                        'name' => Craft::t('formie', 'Company Name'),
                    ]),
                    new IntegrationField([
                        'handle' => 'phone_number',
                        'name' => Craft::t('formie', 'Phone Number'),
                    ]),
                    new IntegrationField([
                        'handle' => 'anniversary',
                        'name' => Craft::t('formie', 'Anniversary'),
                    ]),
                ], $this->_getCustomFields($fields));

                $settings['lists'][] = new IntegrationCollection([
                    'id' => $list['list_id'],
                    'name' => $list['name'],
                    'fields' => $listFields,
                ]);
            }
        } catch (Throwable $e) {
            Integration::apiError($this, $e);
        }

        return new IntegrationFormSettings($settings);
    }

    public function sendPayload(Submission $submission): bool
    {
        try {
            $fieldValues = $this->getFieldMappingValues($submission, $this->fieldMapping);

            // Pull out email, as it needs to be top level
            $email = ArrayHelper::remove($fieldValues, 'email');

            // Deal with custom fields
            $customFields = [];

            foreach ($fieldValues as $key => $fieldValue) {
                if (str_contains($key, '-')) {
                    $customFields[] = [
                        'custom_field_id' => $key,
                        'value' => ArrayHelper::remove($fieldValues, $key),
                    ];
                }
            }

            $payload = array_merge([
                'email_address' => $email,
                'list_memberships' => [$this->listId],
                'custom_fields' => $customFields,
            ], $fieldValues);

            $response = $this->deliverPayload($submission, 'contacts/sign_up_form', $payload);

            if ($response === false) {
                return true;
            }

            $contactId = $response['contact_id'] ?? '';

            if (!$contactId) {
                Integration::error($this, Craft::t('formie', 'API error: “{response}”. Sent payload {payload}', [
                    'response' => Json::encode($response),
                    'payload' => Json::encode($payload),
                ]), true);

                return false;
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

        $token = $this->getToken();

        if (!$token) {
            Integration::apiError($this, 'Token not found for integration.', true);
        }

        $this->_client = Craft::createGuzzleClient([
            'base_uri' => 'https://api.cc.email/v3/',
            'headers' => [
                'Authorization' => 'Bearer ' . ($token->accessToken ?? 'empty'),
                'Content-Type' => 'application/json',
            ],
        ]);

        // Always provide an authenticated client - so check first.
        // We can't always rely on the EOL of the token.
        try {
            $this->request('GET', 'contact_lists');
        } catch (Throwable $e) {
            if ($e->getCode() === 401) {
                // Force-refresh the token
                Formie::$plugin->getTokens()->refreshToken($token, true);

                // Then try again, with the new access token
                $this->_client = Craft::createGuzzleClient([
                    'base_uri' => 'https://api.cc.email/v3/',
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
            'date' => IntegrationField::TYPE_DATETIME,
        ];

        return $fieldTypes[$fieldType] ?? IntegrationField::TYPE_STRING;
    }

    private function _getCustomFields($fields, $excludeNames = []): array
    {
        $customFields = [];

        foreach ($fields as $field) {
            // Exclude any names
            if (in_array($field['label'], $excludeNames)) {
                continue;
            }

            $customFields[] = new IntegrationField([
                'handle' => $field['custom_field_id'],
                'name' => $field['label'],
                'type' => $this->_convertFieldType($field['type']),
            ]);
        }

        return $customFields;
    }
}