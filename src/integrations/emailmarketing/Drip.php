<?php
namespace verbb\formie\integrations\emailmarketing;

use verbb\formie\Formie;
use verbb\formie\base\Integration;
use verbb\formie\base\EmailMarketing;
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

class Drip extends EmailMarketing
{
    // Properties
    // =========================================================================

    public $clientId;
    public $clientSecret;


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
        return 'https://www.getdrip.com/oauth/authorize';
    }

    /**
     * @inheritDoc
     */
    public function getAccessTokenUrl(): string
    {
        return 'https://www.getdrip.com/oauth/token';
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
        return Craft::t('formie', 'Drip');
    }

    /**
     * @inheritDoc
     */
    public function getDescription(): string
    {
        return Craft::t('formie', 'Sign up users to your Drip lists to grow your audience for campaigns.');
    }

    /**
     * @inheritDoc
     */
    public function defineRules(): array
    {
        $rules = parent::defineRules();

        $rules[] = [['clientId', 'clientSecret'], 'required'];

        return $rules;
    }

    /**
     * @inheritDoc
     */
    public function fetchFormSettings()
    {
        $settings = [];

        try {
            // Fetch the account first
            $response = $this->request('GET', 'accounts');
            $accountId = $response['accounts'][0]['id'] ?? '';

            $response = $this->request('GET', "{$accountId}/custom_field_identifiers");
            $fields = $response['custom_field_identifiers'] ?? [];

            $listFields = [
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
                    'handle' => 'address1',
                    'name' => Craft::t('formie', 'Address 1'),
                ]),
                new IntegrationField([
                    'handle' => 'address2',
                    'name' => Craft::t('formie', 'Address 2'),
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
                    'handle' => 'zip',
                    'name' => Craft::t('formie', 'Zip'),
                ]),
                new IntegrationField([
                    'handle' => 'country',
                    'name' => Craft::t('formie', 'Country'),
                ]),
                new IntegrationField([
                    'handle' => 'phone',
                    'name' => Craft::t('formie', 'Phone'),
                ]),
            ];

            foreach ($fields as $field) {
                $listFields[] = new IntegrationField([
                    'handle' => $field,
                    'name' => $field,
                ]);
            }

            $settings['lists'][] = new IntegrationCollection([
                'id' => 'all',
                'name' => 'All Subscribers',
                'fields' => $listFields,
            ]);
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
            $fieldValues = $this->getFieldMappingValues($submission, $this->fieldMapping);

            // Pull out email, as it needs to be top level
            $email = ArrayHelper::remove($fieldValues, 'email');
            $firstName = ArrayHelper::remove($fieldValues, 'first_name');
            $lastName = ArrayHelper::remove($fieldValues, 'last_name');
            $address1 = ArrayHelper::remove($fieldValues, 'address1');
            $address2 = ArrayHelper::remove($fieldValues, 'address2');
            $city = ArrayHelper::remove($fieldValues, 'city');
            $state = ArrayHelper::remove($fieldValues, 'state');
            $zip = ArrayHelper::remove($fieldValues, 'zip');
            $country = ArrayHelper::remove($fieldValues, 'country');
            $phone = ArrayHelper::remove($fieldValues, 'phone');

            $payload = [
                'subscribers' => [
                    [
                        // API doesn't like null values
                        'email' => $email,
                        'first_name' => $firstName ?? '',
                        'last_name' => $lastName ?? '',
                        'address1' => $address1 ?? '',
                        'address2' => $address2 ?? '',
                        'city' => $city ?? '',
                        'state' => $state ?? '',
                        'zip' => $zip ?? '',
                        'country' => $country ?? '',
                        'phone' => $phone ?? '',
                        'custom_fields' => $fieldValues,
                    ],
                ],
            ];

            // Because we pass via reference, we need variables
            $endpoint = 'accounts';
            $method = 'GET';

            // Allow events to cancel sending
            if (!$this->beforeSendPayload($submission, $endpoint, $payload, $method)) {
                return true;
            }

            // Fetch the account first
            $response = $this->request('GET', 'accounts');
            $accountId = $response['accounts'][0]['id'] ?? '';

            // Allow events to say the response is invalid
            if (!$this->afterSendPayload($submission, 'accounts', $payload, 'GET', $response)) {
                return true;
            }

            $response = $this->deliverPayload($submission, "{$accountId}/subscribers", $payload);

            if ($response === false) {
                return true;
            }

            $contactId = $response['subscribers'][0]['id'] ?? '';

            if (!$contactId) {
                Integration::error($this, Craft::t('formie', 'API error: “{response}”. Sent payload {payload}', [
                    'response' => Json::encode($response),
                    'payload' => Json::encode($payload),
                ]), true);

                return false;
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
    public function getClient()
    {
        if ($this->_client) {
            return $this->_client;
        }

        $token = $this->getToken();

        $this->_client = Craft::createGuzzleClient([
            'base_uri' => 'https://api.getdrip.com/v2/',
            'headers' => [
                'Authorization' => 'Bearer ' . $token->accessToken ?? '',
                'Content-Type' => 'application/json',
            ],
        ]);

        // Always provide an authenticated client - so check first.
        // We can't always rely on the EOL of the token.
        try {
            $response = $this->request('GET', 'accounts');
        } catch (\Throwable $e) {
            if ($e->getCode() === 401) {
                // Force-refresh the token
                Formie::$plugin->getTokens()->refreshToken($token, true);

                // Then try again, with the new access token
                $this->_client = Craft::createGuzzleClient([
                    'base_uri' => 'https://api.getdrip.com/v2/',
                    'headers' => [
                        'Authorization' => 'Bearer ' . $token->accessToken ?? '',
                        'Content-Type' => 'application/json',
                    ],
                ]);
            }
        }

        return $this->_client;
    }
}