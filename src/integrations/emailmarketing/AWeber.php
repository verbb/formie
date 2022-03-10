<?php
namespace verbb\formie\integrations\emailmarketing;

use verbb\formie\Formie;
use verbb\formie\base\Integration;
use verbb\formie\base\EmailMarketing;
use verbb\formie\elements\Submission;
use verbb\formie\errors\IntegrationException;
use verbb\formie\models\IntegrationCollection;
use verbb\formie\models\IntegrationField;
use verbb\formie\models\IntegrationFormSettings;

use Craft;
use craft\helpers\App;
use craft\helpers\ArrayHelper;
use craft\helpers\Json;

use Throwable;

use GuzzleHttp\Client;

class AWeber extends EmailMarketing
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
        return Craft::t('formie', 'AWeber');
    }

    // Properties
    // =========================================================================

    public ?string $clientId = null;
    public ?string $clientSecret = null;


    // Public Methods
    // =========================================================================

    public function getAuthorizeUrl(): string
    {
        return 'https://auth.aweber.com/oauth2/authorize';
    }

    public function getAccessTokenUrl(): string
    {
        return 'https://auth.aweber.com/oauth2/token';
    }

    public function getResourceOwner(): string
    {
        return 'https://api.aweber.com/1.0/accounts';
    }

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
            'account.read',
            'list.read',
            'list.write',
            'subscriber.read',
            'subscriber.write',
            'email.read',
            'email.write',
            'subscriber.read-extended',
            'landing-page.read',
        ];
    }

    public function getOauthProviderConfig(): array
    {
        return array_merge(parent::getOauthProviderConfig(), [
            'scopeSeparator' => ' ',
        ]);
    }

    public function getDescription(): string
    {
        return Craft::t('formie', 'Sign up users to your AWeber lists to grow your audience for campaigns.');
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

    public function fetchFormSettings(): IntegrationFormSettings
    {
        $settings = [];

        try {
            // Find the account first to fetch lists
            $response = $this->request('GET', 'accounts');
            $accounts = $response['entries'] ?? [];

            $listsUrl = $accounts[0]['lists_collection_link'] ?? '';
            $listsUrl = str_replace('https://api.aweber.com/1.0/', '', $listsUrl);

            $response = $this->request('GET', $listsUrl);
            $lists = $response['entries'] ?? [];

            foreach ($lists as $list) {
                // While we're at it, fetch the fields for the list
                $response = $this->request('GET', "{$listsUrl}/{$list['id']}/custom_fields");
                $fields = $response['entries'] ?? [];

                $listFields = [
                    new IntegrationField([
                        'handle' => 'email',
                        'name' => Craft::t('formie', 'Email'),
                        'required' => true,
                    ]),
                    new IntegrationField([
                        'handle' => 'name',
                        'name' => Craft::t('formie', 'Name'),
                    ]),
                ];

                foreach ($fields as $field) {
                    $listFields[] = new IntegrationField([
                        'handle' => $field['name'],
                        'name' => $field['name'],
                    ]);
                }

                $settings['lists'][] = new IntegrationCollection([
                    'id' => (string)$list['id'],
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
            $name = ArrayHelper::remove($fieldValues, 'name');

            $payload = [
                'email' => $email,
                'name' => $name,
                'custom_fields' => $fieldValues,
                'update_existing' => true,
            ];

            // Because we pass via reference, we need variables
            $endpoint = 'accounts';
            $method = 'GET';

            // Allow events to cancel sending
            if (!$this->beforeSendPayload($submission, $endpoint, $payload, $method)) {
                return true;
            }

            // Find the account first to fetch lists
            $response = $this->request('GET', 'accounts');
            $accounts = $response['entries'] ?? [];
            $listsUrl = $accounts[0]['lists_collection_link'] ?? '';
            $listsUrl = str_replace('https://api.aweber.com/1.0/', '', $listsUrl);

            // Allow events to say the response is invalid
            if (!$this->afterSendPayload($submission, 'accounts', $payload, 'GET', $response)) {
                return true;
            }

            if (!$listsUrl) {
                Integration::error($this, Craft::t('formie', 'API error: “{response}”. Sent payload {payload}', [
                    'response' => Json::encode($response),
                    'payload' => Json::encode($payload),
                ]), true);

                return false;
            }

            $response = $this->deliverPayload($submission, "{$listsUrl}/{$this->listId}/subscribers", $payload);

            if ($response === false) {
                return true;
            }
        } catch (Throwable $e) {
            Integration::apiError($this, $e);

            return false;
        }

        return true;
    }

    /**
     * @throws IntegrationException
     */
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
            'base_uri' => 'https://api.aweber.com/1.0/',
            'headers' => [
                'Authorization' => 'Bearer ' . ($token->accessToken ?? 'empty'),
                'Content-Type' => 'application/json',
            ],
        ]);

        // Always provide an authenticated client - so check first.
        // We can't always rely on the EOL of the token.
        try {
            $this->request('GET', 'accounts');
        } catch (Throwable $e) {
            if ($e->getCode() === 401) {
                // Force-refresh the token
                Formie::$plugin->getTokens()->refreshToken($token, true);

                // Then try again, with the new access token
                $this->_client = Craft::createGuzzleClient([
                    'base_uri' => 'https://api.aweber.com/1.0/',
                    'headers' => [
                        'Authorization' => 'Bearer ' . ($token->accessToken ?? 'empty'),
                        'Content-Type' => 'application/json',
                    ],
                ]);
            }
        }

        return $this->_client;
    }
}