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

class ConvertKit extends EmailMarketing
{
    // Properties
    // =========================================================================

    public $handle = 'convertKit';


    // Public Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
    public static function getName(): string
    {
        return Craft::t('formie', 'ConvertKit');
    }

    /**
     * @inheritDoc
     */
    public function getDescription(): string
    {
        return Craft::t('formie', 'Sign up users to your ConvertKit lists to grow your audience for campaigns.');
    }

    /**
     * @inheritDoc
     */
    public function beforeSave(): bool
    {
        if ($this->enabled) {
            $apiKey = $this->settings['apiKey'] ?? '';
            $apiSecret = $this->settings['apiSecret'] ?? '';

            if (!$apiKey) {
                $this->addError('apiKey', Craft::t('formie', 'API key is required.'));
                return false;
            }

            if (!$apiSecret) {
                $this->addError('apiSecret', Craft::t('formie', 'API secret is required.'));
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
            $response = $this->_request('GET', 'forms');
            $lists = $response['forms'] ?? [];

            foreach ($lists as $list) {
                // While we're at it, fetch the fields for the list
                $response = $this->_request('GET', 'custom_fields');

                $listFields = [
                    new IntegrationField([
                        'handle' => 'Email',
                        'name' => Craft::t('formie', 'Email'),
                        'required' => true,
                    ]),
                    new IntegrationField([
                        'handle' => 'FirstName',
                        'name' => Craft::t('formie', 'First Name'),
                    ]),
                ];

                $fields = $response['custom_fields'] ?? [];
            
                foreach ($fields as $field) {
                    $listFields[] = new IntegrationField([
                        'handle' => $field['key'],
                        'name' => $field['label'],
                    ]);
                }

                $settings['lists'][] = new EmailMarketingList([
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
            $email = ArrayHelper::remove($fieldValues, 'Email');
            $firstName = ArrayHelper::remove($fieldValues, 'FirstName');

            $payload = [
                'email' => $email,
                'first_name' => $firstName,
                'fields' => $fieldValues,
            ];

            // Allow events to cancel sending
            if (!$this->beforeSendPayload($submission, $payload)) {
                return false;
            }

            // Add or update
            $response = $this->_request('POST', "forms/{$this->listId}/subscribe", [
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

    /**
     * @inheritDoc
     */
    public function fetchConnection(): bool
    {
        try {
            $response = $this->_request('GET', 'account');
            $accountId = $response['primary_email_address'] ?? '';

            if (!$accountId) {
                Integration::error($this, 'Unable to find “{primary_email_address}” in response.', true);
                return false;
            }
        } catch (\Throwable $e) {
            Integration::error($this, Craft::t('formie', 'API error: “{message}” {file}:{line}', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]), true);

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

        $apiSecret = $this->settings['apiSecret'] ?? '';

        if (!$apiSecret) {
            Integration::error($this, 'Invalid API Secret for ConvertKit', true);
        }

        return $this->_client = Craft::createGuzzleClient([
            'base_uri' => 'https://api.convertkit.com/v3/',
            'query' => ['api_secret' => $apiSecret],
        ]);
    }

    private function _request(string $method, string $uri, array $options = [])
    {
        $response = $this->_getClient()->request($method, trim($uri, '/'), $options);

        return Json::decode((string)$response->getBody());
    }
}