<?php
namespace verbb\formie\integrations\emailmarketing;

use verbb\formie\base\Integration;
use verbb\formie\base\EmailMarketing;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\errors\IntegrationException;
use verbb\formie\events\SendIntegrationPayloadEvent;
use verbb\formie\models\EmailMarketingField;
use verbb\formie\models\EmailMarketingList;

use Craft;
use craft\helpers\ArrayHelper;
use craft\helpers\Json;
use craft\web\View;

class AWeber extends EmailMarketing
{
    // Properties
    // =========================================================================

    public $handle = 'aweber';


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
        return 'https://auth.aweber.com/oauth2/authorize';
    }

    /**
     * @inheritDoc
     */
    public function getAccessTokenUrl(): string
    {
        return 'https://auth.aweber.com/oauth2/token';
    }

    /**
     * @inheritDoc
     */
    public function getResourceOwner(): string
    {
        return 'https://api.aweber.com/1.0/accounts';
    }

    /**
     * @inheritDoc
     */
    public function getClientId(): string
    {
        return $this->settings['clientId'] ?? '';
    }

    /**
     * @inheritDoc
     */
    public function getClientSecret(): string
    {
        return $this->settings['clientSecret'] ?? '';
    }

    /**
     * @inheritDoc
     */
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

    /**
     * @inheritDoc
     */
    public function getOauthProviderConfig()
    {
        return array_merge(parent::getOauthProviderConfig(), [
            'scopeSeparator' => ' ',
        ]);
    }


    // Public Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
    public static function getName(): string
    {
        return Craft::t('formie', 'AWeber');
    }

    /**
     * @inheritDoc
     */
    public function getDescription(): string
    {
        return Craft::t('formie', 'Sign up users to your AWeber lists to grow your audience for campaigns.');
    }

    /**
     * @inheritDoc
     */
    public function beforeSave(): bool
    {
        if ($this->enabled) {
            $clientId = $this->settings['clientId'] ?? '';
            $clientSecret = $this->settings['clientSecret'] ?? '';

            if (!$clientId) {
                $this->addError('clientId', Craft::t('formie', 'Client ID is required.'));
                return false;
            }

            if (!$clientSecret) {
                $this->addError('clientSecret', Craft::t('formie', 'Client Secret is required.'));
                return false;
            }
        }

        return true;
    }

    /**
     * @inheritDoc
     */
    public function fetchLists()
    {
        $allLists = [];

        try {
            // Find the account first to fetch lists
            $response = $this->_request('GET', 'accounts');
            $accounts = $response['entries'] ?? [];

            $listsUrl = $accounts[0]['lists_collection_link'] ?? '';
            $listsUrl = str_replace('https://api.aweber.com/1.0/', '', $listsUrl);

            $response = $this->_request('GET', $listsUrl);
            $lists = $response['entries'] ?? [];

            foreach ($lists as $list) {
                // While we're at it, fetch the fields for the list
                $response = $this->_request('GET', "{$listsUrl}/{$list['id']}/custom_fields");

                $listFields = [
                    new EmailMarketingField([
                        'tag' => 'email',
                        'name' => Craft::t('formie', 'Email'),
                        'type' => 'email',
                        'required' => true,
                    ]),
                    new EmailMarketingField([
                        'tag' => 'name',
                        'name' => Craft::t('formie', 'Name'),
                        'type' => 'name',
                    ]),
                ];

                $fields = $response['entries'] ?? [];

                foreach ($fields as $field) {
                    $listFields[] = new EmailMarketingField([
                        'tag' => $field['name'],
                        'name' => $field['name'],
                        'type' => 'string',
                    ]);
                }

                $allLists[] = new EmailMarketingList([
                    'id' => (string)$list['id'],
                    'name' => $list['name'],
                    'fields' => $listFields,
                ]);
            }
        } catch (\Throwable $e) {
            Integration::error($this, Craft::t('formie', 'API error: “{message}” {file}:{line}', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]));
        }

        return $allLists;
    }

    /**
     * @inheritDoc
     */
    public function sendPayload(Submission $submission): bool
    {
        try {
            $fieldValues = $this->getFieldMappingValues($submission);

            // Pull out email, as it needs to be top level
            $email = ArrayHelper::remove($fieldValues, 'email');
            $name = ArrayHelper::remove($fieldValues, 'name');

            $payload = [
                'email' => $email,
                'name' => $name,
                'custom_fields' => $fieldValues,
                'update_existing' => true,
            ];

            // Allow events to cancel sending
            if (!$this->beforeSendPayload($submission, $payload)) {
                return false;
            }

            // Find the account first to fetch lists
            $response = $this->_request('GET', 'accounts');
            $accounts = $response['entries'] ?? [];
            $listsUrl = $accounts[0]['lists_collection_link'] ?? '';
            $listsUrl = str_replace('https://api.aweber.com/1.0/', '', $listsUrl);

            // Allow events to say the response is invalid
            if (!$this->afterSendPayload($submission, $payload, $response)) {
                return false;
            }

            if (!$listsUrl) {
                Integration::error($this, Craft::t('formie', 'API error: “{response}”', [
                    'response' => Json::encode($response),
                ]));

                return false;
            }

            // Add or update
            $response = $this->_request('POST', "{$listsUrl}/{$this->listId}/subscribers", [
                'json' => $payload,
            ]);

            // Allow events to say the response is invalid
            if (!$this->afterSendPayload($submission, $payload, $response)) {
                return false;
            }
        } catch (\Throwable $e) {
            Integration::error($this, Craft::t('formie', 'API error: “{message}” {file}:{line}', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]));

            return false;
        }

        return true;
    }


    // Private Methods
    // =========================================================================

    private function _getClient()
    {
        if ($this->_client) {
            return $this->_client;
        }

        return $this->_client = Craft::createGuzzleClient([
            'base_uri' => 'https://api.aweber.com/1.0/',
            'headers' => [
                'Authorization' => 'Bearer ' . $this->getToken()->accessToken ?? '',
                'Content-Type' => 'application/json',
            ],
        ]);
    }

    private function _request(string $method, string $uri, array $options = [])
    {
        $response = $this->_getClient()->request($method, trim($uri, '/'), $options);

        return Json::decode((string)$response->getBody());
    }
}