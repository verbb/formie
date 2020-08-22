<?php
namespace verbb\formie\integrations\emailmarketing;

use verbb\formie\base\Integration;
use verbb\formie\base\EmailMarketing;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\errors\IntegrationException;
use verbb\formie\events\SendIntegrationPayloadEvent;
use verbb\formie\models\IntegrationField;
use verbb\formie\models\EmailMarketingList;

use Craft;
use craft\helpers\ArrayHelper;
use craft\helpers\Json;
use craft\web\View;

class Drip extends EmailMarketing
{
    // Properties
    // =========================================================================

    public $handle = 'drip';


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
        return $this->settings['clientId'] ?? '';
    }

    /**
     * @inheritDoc
     */
    public function getClientSecret(): string
    {
        return $this->settings['clientSecret'] ?? '';
    }


    // Public Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
    public static function getName(): string
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
                $this->addError('clientSecret', Craft::t('formie', 'Client secret is required.'));
                return false;
            }
        }

        return true;
    }

    /**
     * @inheritDoc
     */
    public function fetchFormSettings()
    {
        $settings = [];

        try {
            // Fetch the account first
            $response = $this->_request('GET', 'accounts');
            $accountId = $response['accounts'][0]['id'] ?? '';

            $response = $this->_request('GET', "{$accountId}/custom_field_identifiers");
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

            $settings['lists'][] = new EmailMarketingList([
                'id' => 'all',
                'name' => 'All Subscribers',
                'fields' => $listFields,
            ]);
        } catch (\Throwable $e) {
            Integration::error($this, Craft::t('formie', 'API error: “{message}” {file}:{line}', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]));
        }

        return $settings;
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

            // Allow events to cancel sending
            if (!$this->beforeSendPayload($submission, $payload)) {
                return false;
            }

            // Fetch the account first
            $response = $this->_request('GET', 'accounts');
            $accountId = $response['accounts'][0]['id'] ?? '';

            // Add or update
            $response = $this->_request('POST', "{$accountId}/subscribers", [
                'json' => $payload,
            ]);

            // Allow events to say the response is invalid
            if (!$this->afterSendPayload($submission, $payload, $response)) {
                return false;
            }

            $contactId = $response['subscribers'][0]['id'] ?? '';

            if (!$contactId) {
                Integration::error($this, Craft::t('formie', 'API error: “{response}”', [
                    'response' => Json::encode($response),
                ]));

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
            'base_uri' => 'https://api.getdrip.com/v2/',
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